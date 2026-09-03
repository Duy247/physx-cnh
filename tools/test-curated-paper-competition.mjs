import { readFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const snapshot = JSON.parse(await readFile(path.join(root, 'physics', 'catalog', 'public-snapshot.json'), 'utf8'));
const expected = {
  'IZhO Vật lý và Đáp án — 2010-2023': ['izho', 'Olympic Vật lý Quốc tế Zhautykov (IZhO)'],
  'HKPhO và Đáp án — 2004-2016': ['hkpho', 'Olympic Vật lý Hồng Kông (HKPhO)'],
  'USAPhO và Đáp án — 2014-2024': ['usapho', 'Olympic Vật lý Hoa Kỳ (USAPhO)'],
  'F=ma và Đáp án — 2011-2019': ['fma', 'Olympic Vật lý F=ma (Hoa Kỳ)'],
  'F=ma và Đáp án — 2020-2024': ['fma', 'Olympic Vật lý F=ma (Hoa Kỳ)'],
  'Fyziklani — 2018-2023': ['fyziklani', 'Cuộc thi Fyziklani'],
  'Physics Brawl Online — 2012-2023': ['brawl', 'Physics Brawl Online'],
};
for (const [title, [competition, label]] of Object.entries(expected)) {
  const document = snapshot.documents.find((item) => item.collectionId === 'paper-sol-pho' && item.title === title);
  if (!document || document.competition !== competition || document.competitionLabel !== label || document.year !== 'Collection') throw new Error(`Incorrect curated collection metadata: ${title}`);
}
for (const [competition, label] of Object.entries({ ipho: 'Olympic Vật lý Quốc tế (IPhO)', apho: 'Olympic Vật lý Châu Á (APhO)', eupho: 'Olympic Vật lý Châu Âu (EuPhO)' })) {
  if (!snapshot.documents.some((document) => document.competition === competition && document.competitionLabel === label)) throw new Error(`Missing Vietnamese competition label for ${competition}`);
}
console.log('Curated paper competition checks passed.');
