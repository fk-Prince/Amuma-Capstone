import { execFileSync } from "node:child_process";
import { X509Certificate } from "node:crypto";
import dgram from "node:dgram";
import { copyFileSync, existsSync, readFileSync } from "node:fs";
import os from "node:os";
import path from "node:path";
import { config, root, writeEnv } from "./env-file.mjs";

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
