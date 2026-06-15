<template>
    <ClientOnly>
        <div class="relative" ref="dropdownRef">
            <slot name="trigger" :toggle="toggle" :open="open" />

            <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="open"
                    class="absolute z-50 mt-2 border border-gray-100 rounded-2xl shadow-xl overflow-hidden py-1"
                    :class="[alignClass, widthClass]"
                >
                    <slot :close="close" />
                </div>
            </Transition>
        </div>
    </ClientOnly>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";

const props = withDefaults(
    defineProps<{
        align?: "left" | "right";
        width?: string;
    }>(),
    {
        align: "left",
        width: "w-48",
    },
);

const open = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const toggle = () => (open.value = !open.value);
const close = () => (open.value = false);

const alignClass = computed(() =>
    props.align === "right" ? "right-0" : "left-0",
);
const widthClass = computed(() => props.width);

const handleClickOutside = (e: MouseEvent) => {
    if (!dropdownRef.value?.contains(e.target as Node)) close();
};
</script>
