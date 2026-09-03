import { access, readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const physics = path.join(root, 'physics');
const olympiads = JSON.parse(await readFile(path.join(physics, 'catalog', 'olympiads.json'), 'utf8'));
const audit = JSON.parse(await readFile(path.join(physics, 'catalog', 'olympiad-duplicate-audit.json'), 'utf8'));
const snapshot = JSON.parse(await readFile(path.join(physics, 'catalog', 'public-snapshot.json'), 'utf8'));
const fail = (message) => { throw new Error(message); };
const item = (file) => olympiads.items.find((entry) => entry.file.endsWith(file));

for (const [file, expected] of Object.entries({
  'apho/2000_E.pdf': { role: 'paper', paperType: 'experimental', scope: 'all-problems', title: 'Experimental Paper' },
  'apho/2000_T.pdf': { role: 'paper', paperType: 'theoretical', scope: 'all-problems', title: 'Theoretical Paper' },
  'apho/2000_E_S.pdf': { role: 'solution', paperType: 'experimental', scope: 'all-problems', title: 'Experimental Solution' },
  'apho/2000_T_S.pdf': { role: 'solution', paperType: 'theoretical', scope: 'all-problems', title: 'Theoretical Solution' },
  'apho.olimpicos.net/pdf/APhO_2001_S1.pdf': { role: 'solution', problemNumber: 1, title: 'Solution, Problem 1' },
})) {
  const record = item(file);
  if (!record) fail(`Missing expected record: ${file}`);
  for (const [field, value] of Object.entries(expected)) {
    if (record[field] !== value && !(field === 'title' && record.title.includes(value))) fail(`${file}: expected ${field}=${value}, received ${record[field]}`);
  }
}

const apho2000 = audit.matches.filter((match) => match.competition === 'apho' && match.year === 2000);
if (apho2000.length !== 2 || apho2000.some((match) => match.suppressedFiles.length !== 3)) fail('APhO 2000 split Q/S variants were not fully suppressed.');
if (!audit.reviewCandidates.every((match) => match.similarity >= audit.policy.reviewSimilarityThreshold && match.similarity < audit.policy.automaticSimilarityThreshold)) fail('Duplicate audit review thresholds are inconsistent.');

const publicOlympiads = snapshot.documents.filter((document) => document.collectionId === 'olympiads');
if (publicOlympiads.some((document) => document.language !== 'en')) fail('Olympiad language metadata must not default to Vietnamese.');
if (publicOlympiads.some((document) => /\.pdf$/i.test(document.description || ''))) fail('Olympiad cards must not expose PDF filenames.');
if (publicOlympiads.filter((document) => document.source.startsWith('Olimpicos archive:')).some((document) => document.description !== 'Alternate source')) fail('Olimpicos records must be marked as alternate sources.');
for (const document of publicOlympiads) await access(path.join(root, 'assets', 'v2', 'covers', `${document.slug}.webp`));

console.log(`Olympiad catalog checks passed: ${publicOlympiads.length} public documents, ${audit.matches.length} verified duplicate sets, ${audit.reviewCandidates.length} review candidates.`);
