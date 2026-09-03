import { execFileSync } from "node:child_process";
import { mkdir, readFile, readdir, rename, stat, writeFile } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const physicsRoot = path.join(root, "physics");
let preparedMetadata = {};
try {
  preparedMetadata = JSON.parse(await readFile(path.join(physicsRoot, "catalog", "document-metadata.json"), "utf8")).documents || {};
} catch {}
const collections = [
  { id: "books-pre-vpho", kind: "book", level: "pre-vpho", title: "Sách trước Vòng chọn VPhO" },
  { id: "books-vpho-vn", kind: "book", level: "vpho-vn", title: "Sách VPhO và Vòng chọn (VN)" },
  { id: "books-vpho-en", kind: "book", level: "vpho-en", title: "Sách VPhO và Vòng chọn (EN)" },
  { id: "materials-pho", kind: "material", level: "pho", title: "Tài liệu và handout" },
  { id: "paper-sol-pho", kind: "paper", level: "pho", title: "Đề thi và đáp án" },
  { id: "olympiads", kind: "paper", level: "olympiads", title: "Kho đề Olympic Vật lý" },
  { id: "magazines", kind: "magazine", level: "all", title: "Tạp chí" },
];
const competitionLabels = {
  ipho: 'Olympic Vật lý Quốc tế (IPhO)', apho: 'Olympic Vật lý Châu Á (APhO)', eupho: 'Olympic Vật lý Châu Âu (EuPhO)',
  nbpho: 'Olympic Vật lý Bắc Âu–Baltic (NbPhO)', rmph: 'Kỳ thi Vật lý Bậc thầy Romania (RMPh)',
  vpho: 'Olympic Vật lý Việt Nam (VPhO)', inpho: 'Olympic Vật lý Ấn Độ (InPhO)', izho: 'Bộ sưu tập IZhO',
  hkpho: 'Olympic Vật lý Hồng Kông (HKPhO)', usapho: 'Olympic Vật lý Hoa Kỳ (USAPhO)', fma: 'Olympic Vật lý F=ma (Hoa Kỳ)',
  fyziklani: 'Cuộc thi Fyziklani', brawl: 'Physics Brawl Online', rupho: 'Olympic Vật lý Nga (RuPhO)',
  opho: 'Olympic Vật lý Mở (OPhO)', ppdrpho: 'Olympic Vật lý PPDRPhO',
  'olympic-30-4': 'Olympic 30–4', 'olympic-north': 'Olympic DH&ĐB Bắc Bộ', hsgso: 'Olympic Chuyên KHTN',
  'vietnam-student-olympiad': 'Olympic Vật lý Sinh viên Toàn quốc', 'vietnam-national': 'Kỳ thi HSG Quốc gia',
};

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
async function worktreePdfs(directory, prefix = "") {
  const files = [];
  for (const entry of await readdir(directory, { withFileTypes: true })) {
    const relative = `${prefix}${entry.name}`;
    const absolute = path.join(directory, entry.name);
    if (entry.isDirectory()) files.push(...await worktreePdfs(absolute, `${relative}/`));
    else if (entry.isFile() && entry.name.toLowerCase().endsWith('.pdf')) files.push([relative, (await stat(absolute)).size]);
  }
  return files;
}
for (const [file, bytes] of await worktreePdfs(physicsRoot)) tree.set(file, bytes);
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
    const prepared = preparedMetadata[item.file] || {};
    let bytes = tree.get(item.file) || 0;
    if (!bytes) { try { bytes = (await stat(path.join(physicsRoot, ...item.file.split("/")))).size; } catch {} }
    documents.push({
      id: `${collection.id}:${String(index + 1).padStart(3, "0")}`, slug,
      title: item.title.replace(/\r?\n/g, " — "), authors: item.author ? [item.author] : [],
      description: item.description || "", source: item.source || "", collectionId: collection.id,
      collectionTitle: collection.title, kind: collection.kind, level: collection.level,
      language: item.language || (collection.id === "books-vpho-en" || collection.id === "olympiads" ? "en" : "vi"), format: extension === ".pdf" ? "pdf" : "html",
      pages: Number.isInteger(prepared.pages) ? prepared.pages : null, addedAt: prepared.addedAt || null,
      legacy: item.legacy === true, status: "published",
      file: { path: item.file, bytes, mimeType: extension === ".pdf" ? "application/pdf" : "text/html" },
      cover: extension === ".pdf" ? `/assets/v2/covers/${slug}.webp` : null,
      delivery: { provider: "hostinger" },
      competition: item.competition || null,
      competitionLabel: item.competition ? (competitionLabels[item.competition] || item.competition.toUpperCase()) : null,
      year: item.year ?? null,
      role: item.role || null,
      problemNumber: item.problemNumber ?? null,
      paperType: item.paperType || null,
      scope: item.scope || null,
      coverVersion: item.competition ? "olympiad-metadata-v2" : null,
    });
  }
}

const allPdfs = [...tree.entries()].filter(([file]) => file.toLowerCase().endsWith(".pdf"));
const drafts = allPdfs.filter(([file]) => !publishedFiles.has(file.toLowerCase())).map(([file, bytes]) => ({
  id: `draft:${slugify(file.replace(/\.pdf$/i, ""))}`, title: path.basename(file, path.extname(file)).replace(/[-_]+/g, " "),
  file: { path: file, bytes, mimeType: "application/pdf" }, status: "draft", reason: "uncataloged",
}));
const inventory = { version: 1, generatedAt: new Date().toISOString(), counts: { totalPdf: allPdfs.length, publishedPdf: documents.filter((item) => item.format === "pdf").length, draftPdf: drafts.length }, drafts };
const snapshot = { version: 2, generatedAt: new Date().toISOString(), source: "Duy247/physx-cnh", counts: { published: documents.length, pdf: documents.filter((item) => item.format === "pdf").length }, collections, documents };
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
