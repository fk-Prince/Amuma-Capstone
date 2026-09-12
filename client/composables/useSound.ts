

let ctx: AudioContext | null = null;
let masterGain: GainNode | null = null;

function getContext(): { ctx: AudioContext; master: GainNode } | null {
    if (!process.client) return null;

    if (!ctx) {
        const AudioCtx =
            window.AudioContext || (window as any).webkitAudioContext;
        if (!AudioCtx) return null;

        ctx = new AudioCtx();
        masterGain = ctx.createGain();
        masterGain.gain.value = 1;
        masterGain.connect(ctx.destination);
    }

    if (ctx.state === "suspended") {
        ctx.resume().catch(() => {});
    }

    return { ctx, master: masterGain as GainNode };
}

/**
 * A single soft "note" — a sine fundamental gently blended with a quiet
 * triangle overtone, run through a lowpass filter to round off any
 * harshness, with a smooth attack/decay envelope. This is what makes the
 * result sound like a rounded chime rather than a flat beep.
 */
function note(
    frequency: number,
    duration: number,
    options: {
        volume?: number;
        delay?: number;
        attack?: number;
        cutoff?: number;
    } = {},
) {
    const audio = getContext();
    if (!audio) return;

    const { ctx, master } = audio;
    const { volume = 0.07, delay = 0, attack = 0.01, cutoff = 2200 } = options;
    const startAt = ctx.currentTime + delay;
    const endAt = startAt + duration;

    const filter = ctx.createBiquadFilter();
    filter.type = "lowpass";
    filter.frequency.value = cutoff;
    filter.Q.value = 0.4;

    const gain = ctx.createGain();
    gain.gain.setValueAtTime(0, startAt);
    gain.gain.linearRampToValueAtTime(volume, startAt + attack);
    gain.gain.exponentialRampToValueAtTime(0.0001, endAt);

    const fundamental = ctx.createOscillator();
    fundamental.type = "sine";
    fundamental.frequency.setValueAtTime(frequency, startAt);

    const overtone = ctx.createOscillator();
    overtone.type = "triangle";
    overtone.frequency.setValueAtTime(frequency * 2, startAt);

    const overtoneGain = ctx.createGain();
    overtoneGain.gain.value = 0.18;

    fundamental.connect(filter);
    overtone.connect(overtoneGain).connect(filter);
    filter.connect(gain).connect(master);

    fundamental.start(startAt);
    overtone.start(startAt);
    fundamental.stop(endAt + 0.03);
    overtone.stop(endAt + 0.03);
}

/** A very short, filtered noise burst — used for the UI "tick" instead of a tonal beep. */
function tick(options: { volume?: number; cutoff?: number } = {}) {
    const audio = getContext();
    if (!audio) return;

    const { ctx, master } = audio;
    const { volume = 0.045, cutoff = 3200 } = options;
    const duration = 0.045;
    const startAt = ctx.currentTime;

    const bufferSize = Math.ceil(ctx.sampleRate * duration);
    const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
    const data = buffer.getChannelData(0);
    for (let i = 0; i < bufferSize; i++) {
        // Slight decay curve baked into the noise itself, for a softer tail.
        data[i] = (Math.random() * 2 - 1) * (1 - i / bufferSize);
    }

    const source = ctx.createBufferSource();
    source.buffer = buffer;

    const filter = ctx.createBiquadFilter();
    filter.type = "bandpass";
    filter.frequency.value = cutoff;
    filter.Q.value = 0.7;

    const gain = ctx.createGain();
    gain.gain.setValueAtTime(volume, startAt);
    gain.gain.exponentialRampToValueAtTime(0.0001, startAt + duration);

    source.connect(filter).connect(gain).connect(master);
    source.start(startAt);
}

const SOUND_KEY = "amuma_sound_enabled";

export function isSoundEnabled(): boolean {
    if (!process.client) return true;
    return localStorage.getItem(SOUND_KEY) !== "off";
}

export function setSoundEnabled(enabled: boolean) {
    if (!process.client) return;
    localStorage.setItem(SOUND_KEY, enabled ? "on" : "off");
}

export function useSound() {
    function guarded(fn: () => void) {
        if (!isSoundEnabled()) return;
        try {
            fn();
        } catch {
            // Never let a sound effect throw and interrupt the real action.
        }
    }

    // Soft, filtered tick — for general button presses. Percussive, not
    // tonal, so it doesn't compete with success/error chimes.
    function playClick() {
        guarded(() => tick({ volume: 0.045, cutoff: 3200 }));
    }

    // Slightly duller/lower-pitched tick — for destructive (danger) actions.
    function playClickDanger() {
        guarded(() => tick({ volume: 0.05, cutoff: 1600 }));
    }

    // Two-note gentle rising chime — "something finished successfully".
    function playSuccess() {
        guarded(() => {
            note(523.25, 0.16, { volume: 0.065, cutoff: 2600 }); // C5
            note(783.99, 0.22, {
                volume: 0.06,
                delay: 0.1,
                cutoff: 2600,
            }); // G5
        });
    }

    // Single soft, low tone — "something went wrong". Muted rather than
    // alarming, since a harsh buzzer reads as cheap.
    function playError() {
        guarded(() => {
            note(311.13, 0.28, { volume: 0.06, attack: 0.02, cutoff: 900 }); // Eb4
        });
    }

    // Single mellow blip — informational toast / new notification.
    function playNotification() {
        guarded(() => {
            note(659.25, 0.14, { volume: 0.05, cutoff: 2400 }); // E5
        });
    }

    return {
        playClick,
        playClickDanger,
        playSuccess,
        playError,
        playNotification,
    };
}