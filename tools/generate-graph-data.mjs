import { mkdir, readFile, rename, writeFile } from "node:fs/promises";
import path from "node:path";
import vm from "node:vm";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const roadmapRoot = path.join(root, "roadmap");
const parseJson = async (filename) => JSON.parse(await readFile(path.join(roadmapRoot, filename), "utf8"));
const parseObject = async (filename) => vm.runInNewContext(`(${await readFile(path.join(roadmapRoot, filename), "utf8")})`, Object.create(null), { timeout: 1000 });
const output = {
  roadmap: { model: await parseJson("model.txt"), descriptions: await parseObject("node_data.txt") },
  research: { model: await parseJson("article.txt"), descriptions: await parseObject("article_des.txt") },
};
const dataRoot = path.join(root, "data");
await mkdir(dataRoot, { recursive: true });
const temporary = path.join(dataRoot, "graphs.json.tmp");
await writeFile(temporary, `${JSON.stringify(output)}\n`);
await rename(temporary, path.join(dataRoot, "graphs.json"));
console.log("Generated data/graphs.json");
