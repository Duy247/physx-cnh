import { readFile, rename, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const manifestPath = path.join(root, 'physics', 'catalog', 'paper-sol-pho.json');
const labels = [
  [/vpho/i, 'vpho'], [/rupho/i, 'rupho'], [/inpho/i, 'inpho'], [/opho/i, 'opho'], [/ppdr/i, 'ppdrpho'],
  [/izho/i, 'izho'], [/hkpho/i, 'hkpho'], [/usapho/i, 'usapho'], [/f=ma/i, 'fma'], [/fyziklani/i, 'fyziklani'], [/physics brawl/i, 'brawl'],
  [/30-4/i, 'olympic-30-4'], [/dh&đb|bắc bộ/i, 'olympic-north'], [/khtn/i, 'hsgso'], [/sinh viên/i, 'vietnam-student-olympiad'], [/hsg qg/i, 'vietnam-national'],
];
const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
for (const item of manifest.items) {
  const competition = labels.find(([pattern]) => pattern.test(item.title))?.[1] || 'paper-collection';
  const years = [...item.title.matchAll(/(?<!\d)(?:19|20)\d{2}(?!\d)/g)].map((match) => Number(match[0]));
  item.competition = competition;
  item.year = years.length === 1 ? years[0] : 'Collection';
  item.role = /đáp án|lời giải/i.test(item.title) ? 'solution' : 'document';
}
const temporary = `${manifestPath}.tmp`;
await writeFile(temporary, `${JSON.stringify(manifest, null, 2)}\n`);
await rename(temporary, manifestPath);
console.log(`Backfilled ${manifest.items.length} curated paper records.`);
