<template>
    <div
        class="rounded-2xl bg-white border border-muted-light/70 shadow-sm p-5 font-sans w-[320px] dark:bg-secondary dark:border-white/10"
    >
        <h3 class="text-secondary text-[15px] font-medium mb-4 dark:text-white">{{ title }}</h3>

        <div class="flex" style="height: 220px">
            <!-- y-axis labels -->
            <div
                class="flex flex-col justify-between text-right pr-3 text-[11px] text-muted shrink-0 dark:text-gray-400"
                style="width: 28px"
            >
                <span v-for="tick in yTicks" :key="tick">{{ tick }}</span>
            </div>

            <!-- plot area -->
            <div class="relative flex-1">
                <div
                    class="absolute inset-0 flex flex-col justify-between pointer-events-none"
                >
                    <div
                        v-for="(t, i) in yTicks"
                        :key="i"
                        class="border-t border-muted-light dark:border-white/10"
                    />
                </div>

                <div class="relative h-full flex items-end justify-around">
                    <div
                        v-for="room in rooms"
                        :key="room.label"
                        class="relative flex items-end gap-1.5 h-full px-3 cursor-pointer"
                        @mouseenter="hovered = room.label"
                        @mouseleave="hovered = null"
                    >
                        <div
                            v-if="hovered === room.label"
                            class="absolute -inset-x-2 top-0 bottom-0 bg-primary-100/50 rounded-t-md -z-10 dark:bg-primary-500/15"
                        />

                        <div
                            class="w-6 rounded-t-md transition-all duration-150 bg-primary"
                            :style="{
                                height: `${(room.occupied / topTick) * 100}%`,
                            }"
                        />
                        <div
                            class="w-6 rounded-t-md transition-all duration-150 bg-accent"
                            :style="{
                                height: `${(room.available / topTick) * 100}%`,
                            }"
                        />
                        <div
                            class="w-6 rounded-t-md transition-all duration-150 bg-muted"
                            :style="{
                                height: `${(room.reserved / topTick) * 100}%`,
                            }"
                        />

                        <Transition name="fade">
                            <div
                                v-if="hovered === room.label"
                                class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 z-10 whitespace-nowrap rounded-xl bg-white border border-muted-light shadow-md px-4 py-3 text-left dark:bg-secondary dark:border-white/10"
                            >
                                <p
                                    class="text-secondary text-sm font-medium mb-1.5 dark:text-white"
                                >
                                    {{ room.label }}
                                </p>
                                <p
                                    class="flex items-center gap-2 text-xs text-primary"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-primary"
                                    />
                                    Occupied : {{ room.occupied }}
                                </p>
                                <p
                                    class="flex items-center gap-2 text-xs text-accent"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-accent"
                                    />
                                    Available : {{ room.available }}
                                </p>
                                <p
                                    class="flex items-center gap-2 text-xs text-muted dark:text-gray-400"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full bg-muted"
                                    />
                                    Reserved : {{ room.reserved }}
                                </p>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex mt-2" style="margin-left: 28px">
            <div
                v-for="room in rooms"
                :key="room.label"
                class="flex-1 text-center text-xs text-muted dark:text-gray-400"
            >
                {{ room.label }}
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";

export interface RoomOccupancy {
    label: string;
    occupied: number;
    available: number;
    reserved: number;
}

const props = withDefaults(
    defineProps<{
        title?: string;
        rooms: RoomOccupancy[];
    }>(),
    {
        title: "Room occupancy by type",
    },
);

const hovered = ref<string | null>(null);

const maxValue = computed(() =>
    Math.max(
        ...props.rooms.flatMap((r) => [r.occupied, r.available, r.reserved]),
        1,
    ),
);

function niceStep(range: number): number {
    if (range <= 0) return 1;
    const exponent = Math.floor(Math.log10(range));
    const fraction = range / Math.pow(10, exponent);
    let niceFraction: number;
    if (fraction < 1.5) niceFraction = 1;
    else if (fraction < 3) niceFraction = 2;
    else if (fraction < 7) niceFraction = 5;
    else niceFraction = 10;
    return Math.max(1, Math.round(niceFraction * Math.pow(10, exponent)));
}

const topTick = computed(() => maxValue.value);

const yTicks = computed(() => {
    const top = topTick.value;
    const step = niceStep(top / 4);
    const ticks: number[] = [];

    for (let v = 0; v < top; v += step) ticks.push(v);
    ticks.push(Number(top.toFixed(1)));

    return ticks.reverse().map((t) => t.toString());
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.12s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
