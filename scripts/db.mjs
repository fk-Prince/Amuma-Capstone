import { execFileSync } from "node:child_process";
import net from "node:net";
import path from "node:path";
import { config, readEnv, root, writeEnv } from "./env-file.mjs";

const server = path.join(root, "server");
const envFile = path.join(server, ".env");
const { remote, service, local } = config.database;
const args = process.argv.slice(2);

if (args.includes("--remote")) {
    console.log(`Using the remote database (${remote})`);
    writeEnv(envFile, { DB_CONNECTION: remote });
    process.exit(0);
}

const host = local.LOCAL_DB_HOST;
const port = Number(local.LOCAL_DB_PORT);
const database = local.LOCAL_DB_DATABASE;
const password = readEnv(envFile).LOCAL_DB_PASSWORD;

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function isUp() {
    return new Promise((resolve) => {
        const socket = net.connect(port, host);
        socket.once("connect", () => {
            socket.destroy();
            resolve(true);
        });
        socket.once("error", () => resolve(false));
    });
}

function startService() {
    try {
        execFileSync("net", ["start", service], { stdio: "ignore" });
        return;
    } catch {}

    console.log(`  starting ${service} needs administrator rights, approve the Windows prompt`);

    const command = `Set-Service ${service} -StartupType Manual; Start-Service ${service}`;

    try {
        execFileSync(
            "powershell",
            [
                "-NoProfile",
                "-Command",
                `Start-Process powershell -Verb RunAs -Wait -WindowStyle Hidden -ArgumentList '-NoProfile','-Command','${command}'`,
            ],
            { stdio: "ignore" },
        );
    } catch {}
}

async function ensureMysql() {
    if (await isUp()) return;

    console.log(`Starting MySQL (${service})`);

    if (process.platform === "win32" && service) startService();

    for (let i = 0; i < 60; i++) {
        if (await isUp()) return;
        await sleep(500);
    }

    throw new Error(
        `MySQL is not running on ${host}:${port}. Start it from MySQL Workbench (Server > Startup/Shutdown) and run this again.`,
    );
}

function query(sql) {
    const php = `
        $pdo = new PDO("mysql:host=" . getenv("DB_H") . ";port=" . getenv("DB_P"), getenv("DB_U"), getenv("DB_W"));
        $result = $pdo->query(getenv("DB_Q"));
        echo $result->columnCount() ? $result->fetchColumn() : "";
    `;

    return execFileSync("php", ["-r", php], {
        encoding: "utf8",
        stdio: ["ignore", "pipe", "pipe"],
        env: {
            ...process.env,
            DB_H: host,
            DB_P: String(port),
            DB_U: local.LOCAL_DB_USERNAME,
            DB_W: password ?? "",
            DB_Q: sql,
        },
    }).trim();
}

function artisan(...command) {
    execFileSync("php", ["artisan", ...command, "--database=local", "--force"], {
        cwd: server,
        stdio: "inherit",
    });
}

try {
    await ensureMysql();

    if (password === undefined) writeEnv(envFile, { LOCAL_DB_PASSWORD: "" });

    try {
        query(
            `CREATE DATABASE IF NOT EXISTS \`${database}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`,
        );
    } catch (error) {
        const reason = String(error.stderr || error.stdout || error.message);

        if (reason.includes("1045")) {
            throw new Error(
                "MySQL rejected the login. Put your MySQL root password (the one you use in Workbench) in LOCAL_DB_PASSWORD in server/.env and run this again.",
            );
        }

        throw new Error(reason.trim());
    }

    const tables = Number(
        query(
            `SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${database}'`,
        ),
    );

    console.log(`Using the local MySQL database "${database}"`);
    writeEnv(envFile, { DB_CONNECTION: "local", ...local });

    if (args.includes("--fresh")) artisan("migrate:fresh", "--seed");
    else if (tables === 0) artisan("migrate", "--seed");
    else artisan("migrate");
} catch (error) {
    console.error(`\n${error.message}`);
    process.exit(1);
}
