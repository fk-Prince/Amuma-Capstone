import { computed, onMounted } from "vue";

type ListView = "table" | "card";

const STORAGE_KEY = "amuma_list_view";

// The table / card choice for lists. It starts on cards on a phone and on the
// table elsewhere, and once someone picks a view it is kept for every list.
export const useListView = () => {
    const chosen = useState<ListView | null>("list_view_choice", () => null);
    const fallback = useState<ListView>("list_view_default", () => "table");

    onMounted(() => {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);

            if (stored === "table" || stored === "card") {
                chosen.value = stored;
            }
        } catch {
            // Storage can be blocked (private mode); the choice then lasts until reload.
        }

        if (window.matchMedia("(max-width: 767px)").matches) {
            fallback.value = "card";
        }
    });

    const view = computed<ListView>(() => chosen.value ?? fallback.value);

    const setView = (value: ListView) => {
        chosen.value = value;

        try {
            localStorage.setItem(STORAGE_KEY, value);
        } catch {
            // See above.
        }
    };

    return { view, setView };
};
