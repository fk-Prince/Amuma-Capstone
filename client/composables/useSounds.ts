import { onMounted, ref, watch } from "vue";

type SoundKind = "notification" | "message";

const KEYS: Record<SoundKind, string> = {
    notification: "amuma_sound_notification",
    message: "amuma_sound_message",
};

const DEFAULT_VOLUME = 100;

// Saved as 0-100 in localStorage. Older saves used "on" / "off".
export function getSoundVolume(kind: SoundKind): number {
    if (!import.meta.client) return DEFAULT_VOLUME;

    try {
        const raw = localStorage.getItem(KEYS[kind]);

        if (raw === null || raw === "on") return DEFAULT_VOLUME;
        if (raw === "off") return 0;

        const value = Number(raw);

        return Number.isFinite(value)
            ? Math.min(100, Math.max(0, Math.round(value)))
            : DEFAULT_VOLUME;
    } catch {
        return DEFAULT_VOLUME;
    }
}

export function setSoundVolume(kind: SoundKind, volume: number): void {
    if (!import.meta.client) return;

    try {
        localStorage.setItem(
            KEYS[kind],
            String(Math.min(100, Math.max(0, Math.round(volume)))),
        );
    } catch {
        // storage unavailable (private mode), the choice just won't persist
    }
}

export function isSoundEnabled(kind: SoundKind): boolean {
    return getSoundVolume(kind) > 0;
}

let ctx: AudioContext | null = null;

function getContext(): AudioContext | null {
    if (!import.meta.client) return null;

    const Ctor: typeof AudioContext | undefined =
        window.AudioContext ?? (window as any).webkitAudioContext;

    if (!Ctor) return null;

    ctx ??= new Ctor();

    if (ctx.state === "suspended") ctx.resume().catch(() => {});

    return ctx;
}

let unlockBound = false;

export function bindSoundUnlock(): void {
    if (!import.meta.client || unlockBound) return;

    unlockBound = true;

    const unlock = () => {
        getContext();
        window.removeEventListener("pointerdown", unlock);
        window.removeEventListener("keydown", unlock);
    };

    window.addEventListener("pointerdown", unlock);
    window.addEventListener("keydown", unlock);
}

type ToneOptions = {
    freq: number;
    start?: number;
    duration?: number;
    volume?: number;
    slideTo?: number;
};

function tone(c: BaseAudioContext, o: ToneOptions, out: AudioNode) {
    const { freq, start = 0, duration = 0.3, volume = 0.16, slideTo } = o;

    const osc = c.createOscillator();
    const gain = c.createGain();
    const t0 = c.currentTime + start;

    osc.type = "sine";
    osc.frequency.setValueAtTime(freq, t0);

    if (slideTo) {
        osc.frequency.exponentialRampToValueAtTime(slideTo, t0 + duration);
    }

    gain.gain.setValueAtTime(0.0001, t0);
    gain.gain.exponentialRampToValueAtTime(volume, t0 + 0.012);
    gain.gain.exponentialRampToValueAtTime(0.0001, t0 + duration);

    osc.connect(gain);
    gain.connect(out);
    osc.start(t0);
    osc.stop(t0 + duration + 0.05);
}

export function scheduleNotification(c: BaseAudioContext, out: AudioNode = c.destination) {
    tone(c, { freq: 784, start: 0, duration: 0.55, volume: 0.15 }, out);
    tone(c, { freq: 1568, start: 0, duration: 0.3, volume: 0.04 }, out);
    tone(c, { freq: 1175, start: 0.14, duration: 0.75, volume: 0.15 }, out);
    tone(c, { freq: 2350, start: 0.14, duration: 0.4, volume: 0.035 }, out);
}

export function scheduleMessage(c: BaseAudioContext, out: AudioNode = c.destination) {
    tone(c, { freq: 1047, start: 0, duration: 0.28, volume: 0.3 }, out);
    tone(c, { freq: 2093, start: 0, duration: 0.16, volume: 0.08 }, out);
    tone(c, { freq: 1319, start: 0.17, duration: 0.4, volume: 0.3 }, out);
    tone(c, { freq: 2637, start: 0.17, duration: 0.22, volume: 0.08 }, out);
}

// Soft, short "sent" cue: a quick rising blip, quieter than the incoming chime.
export function scheduleSend(c: BaseAudioContext, out: AudioNode = c.destination) {
    tone(c, { freq: 523, slideTo: 784, start: 0, duration: 0.14, volume: 0.1 }, out);
    tone(c, { freq: 1568, start: 0.02, duration: 0.09, volume: 0.025 }, out);
}

const lastPlayed: Record<string, number> = {
    notification: 0,
    message: 0,
    send: 0,
};
const MIN_GAP_MS = 400;

function play(
    kind: SoundKind,
    schedule: (c: BaseAudioContext, out: AudioNode) => void,
    force: boolean,
    gapKey: string = kind,
) {
    if (!import.meta.client) return;
    const volume = getSoundVolume(kind);

    if (volume === 0) return;

    // several events arriving together should sound like one
    const now = Date.now();
    if (!force && now - (lastPlayed[gapKey] ?? 0) < MIN_GAP_MS) return;
    lastPlayed[gapKey] = now;

    const c = getContext();
    if (!c) return;

    try {
        // squared so the slider feels even to the ear
        const master = c.createGain();
        master.gain.value = (volume / 100) ** 2;
        master.connect(c.destination);

        schedule(c, master);
    } catch {
        // audio is a nice-to-have, never break the page for it
    }
}

// force: true skips the short gap between sounds (for the Test buttons). A volume of 0 is always silent.
export function playNotificationSound(options: { force?: boolean } = {}) {
    play("notification", scheduleNotification, !!options.force);
}

export function playMessageSound(options: { force?: boolean } = {}) {
    play("message", scheduleMessage, !!options.force);
}

// Plays when you hit Send. Follows the "message" volume in the profile
// settings, so setting it to 0 silences this too.
export function playSendSound(options: { force?: boolean } = {}) {
    play("message", scheduleSend, !!options.force, "send");
}

// For the profile settings volume sliders (0-100).
export function useSoundPrefs() {
    const notification = ref(DEFAULT_VOLUME);
    const message = ref(DEFAULT_VOLUME);

    onMounted(() => {
        notification.value = getSoundVolume("notification");
        message.value = getSoundVolume("message");
    });

    watch(notification, (value) => setSoundVolume("notification", value));
    watch(message, (value) => setSoundVolume("message", value));

    return { notification, message };
}
