<script setup lang="ts">
import Reveal from "~/components/ui/Reveal.vue";
import scheduleImg from "~/assets/images/Rectangle_12.png";
import billingImg from "~/assets/images/Rectangle_13.png";
import securityImg from "~/assets/images/Rectangle_15.png";

const cards = [
    {
        tag: "Schedule",
        highlight: "Caregiver",
        title: "scheduling that works",
        text: "Assign shifts and manage availability seamlessly.",
        image: scheduleImg,
        to: "/for-agencies#scheduling",
        wide: false,
    },
    {
        tag: "Billing",
        highlight: "Subscriptions",
        title: "and billing plans",
        text: "Handle payments and invoicing automatically.",
        image: billingImg,
        to: "/for-agencies#billing",
        wide: false,
    },
    {
        tag: "Secure",
        highlight: "CCTV Solutions",
        title: "coverage that captures",
        text: "Integrated, seamless 24/7 security recording and retrieval.",
        image: securityImg,
        to: "/solutions/facility",
        wide: true,
    },
];
</script>

<template>
    <section
        class="relative overflow-hidden bg-white py-24 dark:bg-secondary"
    >
        <div
            class="pointer-events-none absolute top-0 right-[-150px] h-[500px] w-[500px] rounded-full bg-cyan-200/40 blur-[100px] dark:bg-accent/10"
        ></div>

        <div
            class="relative z-10 mx-auto flex w-[94%] max-w-[1600px] gap-16 px-10 max-sm:px-4 max-lg:flex-col"
        >
            <Reveal class="w-[360px] shrink-0 max-lg:w-full">
                <p
                    class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                >
                    FEATURES
                </p>

                <h2
                    class="mb-4 text-[clamp(2.5rem,3.9vw,4rem)] leading-none font-black tracking-[-0.03em] text-secondary dark:text-white"
                >
                    <span
                        class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent dark:to-primary-300"
                    >
                        Multi-<br />
                        branch
                    </span>
                    <br />
                    oversight
                    <br />
                    made simple.
                </h2>

                <p class="mb-6 text-sm leading-7 text-muted dark:text-gray-400">
                    Coordinate care across all locations from one dashboard.
                </p>

                <NuxtLink
                    to="/for-agencies#features"
                    class="group inline-flex items-center gap-2 font-semibold text-primary"
                >
                    Explore

                    <span
                        class="transition-transform duration-200 group-hover:translate-x-1"
                    >
                        →
                    </span>
                </NuxtLink>
            </Reveal>

            <div class="grid flex-1 gap-4 lg:grid-cols-2">
                <Reveal
                    v-for="(card, i) in cards"
                    :key="card.tag"
                    :delay="i * 120"
                    :class="card.wide ? 'lg:col-span-2' : ''"
                >
                    <NuxtLink
                        :to="card.to"
                        class="group block h-full overflow-hidden rounded-3xl border border-gray-200 bg-white transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-[0_16px_40px_rgba(49,130,237,0.12)] dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-primary/40"
                    >
                        <div
                            class="flex h-full"
                            :class="card.wide ? 'max-md:flex-col' : 'flex-col'"
                        >
                            <div
                                class="p-6"
                                :class="
                                    card.wide
                                        ? 'flex w-[46%] flex-col justify-center p-8 max-md:w-full'
                                        : ''
                                "
                            >
                                <div
                                    class="mb-3 flex items-center gap-2 text-xs font-semibold tracking-wider text-muted uppercase dark:text-gray-400"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-primary"
                                    ></span>

                                    {{ card.tag }}
                                </div>

                                <h4
                                    class="mb-2 font-bold text-secondary dark:text-white"
                                    :class="card.wide ? 'text-xl' : 'text-lg'"
                                >
                                    <span class="text-primary">
                                        {{ card.highlight }}
                                    </span>
                                    {{ card.title }}
                                </h4>

                                <p
                                    class="text-sm leading-6 text-muted dark:text-gray-400"
                                >
                                    {{ card.text }}
                                </p>
                            </div>

                            <div
                                class="overflow-hidden"
                                :class="card.wide ? 'flex-1' : 'mt-auto'"
                            >
                                <img
                                    :src="card.image"
                                    :alt="`${card.tag} preview`"
                                    class="w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                                    :class="
                                        card.wide
                                            ? 'h-[280px]'
                                            : 'h-[220px]'
                                    "
                                />
                            </div>
                        </div>
                    </NuxtLink>
                </Reveal>
            </div>
        </div>
    </section>
</template>