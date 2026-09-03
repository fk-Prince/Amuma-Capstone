<template>
    <nav class="z-30 mt-[2rem] w-full">
        <div class="w-full mx-auto h-[70px] flex items-end">
            <div
                ref="containerRef"
                class="flex w-full text-[15px] font-medium relative"
            >
                <button
                    v-for="(tab, index) in tabs"
                    :key="tab"
                    :ref="(el) => setTabRef(el, index)"
                    @click="setTab(index)"
                    class="flex-1 pb-4 transition text-center whitespace-nowrap"
                    :class="
                        activeIndex === index
                            ? 'text-primary'
                            : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-400'
                    "
                >
                    {{ tab }}
                </button>

                <span
                    class="absolute bottom-0 left-0 w-full h-[1px] bg-slate-200 dark:bg-white/15"
                />

                <span
                    class="absolute left-0 bottom-0 h-[2px] bg-primary rounded transition-all duration-300 ease-out"
                    :style="underlineStyle"
                />
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { ref, nextTick, onMounted, onBeforeUnmount } from "vue";

const emit = defineEmits<{
    (e: "change", index: number): void;
}>();

const tabs = ["Overview", "Location", "Services", "Reviews"];

const activeIndex = ref(0);

const tabRefs = ref<HTMLElement[]>([]);
const containerRef = ref<HTMLElement | null>(null);

const underlineStyle = ref({
    width: "0px",
    transform: "translateX(0px)",
});

const setTabRef = (el: any, index: number) => {
    if (el) {
        tabRefs.value[index] = el;
    }
};

const updateUnderline = () => {
    const el = tabRefs.value[activeIndex.value];
    const container = containerRef.value;

    if (!el || !container) return;

    const elRect = el.getBoundingClientRect();
    const containerRect = container.getBoundingClientRect();

    underlineStyle.value = {
        width: `${elRect.width}px`,
        transform: `translateX(${elRect.left - containerRect.left}px)`,
    };
};

const setTab = async (index: number) => {
    activeIndex.value = index;

    await nextTick();

    updateUnderline();

    emit("change", index);
};

const setActive = async (index: number) => {
    activeIndex.value = index;

    await nextTick();

    updateUnderline();
};

defineExpose({
    setActive,
});

const handleResize = () => {
    updateUnderline();
};

onMounted(async () => {
    await nextTick();

    updateUnderline();

    window.addEventListener("resize", handleResize);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", handleResize);
});
</script>
