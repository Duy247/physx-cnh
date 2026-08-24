import { execFileSync } from "node:child_process";
import { mkdir, readFile, rename, stat, writeFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const physicsRoot = path.join(root, "physics");
const collections = [
  { id: "books-pre-vpho", kind: "book", level: "pre-vpho", title: "Sách trước Vòng chọn VPhO" },
  { id: "books-vpho-vn", kind: "book", level: "vpho-vn", title: "Sách VPhO và Vòng chọn (VN)" },
  { id: "books-vpho-en", kind: "book", level: "vpho-en", title: "Sách VPhO và Vòng chọn (EN)" },
  { id: "materials-pho", kind: "material", level: "pho", title: "Tài liệu và handout" },
  { id: "paper-sol-pho", kind: "paper", level: "pho", title: "Đề thi và đáp án" },
  { id: "magazines", kind: "magazine", level: "all", title: "Tạp chí" },
  { id: "lessons", kind: "lesson", level: "all", title: "Nội dung ngày học" },
];

function slugify(value) { return value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/đ/gi, "d").toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "").slice(0, 72) || "tai-lieu"; }
function trackedFiles() {
  const output = execFileSync("git", ["-C", root, "ls-tree", "-r", "-l", "HEAD", "physics"], { encoding: "utf8", maxBuffer: 20 * 1024 * 1024 });
  const map = new Map();
  for (const line of output.split(/\r?\n/)) {
    const match = line.match(/^\d+\s+blob\s+[0-9a-f]+\s+(\d+|-)?\t(.+)$/);
    if (match) map.set(match[2].replace(/^physics\//, ""), Number(match[1]) || 0);
  }
  return map;
}

const tree = trackedFiles();
const usedSlugs = new Set();
const publishedFiles = new Set();
const documents = [];
for (const collection of collections) {
  const manifest = JSON.parse(await readFile(path.join(physicsRoot, "catalog", `${collection.id}.json`), "utf8"));
  for (const [index, item] of manifest.items.entries()) {
    let slug = slugify(item.title.replace(/\s+/g, " "));
    if (usedSlugs.has(slug)) slug = `${slug}-${collection.id}`;
    if (usedSlugs.has(slug)) slug = `${slug}-${index + 1}`;
    usedSlugs.add(slug);
    publishedFiles.add(item.file.toLowerCase());
    const extension = path.extname(item.file).toLowerCase();
    let bytes = tree.get(item.file) || 0;
    if (!bytes) { try { bytes = (await stat(path.join(physicsRoot, ...item.file.split("/")))).size; } catch {} }
    documents.push({
      id: `${collection.id}:${String(index + 1).padStart(3, "0")}`, slug,
      title: item.title.replace(/\r?\n/g, " — "), authors: item.author ? [item.author] : [],
      description: item.description || "", source: item.source || "", collectionId: collection.id,
      collectionTitle: collection.title, kind: collection.kind, level: collection.level,
      language: collection.id === "books-vpho-en" ? "en" : "vi", format: extension === ".pdf" ? "pdf" : "lesson",
      legacy: item.legacy === true, status: "published",
      file: { path: item.file, bytes, mimeType: extension === ".pdf" ? "application/pdf" : "text/html" },
      cover: extension === ".pdf" ? `/assets/v2/covers/${slug}.webp` : null,
      delivery: { provider: "hostinger" },
    });
  }
}

const allPdfs = [...tree.entries()].filter(([file]) => file.toLowerCase().endsWith(".pdf"));
const drafts = allPdfs.filter(([file]) => !publishedFiles.has(file.toLowerCase())).map(([file, bytes]) => ({
  id: `draft:${slugify(file.replace(/\.pdf$/i, ""))}`, title: path.basename(file, path.extname(file)).replace(/[-_]+/g, " "),
  file: { path: file, bytes, mimeType: "application/pdf" }, status: "draft", reason: "uncataloged",
}));
const inventory = { version: 1, generatedAt: new Date().toISOString(), counts: { totalPdf: allPdfs.length, publishedPdf: documents.filter((item) => item.format === "pdf").length, draftPdf: drafts.length }, drafts };
const snapshot = { version: 2, generatedAt: new Date().toISOString(), source: "Duy247/physx-cnh", counts: { published: documents.length, pdf: documents.filter((item) => item.format === "pdf").length, lesson: documents.filter((item) => item.format === "lesson").length }, collections, documents };
await mkdir(path.join(physicsRoot, "catalog"), { recursive: true });
async function writeAtomic(filename, value) {
  const destination = path.join(physicsRoot, "catalog", filename);
  const temporary = `${destination}.tmp`;
  await writeFile(temporary, `${JSON.stringify(value, null, 2)}\n`);
  await rename(temporary, destination);
}
await writeAtomic("inventory.json", inventory);
await writeAtomic("public-snapshot.json", snapshot);
console.log(`Inventory: ${allPdfs.length} PDFs = ${inventory.counts.publishedPdf} published + ${drafts.length} drafts; ${documents.length} public records.`);
