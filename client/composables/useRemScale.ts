import { onMounted, ref } from "vue";
import { useEventListener } from "@vueuse/core";
import { remScale } from "~/utils/rem";

export function useRemScale() {
    const scale = ref(1);
    const update = () => {
        scale.value = remScale();
    };

    onMounted(update);
    useEventListener("resize", update);

    return scale;
}
