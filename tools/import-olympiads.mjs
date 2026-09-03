import { createHash } from 'node:crypto';
import { mkdir, readdir, stat, copyFile, writeFile, rename } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const physicsRoot = path.join(root, 'physics');
const phoxivRoot = process.env.PHOXIV_OLYMPIADS_ROOT || 'C:/Users/DuyVan/phoxiv-pdf-downloader/downloads/cdn.phoxiv.org/olympiads';
const importedRoot = path.join(physicsRoot, 'materials', 'cdn.phoxiv.org', 'olympiads');
const manifestPath = path.join(physicsRoot, 'catalog', 'olympiads.json');
const excludedFiles = new Set(['materials/ipho.olimpicos.net/pdf/IPhO_2022_S5.pdf']); // Zero-page PDF; retain on disk until a valid replacement is available.

const labels = {
  ipho: 'International Physics Olympiad (IPhO)', apho: 'Asian Physics Olympiad (APhO)', eupho: 'European Physics Olympiad (EuPhO)',
  nbpho: 'Nordic-Baltic Physics Olympiad (NbPhO)', rmph: 'Romanian Master of Physics (RMPh)',
  aupho: 'Australian Physics Olympiad (AuPhO)', 'bpho-r1': 'British Physics Olympiad — Round 1', 'bpho-r2': 'British Physics Olympiad — Round 2',
  'cpho-f': 'Chinese Physics Olympiad — Final', eotvos: 'Eötvös Competition', fma: 'F=ma Contest', gpho: 'German Physics Olympiad (GPhO)',
  inpho: 'Indian Physics Olympiad (InPhO)', izho: 'Izho Physics Olympiad', kphc: 'Korea Physics High School Competition',
  sjpo: 'Singapore Junior Physics Olympiad (SJPO)', spho: 'Singapore Physics Olympiad (SPhO)', spot: 'Singapore Physics Olympiad Training',
  upho: 'Ukraine Physics Olympiad (UPhO)', usapho: 'USA Physics Olympiad (USAPhO)', usatst: 'USA Physics Team Selection Test',
  wopho: 'World Open Physics Olympiad (WOPhO)', twpho: 'Taiwan Physics Olympiad', twtst: 'Taiwan Team Selection Test',
};

const roleLabels = { problem: 'Problem', solution: 'Solution', marking: 'Marking scheme', answer: 'Answer sheet', report: 'Report', reference: 'Reference', document: 'Document' };
const competitionLabel = (id) => labels[id] || id.toUpperCase();

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
  const name = path.basename(relative, '.pdf').toLowerCase();
  const problem = name.match(/(?:^|[_-])(?:q|t|e|p)(\d+)(?:[_-]|$)/)?.[1];
  let role = 'document';
  if (/(?:solution|[_-]s(?:[_-]|$)|_sol(?:[_-]|$))/.test(name)) role = 'solution';
  else if (/(?:mark|[_-]m(?:[_-]|$))/.test(name)) role = 'marking';
  else if (/(?:answer|[_-]a(?:[_-]|$))/.test(name)) role = 'answer';
  else if (/(?:report|proc|result)/.test(name)) role = 'report';
  else if (/(?:min|reference|handbook)/.test(name)) role = 'reference';
  else if (/(?:^|[_-])(?:q|t|e|p)\d+/.test(name)) role = 'problem';
  return { role, problemNumber: problem ? Number(problem) : undefined };
}

function toItem(candidate) {
  const { competition, relative, file, provider } = candidate;
  const year = yearFrom(relative);
  const meta = documentMeta(relative);
  const suffix = `${roleLabels[meta.role]}${meta.problemNumber ? ` ${meta.problemNumber}` : ''}`;
  return {
    title: `${competitionLabel(competition)} ${year} — ${suffix}`,
    author: '', file, description: path.basename(relative), source: provider === 'olimpicos' ? `Olimpicos archive: ${relative}` : `PhOxiv archive: ${relative}`,
    competition, year, role: meta.role, ...(meta.problemNumber ? { problemNumber: meta.problemNumber } : {}),
  };
}

async function main() {
  const { sourceFiles, copied } = await copyPhoxiv();
  const candidates = [];
  for (const absolute of await walk(importedRoot)) {
    const relative = path.relative(importedRoot, absolute).split(path.sep).join('/');
    candidates.push({ absolute, relative, file: `materials/cdn.phoxiv.org/olympiads/${relative}`, competition: relative.split('/')[0], provider: 'phoxiv' });
  }
  const olympicosDirectories = (await readdir(path.join(physicsRoot, 'materials'), { withFileTypes: true })).filter((entry) => entry.isDirectory() && entry.name.endsWith('.olimpicos.net'));
  for (const directory of olympicosDirectories) {
    const absoluteRoot = path.join(physicsRoot, 'materials', directory.name);
    for (const absolute of await walk(absoluteRoot)) {
      const relative = path.relative(absoluteRoot, absolute).split(path.sep).join('/');
      candidates.push({ absolute, relative, file: `materials/${directory.name}/${relative}`, competition: directory.name.split('.')[0], provider: 'olimpicos' });
    }
  }
  const validCandidates = candidates.filter((candidate) => !excludedFiles.has(candidate.file));
  validCandidates.sort((left, right) => (left.provider === 'olimpicos' ? -1 : 1) - (right.provider === 'olimpicos' ? -1 : 1) || left.file.localeCompare(right.file));
  const chosen = new Map();
  for (const candidate of validCandidates) {
    const hash = await sha256(candidate.absolute);
    if (!chosen.has(hash)) chosen.set(hash, candidate);
  }
  const items = [...chosen.values()].map(toItem).sort((left, right) => left.competition.localeCompare(right.competition) || Number(right.year) - Number(left.year) || left.title.localeCompare(right.title));
  const temporary = `${manifestPath}.tmp`;
  await writeFile(temporary, `${JSON.stringify({ version: 1, items }, null, 2)}\n`);
  await rename(temporary, manifestPath);
  console.log(`Olympiad import: ${sourceFiles.length} PhOxiv PDFs (${copied} copied), ${validCandidates.length} valid source PDFs, ${items.length} public records after SHA-256 deduplication.`);
}

await main();
