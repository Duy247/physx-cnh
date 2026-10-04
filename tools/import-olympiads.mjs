import { createHash } from 'node:crypto';
import { mkdir, readdir, stat, copyFile, writeFile, rename } from 'node:fs/promises';
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const physicsRoot = path.join(root, 'physics');
const phoxivRoot = process.env.PHOXIV_OLYMPIADS_ROOT || 'C:/Users/DuyVan/phoxiv-pdf-downloader/downloads/cdn.phoxiv.org/olympiads';
const importedRoot = path.join(physicsRoot, 'materials', 'cdn.phoxiv.org', 'olympiads');
const manifestPath = path.join(physicsRoot, 'catalog', 'olympiads.json');
const auditPath = path.join(physicsRoot, 'catalog', 'olympiad-duplicate-audit.json');
const excludedFiles = new Set(['materials/ipho.olimpicos.net/pdf/IPhO_2022_S5.pdf']); // Zero-page PDF; retain on disk until a valid replacement is available.

const labels = {
  ipho: 'International Physics Olympiad (IPhO)', apho: 'Asian Physics Olympiad (APhO)', eupho: 'European Physics Olympiad (EuPhO)',
  nbpho: 'Nordic-Baltic Physics Olympiad (NbPhO)', rmph: 'Romanian Master of Physics (RMPh)',
  aupho: 'Australian Physics Olympiad (AuPhO)', 'bpho-r1': 'British Physics Olympiad — Round 1', 'bpho-r2': 'British Physics Olympiad — Round 2',
  'cpho-f': 'Chinese Physics Olympiad — Final', eotvos: 'Eötvös Competition', fma: 'F=ma Contest', gpho: 'Gulf Physics Olympiad (GPhO)',
  inpho: 'Indian Physics Olympiad (InPhO)', izho: 'Izho Physics Olympiad', kphc: 'Korea Physics High School Competition',
  sjpo: 'Singapore Junior Physics Olympiad (SJPO)', spho: 'Singapore Physics Olympiad (SPhO)', spot: 'Singapore Physics Olympiad Training',
  upho: 'Ukraine Physics Olympiad (UPhO)', usapho: 'USA Physics Olympiad (USAPhO)', usatst: 'USA Physics Team Selection Test',
  wopho: 'World Open Physics Olympiad (WOPhO)', twpho: 'Taiwan Physics Olympiad', twtst: 'Taiwan Team Selection Test',
  rupho: 'Russian Physics Olympiad (RuPhO)',
};

const roleLabels = { problem: 'Problem', paper: 'Paper', solution: 'Solution', marking: 'Marking scheme', answer: 'Answer sheet', results: 'Results', reference: 'Reference', guidance: 'Guide', document: 'Document' };
const paperTypeLabels = { theoretical: 'Theoretical', experimental: 'Experimental' };
const competitionLabel = (id) => labels[id] || id.toUpperCase();
const normalizeCompetition = (id) => ({ 'rupho-w': 'rupho', 'rupho-x': 'rupho', 'rupho-y': 'rupho' }[id] || id);

async function walk(directory) {
  const files = [];
  for (const entry of await readdir(directory, { withFileTypes: true })) {
    const absolute = path.join(directory, entry.name);
    if (entry.isDirectory()) files.push(...await walk(absolute));
    else if (entry.isFile() && path.extname(entry.name).toLowerCase() === '.pdf') files.push(absolute);
  }
  return files;
}

async function copyPhoxiv() {
  const sourceFiles = await walk(phoxivRoot);
  let copied = 0;
  for (const source of sourceFiles) {
    const relative = path.relative(phoxivRoot, source);
    const target = path.join(importedRoot, relative);
    try { await stat(target); } catch {
      await mkdir(path.dirname(target), { recursive: true });
      await copyFile(source, target);
      copied += 1;
    }
  }
  return { sourceFiles, copied };
}

async function sha256(filename) {
  const { createReadStream } = await import('node:fs');
  return await new Promise((resolve, reject) => {
    const hash = createHash('sha256');
    createReadStream(filename).on('error', reject).on('data', (chunk) => hash.update(chunk)).on('end', () => resolve(hash.digest('hex')));
  });
}

function yearFrom(relative) {
  const matches = [...relative.matchAll(/(?<!\d)(?:19|20)\d{2}(?!\d)/g)].map((match) => Number(match[0]));
  if (matches.length !== 1) throw new Error(`Expected exactly one year in ${relative}`);
  return matches[0];
}

