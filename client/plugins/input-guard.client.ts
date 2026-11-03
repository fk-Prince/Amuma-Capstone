/**
 * Keeps form fields clean everywhere in the app, as people type or paste:
 *
 * - no emojis in any text field or text area;
 * - no stray code-like symbols ( < > { } [ ] \ | ^ ~ ` ) in text;
 * - names: letters, spaces, periods, apostrophes and hyphens only;
 * - emails: only characters an email address can have, no spaces;
 * - PH mobile numbers: digits only, at most 10 after +63
 *   (typing the 11-digit 09XX… form drops the leading 0 automatically);
 * - number fields never go past their max.
 *
 * Fields tell it what they are with data-input-kind (BaseInput sets this);
 * other fields are recognised by their type. Password fields are left alone,
 * and anything inside [data-allow-emoji] (the chat box) keeps its emojis.
 */

const EMOJI =
    /[\p{Extended_Pictographic}\u{1F1E6}-\u{1F1FF}\u{1F3FB}-\u{1F3FF}\u{E0020}-\u{E007F}‍︎️⃣]/gu;
const CODE_SYMBOLS = /[<>{}[\]\\|^~`]/g;
const NOT_NAME = /[^\p{L}\p{M} .'\-]/gu;
const NOT_EMAIL = /[^A-Za-z0-9@._%+\-]/g;

type Kind = "name" | "email" | "phone" | "text" | "textarea" | "raw";

const TEXT_TYPES = new Set(["text", "search", "email", "tel", "url", ""]);

function kindOf(el: HTMLInputElement | HTMLTextAreaElement): Kind | null {
    if (el.closest("[data-allow-emoji]")) return null;

    const declared = el.dataset.inputKind as Kind | undefined;
    if (declared) return declared === "raw" ? null : declared;

    if (el instanceof HTMLTextAreaElement) return "textarea";
    if (!TEXT_TYPES.has(el.type)) return null;

    // Password boxes with a "show" toggle turn into text fields; leave them be.
    const hints = `${el.autocomplete} ${el.name} ${el.id} ${el.placeholder}`;
    if (/pass(word|code)?/i.test(hints)) return null;
    const auto = el.autocomplete.toLowerCase();
    if (["given-name", "family-name", "additional-name", "name"].includes(auto)) return "name";
    if (el.type === "email" || auto === "email") return "email";
    if (el.type === "tel" || auto.startsWith("tel")) return "phone";
    return "text";
}

// Digits only; drops a repeated +63 / 63 / leading 0, keeps at most 10 digits
// (spaces and dashes the person types between groups are kept).
function cleanPhone(value: string) {
    const cleaned = value
        .replace(/[^\d\s-]/g, "")
        .replace(/^\s*(\+?63)[\s-]?(?=9)/, "")
        .replace(/^\s*0(?=9)/, "");

    let digits = 0;
    let out = "";
    for (const char of cleaned) {
        if (/\d/.test(char)) {
            if (digits === 10) break;
            digits++;
        }
        out += char;
    }
    return out.replace(/\s{2,}/g, " ").replace(/-{2,}/g, "-");
}

function clean(kind: Kind, value: string) {
    const noEmoji = value.replace(EMOJI, "");

    switch (kind) {
        case "name":
            return noEmoji.replace(NOT_NAME, "").replace(/\s{2,}/g, " ").replace(/^\s+/, "");
        case "email":
            return noEmoji.replace(NOT_EMAIL, "");
        case "phone":
            return cleanPhone(noEmoji);
        case "textarea":
        case "text":
            return noEmoji.replace(CODE_SYMBOLS, "");
        default:
            return noEmoji;
    }
}

function onInput(event: Event) {
    const el = event.target;
    if ((event as InputEvent).isComposing) return;

    // Number fields: never past their max.
    if (el instanceof HTMLInputElement && el.type === "number") {
        if (el.max !== "" && el.value !== "" && Number(el.value) > Number(el.max)) {
            el.value = el.max;
        }
        return;
    }

    if (!(el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement)) return;
    if (el.readOnly || el.disabled) return;

    const kind = kindOf(el);
    if (!kind) return;

    const before = el.value;
    const after = clean(kind, before);
    if (after === before) return;

    // Keep the caret where the person was typing.
    const caret = el.selectionStart ?? before.length;
    const removedBeforeCaret = before.length - after.length;
    el.value = after;

    try {
        const position = Math.max(0, caret - removedBeforeCaret);
        el.setSelectionRange(position, position);
    } catch {
        // Some input types (email) don't support selection; fine.
    }
}

export default defineNuxtPlugin(() => {
    // Capture phase, so the value is clean before v-model reads it.
    document.addEventListener("input", onInput, true);
});
