<script setup lang="ts">
import { computed, type Component } from "vue";
import { Check } from "lucide-vue-next";

type Action = { label: string; to: string };
type Showcase = { light: string; dark: string; alt: string };
type FloatCard = { icon: Component; title: string; text: string };

const props = withDefaults(
    defineProps<{
        title: string;
        subtitle: string;
        highlight?: string;
        // Small label above the headline, e.g. "For agencies".
        eyebrow?: string;
        primary?: Action | null;
        secondary?: Action | null;
        // Short selling points shown as chips under the buttons.
        points?: string[];
        // Product screenshot that rises out of the bottom of the hero panel.
        showcase?: Showcase | null;
        // Up to two small cards that hang off the corners of the screenshot window.
        floats?: FloatCard[];
    }>(),
    {
        highlight: "",
        eyebrow: "",
        primary: null,
        secondary: null,
        points: () => [],
        showcase: null,
        floats: () => [],
    },
);

// Splits the title so the highlighted words get the same blue gradient as the landing hero.
const parts = computed(() => {
    const at = props.highlight ? props.title.indexOf(props.highlight) : -1;

    if (at === -1) return { before: props.title, mid: "", after: "" };

    return {
        before: props.title.slice(0, at),
        mid: props.highlight,
        after: props.title.slice(at + props.highlight.length),
    };
});
</script>

