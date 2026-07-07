<template>
    <nav class="sticky top-0 z-30 bg-white w-full mt-10">
        <div
            class="w-full md:w-[90%] mx-auto h-[70px] flex justify-center items-center px-3 md:px-6"
        >
            <div
                ref="containerRef"
                class="flex gap-4 md:gap-6 text-sm font-medium relative"
            >
                <button
                    v-for="(tab, index) in tabs"
                    :key="tab"
                    @click="setTab(index)"
                    class="pb-3 px-3 md:px-6 transition whitespace-nowrap"
                    :class="
                        activeIndex === index
                            ? 'text-primary'
                            : 'text-secondary opacity-70 hover:opacity-100'
                    "
                    :ref="(el) => setTabRef(el, index)"
                >
                    {{ tab }}
                </button>

                <span
                    class="absolute bottom-0 left-0 w-full h-[2px] bg-slate-200 rounded"
                />

                <span
                    class="absolute bottom-0 h-[2px] bg-primary rounded transition-all duration-300 ease-out"
                    :style="underlineStyle"
                />
            </div>
        </div>
    </nav>
</template>

<script setup lang="ts">
import { ref, nextTick, onMounted } from "vue";

const emit = defineEmits<{
    (e: "change", index: number): void;
}>();

const tabs = ["Overview", "Services", "Photos", "Reviews", "Location"];

const activeIndex = ref(0);

const tabRefs = ref<HTMLElement[]>([]);
const containerRef = ref<HTMLElement | null>(null);

const underlineStyle = ref({
    width: "0px",
    transform: "translateX(0px)",
});

const setTabRef = (el: any, index: number) => {
    if (el) tabRefs.value[index] = el;
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

onMounted(() => {
    nextTick(updateUnderline);
    window.addEventListener("resize", updateUnderline);
});
</script>
