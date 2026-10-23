const EMOJI_PATTERN =
    /[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{2300}-\u{23FF}\u{2B00}-\u{2BFF}\u{FE0F}\u{20E3}\u{200D}]/u;

export const EMOJI_MESSAGE = "Emoji are not allowed";

export function hasEmoji(value: unknown): boolean {
    return typeof value === "string" && EMOJI_PATTERN.test(value);
}
