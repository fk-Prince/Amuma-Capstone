import { readdirSync, readFileSync, statSync, writeFileSync } from "node:fs";
import { join } from "node:path";

const DRY = process.argv.includes("--dry");

// Dashboard areas only - landing, auth, booking and marketing pages keep their own look.
const TARGETS = [
    "pages/app",
    "pages/portal",
    "pages/profile.vue",
    "layouts",
    "components/ui",
    "components/billing",
    "components/messaging",
    "components/portal",
    "components/sections/app",
    "components/sections/owner",
    "components/sections/portal",
    "components/sections/profile",
    "components/sections/subscription",
    "components/sections/DashboardHeader.vue",
    "components/sections/DashboardSidebar.vue",
    "components/sections/PortalHeader.vue",
    "components/sections/PortalSidebar.vue",
    "components/sections/DynamicSidebar.vue",
    "components/sections/BranchSelectModal.vue",
    "components/sections/UnauthorizedSection.vue",
];

const PROPS = "bg|text|border|ring|from|to|via|fill|stroke|shadow|outline|divide|decoration|accent|caret|placeholder";
const shade = (n) => (n === "950" ? "900" : n); // primary/accent scales stop at 900

const HEX = {
    "16302E": "ink",
    "6B8A87": "ink-muted",
    "9AB3AF": "ink-subtle",
    "E4EFED": "ink-line",
    "DDECEC": "ink-line-alt",
    "EDF4F3": "ink-soft",
    "F1F7F6": "ink-soft-alt",
    "F0F5F4": "ink-soft-alt",
    "F7FAF9": "ink-wash",
    "F8FBFA": "ink-wash",
    "FAFCFB": "ink-faint",
    "FAFDFC": "ink-faint",
    "0E7C7B": "accent",
    "0B6564": "accent-600",
    "EAF4F2": "accent-50",
    "3182ED": "primary",
    "1E68D1": "primary-600",
    "EEF3FB": "page",
};

const RULES = [
    // blue / sky  ->  primary
    [new RegExp(`(?<![\\w-])(${PROPS})-(?:blue|sky)-(\\d{2,3})(?![\\w-])`, "g"), (_, p, n) => `${p}-primary-${shade(n)}`],
    // teal  ->  accent
    [new RegExp(`(?<![\\w-])(${PROPS})-teal-(\\d{2,3})(?![\\w-])`, "g"), (_, p, n) => `${p}-accent-${shade(n)}`],
    // brand / teal-gray hex classes
    [
        new RegExp(`(?<![\\w-])(${PROPS})-\\[#([0-9a-fA-F]{6})\\]`, "g"),
        (m, p, hex) => (HEX[hex.toUpperCase()] ? `${p}-${HEX[hex.toUpperCase()]}` : m),
    ],
    // hardcoded blue glow behind the AMUMA wordmark
    [/rgb\(49_130_237_\/_35%\)/g, () => "rgb(var(--primary-500)_/_35%)"],
];

const files = [];
const walk = (path) => {
    let st;
    try {
        st = statSync(path);
    } catch {
        return;
    }
    if (st.isDirectory()) {
        for (const entry of readdirSync(path)) walk(join(path, entry));
    } else if (path.endsWith(".vue") || path.endsWith(".ts")) {
        files.push(path);
    }
};
TARGETS.forEach(walk);

let changedFiles = 0;
let changes = 0;

for (const file of files) {
    const before = readFileSync(file, "utf8");
    let after = before;
    let count = 0;

    for (const [regex, replace] of RULES) {
        after = after.replace(regex, (...args) => {
            const out = replace(...args);
            if (out !== args[0]) count++;
            return out;
        });
    }

    if (after !== before) {
        changedFiles++;
        changes += count;
        if (!DRY) writeFileSync(file, after, "utf8");
        console.log(`${String(count).padStart(4)}  ${file}`);
    }
}

console.log(`\n${DRY ? "[dry run] would change" : "Changed"} ${changes} classes in ${changedFiles} files.`);