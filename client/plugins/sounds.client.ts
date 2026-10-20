import { bindSoundUnlock } from "~/composables/useSounds";

// Unlocks browser audio on the first click/tap/key press, so the first alert
// after that can play.
export default defineNuxtPlugin(() => {
    bindSoundUnlock();
});