<template>
    <section
        class="relative overflow-hidden bg-slate-50 transition-colors duration-500 dark:bg-secondary"
        :class="showcase ? 'pt-36 pb-16 max-sm:pb-10' : 'pt-40 pb-14'"
    >
        <div
            class="pointer-events-none absolute -top-[120px] -right-[80px] h-[520px] w-[520px] rounded-full bg-blue-300 opacity-35 blur-[70px] dark:opacity-15"
        ></div>

        <div
            class="pointer-events-none absolute -left-[60px] bottom-0 h-[320px] w-[320px] rounded-full bg-indigo-200 opacity-35 blur-[70px] dark:opacity-10"
        ></div>

        <!-- ============ Panel hero (landscape): used when a screenshot is passed ============ -->
        <div
            v-if="showcase"
            class="relative z-10 mx-auto w-[94%] max-w-[1400px] overflow-hidden rounded-[2rem] border border-gray-200/80 bg-gradient-to-br from-white via-white to-primary-50/80 shadow-[0_30px_90px_rgba(49,130,237,0.10)] transition-colors duration-500 max-sm:rounded-3xl dark:border-white/10 dark:from-secondary-900 dark:via-secondary-900 dark:to-secondary-800/70 dark:shadow-[0_30px_90px_rgba(0,0,0,0.4)]"
        >
            <div
                class="relative grid items-center gap-x-12 px-12 py-12 max-lg:gap-y-10 max-lg:px-6 max-lg:py-10 max-sm:px-4 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]"
            >
                <!-- Text -->
                <div>
                    <div
                        v-if="eyebrow"
                        class="mb-6 inline-flex items-center gap-2.5 rounded-full border border-primary/20 bg-white/80 py-1.5 pr-4 pl-3 text-xs font-semibold tracking-wide text-primary shadow-sm backdrop-blur motion-safe:animate-fadeInUp dark:border-primary/30 dark:bg-white/5"
                    >
                        <span class="relative flex h-2 w-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-60"
                            ></span>
                            <span
                                class="relative inline-flex h-2 w-2 rounded-full bg-primary"
                            ></span>
                        </span>
                        {{ eyebrow }}
                    </div>

                    <h1
                        class="mb-4 text-[clamp(2.2rem,3.3vw,3.3rem)] font-black leading-[1.05] tracking-[-0.045em] text-secondary motion-safe:animate-fadeInUp dark:text-white"
                    >
                        {{ parts.before
                        }}<span
                            v-if="parts.mid"
                            class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent dark:to-primary-300"
                            >{{ parts.mid }}</span
                        >{{ parts.after }}
                    </h1>

                    <p
                        class="max-w-[520px] text-[0.95rem] leading-7 text-muted motion-safe:animate-fadeInUp dark:text-gray-400"
                        style="animation-delay: 120ms"
                    >
                        {{ subtitle }}
                    </p>

                    <div
                        v-if="primary || secondary"
                        class="mt-7 flex flex-wrap gap-3 motion-safe:animate-fadeInUp"
                        style="animation-delay: 200ms"
                    >
                        <NuxtLink
                            v-if="primary"
                            :to="primary.to"
                            class="group flex items-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-[0.95rem] font-bold text-white shadow-lg shadow-primary/25 transition hover:-translate-y-0.5 hover:bg-blue-700"
                        >
                            {{ primary.label }}
                            <span
                                class="transition-transform duration-200 group-hover:translate-x-1"
                            >
                                →
                            </span>
                        </NuxtLink>

                        <NuxtLink
                            v-if="secondary"
                            :to="secondary.to"
                            class="rounded-xl border border-gray-200 bg-white/70 px-6 py-3.5 text-[0.95rem] font-bold text-secondary backdrop-blur transition hover:border-primary hover:text-primary dark:border-white/15 dark:bg-white/5 dark:text-white"
                        >
                            {{ secondary.label }}
                        </NuxtLink>
                    </div>

                    <ul
                        v-if="points.length"
                        class="mt-7 grid gap-x-5 gap-y-2.5 motion-safe:animate-fadeInUp sm:grid-cols-2"
                        style="animation-delay: 280ms"
                    >
                        <li
                            v-for="point in points"
                            :key="point"
                            class="flex items-center gap-2.5 text-[13px] font-medium text-secondary dark:text-gray-200"
                        >
                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                            >
                                <Check class="h-3 w-3" :stroke-width="3" />
                            </span>
                            {{ point }}
                        </li>
                    </ul>
                </div>

                <!-- Screenshot: one clean window, nothing laid on top of it -->
                <div
                    class="relative motion-safe:animate-fadeInUp lg:py-6"
                    style="animation-delay: 240ms"
                >
                    <div
                        class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-[0_25px_70px_rgba(49,130,237,0.18)] transition-[background-color,border-color,box-shadow] duration-500 dark:border-white/10 dark:bg-secondary-900 dark:shadow-[0_25px_70px_rgba(0,0,0,0.5)]"
                    >
                        <div
                            class="flex items-center gap-3 border-b border-gray-100 bg-slate-50/90 px-4 py-2.5 transition-colors duration-500 dark:border-white/10 dark:bg-secondary-800"
                        >
                            <div class="flex gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-red-300"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                            </div>
                            <div
                                class="mx-auto h-5 w-1/2 rounded-md bg-white shadow-inner dark:bg-white/5"
                            ></div>
                            <span class="w-[42px]"></span>
                        </div>

                        <div class="relative grid overflow-hidden">
                            <template
                                v-for="mode in ['light', 'dark'] as const"
                                :key="mode"
                            >
                                <img
                                    v-if="showcase[mode]"
                                    :src="showcase[mode]"
                                    :alt="showcase.alt"
                                    class="theme-shot col-start-1 row-start-1 block h-auto w-full"
                                    :class="
                                        mode === 'light'
                                            ? 'opacity-100 dark:scale-[1.03] dark:opacity-0'
                                            : 'scale-[0.97] opacity-0 dark:scale-100 dark:opacity-100'
                                    "
                                />
                            </template>
                        </div>
                    </div>

                    <!-- Floating cards hang off the corners, so they only touch the window's title bar and edge, never its content -->
                    <div
                        v-if="floats[0]"
                        class="float-b absolute top-0 right-8 hidden items-center gap-3 rounded-2xl border border-white/70 bg-white/95 px-4 py-3 shadow-[0_18px_40px_rgba(15,23,42,0.16)] backdrop-blur-md transition-colors duration-500 lg:flex dark:border-white/10 dark:bg-secondary-800/95"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <component :is="floats[0].icon" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-bold text-secondary dark:text-white">
                                {{ floats[0].title }}
                            </p>
                            <p class="text-xs text-muted dark:text-gray-400">
                                {{ floats[0].text }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="floats[1]"
                        class="float-a absolute bottom-0 left-10 hidden items-center gap-3 rounded-2xl border border-white/70 bg-white/95 px-4 py-3 shadow-[0_18px_40px_rgba(15,23,42,0.16)] backdrop-blur-md transition-colors duration-500 lg:flex dark:border-white/10 dark:bg-secondary-800/95"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent/10 text-accent dark:text-accent-300"
                        >
                            <component :is="floats[1].icon" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-bold text-secondary dark:text-white">
                                {{ floats[1].title }}
                            </p>
                            <p class="text-xs text-muted dark:text-gray-400">
                                {{ floats[1].text }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ Plain hero: unchanged layout for pages without a screenshot ============ -->
        <div
            v-else
            class="relative z-10 mx-auto flex w-[94%] max-w-[1600px] items-center gap-16 px-10 max-lg:flex-col max-sm:px-4"
        >
            <div
                class="flex-1"
                :class="
                    $slots.aside
                        ? 'max-w-[680px]'
                        : 'mx-auto max-w-[900px] text-center'
                "
            >
                <div
                    v-if="eyebrow"
                    class="mb-7 inline-flex items-center gap-2.5 rounded-full border border-primary/20 bg-white/80 py-1.5 pr-4 pl-3 text-xs font-semibold tracking-wide text-primary shadow-sm backdrop-blur dark:border-primary/30 dark:bg-white/5"
                >
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-60"
                        ></span>
                        <span
                            class="relative inline-flex h-2 w-2 rounded-full bg-primary"
                        ></span>
                    </span>
                    {{ eyebrow }}
                </div>

                <div
                    v-else
                    class="mb-8 h-[10px] w-[10px] animate-pulse rounded-full bg-primary shadow-[0_0_25px_rgba(49,130,237,0.8)]"
                    :class="$slots.aside ? '' : 'mx-auto'"
                ></div>

                <h1
                    class="mb-4 text-[clamp(2.3rem,3.6vw,3.6rem)] font-black leading-[1.02] tracking-[-0.045em] text-secondary motion-safe:animate-fadeInUp dark:text-white"
                >
                    {{ parts.before
                    }}<span
                        v-if="parts.mid"
                        class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent dark:to-primary-300"
                        >{{ parts.mid }}</span
                    >{{ parts.after }}
                </h1>

                <p
                    class="max-w-[640px] text-[0.95rem] leading-8 text-muted motion-safe:animate-fadeInUp dark:text-gray-400"
                    :class="$slots.aside ? '' : 'mx-auto'"
                    style="animation-delay: 120ms"
                >
                    {{ subtitle }}
                </p>

                <span
                    class="mt-4 mb-7 block h-[3px] w-20 rounded bg-primary motion-safe:animate-fadeInUp"
                    :class="$slots.aside ? '' : 'mx-auto'"
                    style="animation-delay: 180ms"
                ></span>

                <div
                    v-if="primary || secondary"
                    class="flex flex-wrap gap-3 motion-safe:animate-fadeInUp"
                    :class="$slots.aside ? '' : 'justify-center'"
                    style="animation-delay: 240ms"
                >
                    <NuxtLink
                        v-if="primary"
                        :to="primary.to"
                        class="group flex items-center gap-2 rounded-xl bg-primary px-7 py-3.5 text-[0.95rem] font-bold text-white transition hover:bg-blue-700"
                    >
                        {{ primary.label }}
                        <span
                            class="transition-transform duration-200 group-hover:translate-x-1"
                        >
                            →
                        </span>
                    </NuxtLink>

                    <NuxtLink
                        v-if="secondary"
                        :to="secondary.to"
                        class="rounded-xl border border-primary px-7 py-3.5 text-[0.95rem] font-bold text-primary transition hover:bg-primary hover:text-white"
                    >
                        {{ secondary.label }}
                    </NuxtLink>
                </div>
            </div>

            <div
                v-if="$slots.aside"
                class="w-full flex-1 motion-safe:animate-fadeInUp"
                style="animation-delay: 200ms"
            >
                <slot name="aside" />
            </div>
        </div>
    </section>
</template>

<style scoped>
.theme-shot {
    transition:
        opacity 0.6s ease,
        transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
}

@keyframes hero-float {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-6px);
    }
}

@media (prefers-reduced-motion: no-preference) {
    .float-a {
        animation: hero-float 6s ease-in-out infinite;
    }

    .float-b {
        animation: hero-float 7s ease-in-out infinite 1.2s;
    }
}

@media (prefers-reduced-motion: reduce) {
    .theme-shot {
        transition: none;
    }
}
</style>