function documentMeta(relative) {
  const normalized = relative.replace(/\\/g, '/').replace(/\.pdf$/i, '').toLowerCase();
  const tokens = normalized.split(/[\/_-]+/).filter(Boolean);
  const codes = tokens.map((token) => token.match(/^(t|e|q|s)(\d+)?(?:g0)?$/)).filter(Boolean);
  const coded = [...codes].reverse().find((match) => match[2]) || codes.at(-1);
  const code = coded?.[1];
  const number = coded?.[2] ? Number(coded[2]) : undefined;
  const paperType = /(?:^|[_/\-])(?:theory|theoretical|t)(?:\d|g0|[_/\-]|$)/.test(normalized) ? 'theoretical'
    : /(?:^|[_/\-])(?:experiment|experimental|e)(?:\d|g0|[_/\-]|$)/.test(normalized) ? 'experimental'
      : code === 't' ? 'theoretical' : code === 'e' ? 'experimental' : undefined;
  const pathNumber = [...tokens].reverse().map((token) => token.match(/^\d{1,2}$/)).find(Boolean)?.[0];
  const problemNumber = number || (pathNumber ? Number(pathNumber) : undefined);
  let role = 'document';
  if (/(?:^|[_/\-])(?:solution|solutions|sol|s)(?:[_/\-]|$)/.test(normalized) || (code === 's' && problemNumber)) role = 'solution';
  else if (/(?:^|[_/\-])(?:marking|mark|m)(?:[_/\-]|$)/.test(normalized)) role = 'marking';
  else if (/(?:^|[_/\-])(?:answer|answers|a)(?:[_/\-]|$)/.test(normalized)) role = 'answer';
  else if (/(?:^|[_/\-])(?:results|result|report|proc|r)(?:[_/\-]|$)/.test(normalized)) role = 'results';
  else if (/(?:^|[_/\-])(?:min|reference|handbook)(?:[_/\-]|$)/.test(normalized)) role = 'reference';
  else if (/(?:^|[_/\-])(?:guide|guidance|tg0|eg0)(?:[_/\-]|$)/.test(normalized)) role = 'guidance';
  else if (/(?:^|[_/\-])(?:problem|problems|q|p)(?:\d|[_/\-]|$)/.test(normalized) || (code && problemNumber && code !== 's')) role = 'problem';
  else if (paperType && !problemNumber) role = 'paper';
  const scope = problemNumber ? 'problem' : paperType ? 'all-problems' : undefined;
  return { role, paperType, scope, problemNumber };
}

function displaySuffix(meta) {
  const paperType = meta.paperType ? `${paperTypeLabels[meta.paperType]} ` : '';
  if (meta.role === 'paper') return `${paperType}Paper`;
  if (meta.role === 'solution' && meta.scope === 'all-problems') return `${paperType}Solution`;
  return `${paperType}${roleLabels[meta.role]}${meta.problemNumber ? `, Problem ${meta.problemNumber}` : ''}`;
}

function toItem(candidate) {
  const { competition, relative, file, provider } = candidate;
  const year = yearFrom(relative);
  const meta = documentMeta(relative);
  return {
    title: `${competitionLabel(competition)} ${year} — ${displaySuffix(meta)}`,
    author: '', file, description: provider === 'olimpicos' ? 'Nguồn tham khảo khác' : '', source: provider === 'olimpicos' ? `Olimpicos archive: ${relative}` : `PhOxiv archive: ${relative}`,
    language: 'en', competition, year, role: meta.role,
    ...(meta.paperType ? { paperType: meta.paperType } : {}),
    ...(meta.scope ? { scope: meta.scope } : {}),
    ...(meta.problemNumber ? { problemNumber: meta.problemNumber } : {}),
  };
}

async function duplicateAudit() {
  const result = spawnSync('python', [path.join(root, 'tools', 'analyze-olympiad-duplicates.py')], { cwd: root, encoding: 'utf8' });
  if (result.status !== 0) throw new Error(`Olympiad duplicate audit failed: ${result.stderr || result.stdout}`);
  const jsonStart = result.stdout.indexOf('{');
  if (jsonStart < 0) throw new Error(`Olympiad duplicate audit produced no JSON: ${result.stdout}`);
  const audit = JSON.parse(result.stdout.slice(jsonStart));
  await writeFile(auditPath, `${JSON.stringify(audit, null, 2)}\n`);
  return new Set(audit.matches.flatMap((match) => match.suppressedFiles));
}

async function main() {
  const { sourceFiles, copied } = await copyPhoxiv();
  const candidates = [];
  for (const absolute of await walk(importedRoot)) {
    const relative = path.relative(importedRoot, absolute).split(path.sep).join('/');
    candidates.push({ absolute, relative, file: `materials/cdn.phoxiv.org/olympiads/${relative}`, competition: normalizeCompetition(relative.split('/')[0]), provider: 'phoxiv' });
  }
  const olympicosDirectories = (await readdir(path.join(physicsRoot, 'materials'), { withFileTypes: true })).filter((entry) => entry.isDirectory() && entry.name.endsWith('.olimpicos.net'));
  for (const directory of olympicosDirectories) {
    const absoluteRoot = path.join(physicsRoot, 'materials', directory.name);
    for (const absolute of await walk(absoluteRoot)) {
      const relative = path.relative(absoluteRoot, absolute).split(path.sep).join('/');
      candidates.push({ absolute, relative, file: `materials/${directory.name}/${relative}`, competition: normalizeCompetition(directory.name.split('.')[0]), provider: 'olimpicos' });
    }
  }
  const suppressedFiles = await duplicateAudit();
  const validCandidates = candidates.filter((candidate) => !excludedFiles.has(candidate.file) && !suppressedFiles.has(candidate.file));
  validCandidates.sort((left, right) => (left.provider === 'olimpicos' ? -1 : 1) - (right.provider === 'olimpicos' ? -1 : 1) || left.file.localeCompare(right.file));
  const chosen = new Map();
  for (const candidate of validCandidates) {
    const hash = await sha256(candidate.absolute);
    if (!chosen.has(hash)) chosen.set(hash, candidate);
  }
  const items = [...chosen.values()].map(toItem).sort((left, right) => left.competition.localeCompare(right.competition) || Number(right.year) - Number(left.year) || left.title.localeCompare(right.title));
  const temporary = `${manifestPath}.tmp`;
  await writeFile(temporary, `${JSON.stringify({ version: 2, items }, null, 2)}\n`);
  await rename(temporary, manifestPath);
  console.log(`Olympiad import: ${sourceFiles.length} PhOxiv PDFs (${copied} copied), ${validCandidates.length} eligible source PDFs, ${suppressedFiles.size} verified split variants hidden, ${items.length} public records after SHA-256 deduplication.`);
}

await main();
