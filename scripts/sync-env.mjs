import { execFileSync } from "node:child_process";
import { X509Certificate } from "node:crypto";
import dgram from "node:dgram";
import { copyFileSync, existsSync, readFileSync, writeFileSync } from "node:fs";
import os from "node:os";
import path from "node:path";
import { fileURLToPath } from "node:url";

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const config = JSON.parse(readFileSync(path.join(root, "env.config.json"), "utf8"));

function routeIp() {
    return new Promise((resolve) => {
        const socket = dgram.createSocket("udp4");
        const done = (address) => {
            socket.close();
            resolve(address);
        };

        socket.on("error", () => done(null));
        socket.connect(53, "8.8.8.8", (error) =>
            done(error ? null : socket.address().address),
        );
    });
}

function interfaceIp() {
    return Object.values(os.networkInterfaces())
        .flat()
        .find(
            (net) =>
                net?.family === "IPv4" &&
                !net.internal &&
                !net.address.startsWith("169.254."),
        )?.address;
}

async function detectIp() {
    const manual = process.argv[2] ?? (config.ip !== "auto" ? config.ip : null);

    return manual ?? (await routeIp()) ?? interfaceIp() ?? "127.0.0.1";
}

function writeEnv(file, values) {
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

function ensureCert(ip) {
    const dir = path.join(root, "client", "certs");
    const cert = path.join(dir, "dev.pem");
    const key = path.join(dir, "dev-key.pem");

    if (
        existsSync(cert) &&
        existsSync(key) &&
        new X509Certificate(readFileSync(cert)).checkIP(ip)
    ) {
        return;
    }

    try {
        execFileSync(
            "mkcert",
            ["-cert-file", cert, "-key-file", key, ip, "localhost", "127.0.0.1", "::1"],
            { stdio: "ignore" },
        );
        console.log(`  generated HTTPS certificate for ${ip}`);
    } catch {
        if (!existsSync(cert) || !existsSync(key)) {
            copyFileSync(path.join(dir, "192.168.1.2+3.pem"), cert);
            copyFileSync(path.join(dir, "192.168.1.2+3-key.pem"), key);
        }
        console.warn(`  mkcert not found, the browser will warn that the certificate does not match ${ip}`);
    }
}

const ip = await detectIp();

console.log(`Using IP ${ip}`);

for (const [folder, values] of Object.entries(config.env)) {
    const resolved = Object.fromEntries(
        Object.entries(values).map(([key, value]) => [key, value.replaceAll("{ip}", ip)]),
    );

    writeEnv(path.join(root, folder, ".env"), resolved);
}

ensureCert(ip);
