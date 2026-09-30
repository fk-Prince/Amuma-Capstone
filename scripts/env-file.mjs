import { existsSync, readFileSync, writeFileSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

export const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
export const config = JSON.parse(readFileSync(path.join(root, "env.config.json"), "utf8"));

export function readEnv(file) {
    if (!existsSync(file)) return {};

    return Object.fromEntries(
        readFileSync(file, "utf8")
            .split(/\r?\n/)
            .map((line) => line.match(/^\s*([\w.]+)\s*=\s*(.*?)\s*$/))
            .filter(Boolean)
            .map(([, key, value]) => [key, value.replace(/^(["'])(.*)\1$/, "$2")]),
    );
}

export function writeEnv(file, values) {
    if (!existsSync(file)) {
        console.warn(`  skipped ${path.relative(root, file)} (file not found)`);
        return;
    }

    const text = readFileSync(file, "utf8");
    const eol = text.includes("\r\n") ? "\r\n" : "\n";
    const lines = text.split(/\r?\n/);

    for (const [key, value] of Object.entries(values)) {
        const line = `${key}=${value}`;
        const index = lines.findIndex((l) => new RegExp(`^\\s*${key}\\s*=`).test(l));

        if (index !== -1) lines[index] = line;
        else lines.splice(lines.at(-1) === "" ? -1 : lines.length, 0, line);
    }

    writeFileSync(file, lines.join(eol));
    console.log(`  updated ${path.relative(root, file)}`);
}
