import { readFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const catalogRoot = path.join(root, "physics", "catalog");
const manifests = await Promise.all(["materials-pho", "magazines"].map(async (collection) => ({
  collection,
  items: JSON.parse(await readFile(path.join(catalogRoot, `${collection}.json`), "utf8")).items,
})));

const documents = manifests.flatMap(({ collection, items }) => items.map((item) => ({ collection, ...item })));
const missing = documents.filter((item) => !["en", "vi"].includes(item.language));
if (missing.length) throw new Error(`Missing explicit language: ${missing.map((item) => item.file).join(", ")}`);

const quantum = documents.filter((item) => item.file.startsWith("materials/quantum/"));
const kevinZhou = documents.filter((item) => /kevin zhou/i.test(item.author || ""));
for (const item of [...quantum, ...kevinZhou]) {
  if (item.language !== "en") throw new Error(`Expected English: ${item.file}`);
}

console.log(`Verified ${documents.length} explicit language assignments (${quantum.length} Quantum, ${kevinZhou.length} Kevin Zhou).`);
