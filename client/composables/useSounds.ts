import { onMounted, ref, watch } from "vue";

type SoundKind = "notification" | "message";

const KEYS: Record<SoundKind, string> = {
    notification: "amuma_sound_notification",
    message: "amuma_sound_message",
};

export function isSoundEnabled(kind: SoundKind): boolean {
    if (!import.meta.client) return false;

    try {
        return localStorage.getItem(KEYS[kind]) !== "off";
    } catch {
        return true;
    }
}

export function setSoundEnabled(kind: SoundKind, enabled: boolean): void {
    if (!import.meta.client) return;

    try {
        localStorage.setItem(KEYS[kind], enabled ? "on" : "off");
    } catch {
        // storage unavailable (private mode), the choice just won't persist
    }
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

function tone(c: BaseAudioContext, o: ToneOptions) {
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
    gain.connect(c.destination);
    osc.start(t0);
    osc.stop(t0 + duration + 0.05);
}

export function scheduleNotification(c: BaseAudioContext) {
    tone(c, { freq: 784, start: 0, duration: 0.55, volume: 0.15 });
    tone(c, { freq: 1568, start: 0, duration: 0.3, volume: 0.04 });
    tone(c, { freq: 1175, start: 0.14, duration: 0.75, volume: 0.15 });
    tone(c, { freq: 2350, start: 0.14, duration: 0.4, volume: 0.035 });
}

export function scheduleMessage(c: BaseAudioContext) {
    tone(c, { freq: 1047, start: 0, duration: 0.28, volume: 0.3 });
    tone(c, { freq: 2093, start: 0, duration: 0.16, volume: 0.08 });
    tone(c, { freq: 1319, start: 0.17, duration: 0.4, volume: 0.3 });
    tone(c, { freq: 2637, start: 0.17, duration: 0.22, volume: 0.08 });
}

// Soft, short "sent" cue: a quick rising blip, quieter than the incoming chime.
export function scheduleSend(c: BaseAudioContext) {
    tone(c, { freq: 523, slideTo: 784, start: 0, duration: 0.14, volume: 0.1 });
    tone(c, { freq: 1568, start: 0.02, duration: 0.09, volume: 0.025 });
}

const lastPlayed: Record<string, number> = {
    notification: 0,
    message: 0,
    send: 0,
};
const MIN_GAP_MS = 400;

function play(
    kind: SoundKind,
    schedule: (c: BaseAudioContext) => void,
    force: boolean,
    gapKey: string = kind,
) {
    if (!import.meta.client) return;
    if (!force && !isSoundEnabled(kind)) return;

    // several events arriving together should sound like one
    const now = Date.now();
    if (!force && now - (lastPlayed[gapKey] ?? 0) < MIN_GAP_MS) return;
    lastPlayed[gapKey] = now;

    const c = getContext();
    if (!c) return;

    try {
        schedule(c);
    } catch {
        // audio is a nice-to-have, never break the page for it
    }
}

// force: true plays even when the sound is switched off (for the Test buttons)
export function playNotificationSound(options: { force?: boolean } = {}) {
    play("notification", scheduleNotification, !!options.force);
}

export function playMessageSound(options: { force?: boolean } = {}) {
    play("message", scheduleMessage, !!options.force);
}

// Plays when you hit Send. Follows the existing "message" sound switch in the
// profile settings, so turning message sounds off silences this too.
export function playSendSound(options: { force?: boolean } = {}) {
    play("message", scheduleSend, !!options.force, "send");
}

// For the profile settings switches.
export function useSoundPrefs() {
    const notification = ref(true);
    const message = ref(true);

    onMounted(() => {
        notification.value = isSoundEnabled("notification");
        message.value = isSoundEnabled("message");
    });

    watch(notification, (value) => setSoundEnabled("notification", value));
    watch(message, (value) => setSoundEnabled("message", value));

    return { notification, message };
}