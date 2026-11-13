import { nextTick, onBeforeUnmount, onMounted, ref, type Ref } from "vue";

const END_TOLERANCE_PX = 8;

export function useScrolledToEnd(el: Ref<HTMLElement | null>) {
    const reachedEnd = ref(false);
    let observer: ResizeObserver | null = null;

    function check() {
        const box = el.value;
        if (!box || reachedEnd.value) return;

        if (box.scrollTop + box.clientHeight >= box.scrollHeight - END_TOLERANCE_PX) {
            reachedEnd.value = true;
            observer?.disconnect();
        }
    }

    onMounted(async () => {
        await nextTick();
        const box = el.value;
        if (!box) return;

        box.addEventListener("scroll", check, { passive: true });

        observer = new ResizeObserver(check);
        observer.observe(box);
        Array.from(box.children).forEach((child) => observer?.observe(child));

        check();
    });

    onBeforeUnmount(() => {
        el.value?.removeEventListener("scroll", check);
        observer?.disconnect();
    });

    return { reachedEnd, check };
}
