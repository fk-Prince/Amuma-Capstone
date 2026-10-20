import { onMounted } from "vue";
import "~/assets/css/dashboard-intro.css";

const INTRO_KEY = "amuma_nav_intro_played";


export function useDashboardIntro() {
    const intro = useState<boolean>("dashboard_intro", () => false);

    onMounted(() => {
        // Another piece of the shell already started it, so just join in.
        if (intro.value) return;

        try {
            if (localStorage.getItem(INTRO_KEY)) return;

            localStorage.setItem(INTRO_KEY, "1");
            intro.value = true;

            // Once the animations have finished, stop tagging new components.
            setTimeout(() => {
                intro.value = false;
            }, 2500);
        } catch {
            // Storage can be blocked (private mode); skip the intro then.
        }
    });

    return intro;
}