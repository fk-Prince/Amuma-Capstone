<script setup lang="ts">
import { computed } from "vue";
import LocationMap from "~/components/ui/LocationMap.vue";

const props = defineProps<{
    open: boolean;
    lat: number;
    lng: number;
    address?: string | null;
    title?: string;
}>();

const emit = defineEmits<{
    (e: "close"): void;
}>();

const googleMapsUrl = computed(
    () =>
        `https://www.google.com/maps/search/?api=1&query=${props.lat},${props.lng}`,
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-900/50 p-4"
                @click.self="emit('close')"
            >
                <div
                    class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                >
                    <div
                        class="flex items-start justify-between gap-4 border-b border-[#E4EFED] px-5 py-4 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-[#16302E] dark:text-white">
                                {{ title ?? "Service Location" }}
                            </h3>

                            <p
                                v-if="address"
                                class="mt-0.5 text-xs text-slate-500 break-words dark:text-gray-400"
                            >
                                {{ address }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                            aria-label="Close"
                            @click="emit('close')"
                        >
                            <svg
                                class="h-5 w-5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            >
                                <path d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="p-5">
                        <ClientOnly>
                            <LocationMap
                                :lat="lat"
                                :lng="lng"
                                :label="address ?? undefined"
                                height-class="h-[340px]"
                            />

                            <template #fallback>
                                <div
                                    class="h-[340px] w-full animate-pulse rounded-xl border border-[#E4EFED] bg-slate-50 dark:bg-white/5 dark:border-white/10"
                                />
                            </template>
                        </ClientOnly>

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <span class="text-[11px] text-slate-400 dark:text-gray-500">
                                {{ lat.toFixed(5) }}, {{ lng.toFixed(5) }}
                            </span>

                            <a
                                :href="googleMapsUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-[#0E7C7B] px-3 py-2 text-xs font-medium text-white transition hover:bg-[#0b6564]"
                            >
                                Open in Google Maps

                                <svg
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M8 4H4v12h12v-4" />
                                    <path d="M12 3h5v5M17 3l-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
