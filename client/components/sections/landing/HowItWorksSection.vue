<script setup lang="ts">
import Reveal from "~/components/ui/Reveal.vue";

const steps = [
    {
        num: "01",
        title: "Family books a visit",
        text: "Via web portal. AMUMA automatically creates a patient profile and prepares the request for review.",
    },
    {
        num: "02",
        title: "Admission routes the request",
        text: "Admission staff assigns a caregiver or admits the patient into an available room with a single click.",
    },
    {
        num: "03",
        title: "Care happens, billing follows",
        text: "Nurses complete visits, the cashier automatically generates invoices, and balances update instantly.",
    },
];
</script>

<template>
    <section
        class="relative overflow-hidden bg-slate-50 py-28 dark:bg-secondary"
    >
        <div
            class="pointer-events-none absolute bottom-0 left-[-100px] h-[400px] w-[400px] rounded-full bg-indigo-200/40 blur-[100px] dark:bg-primary/10"
        ></div>

        <div
            class="relative z-10 mx-auto grid w-[94%] max-w-[1600px] items-center gap-20 px-10 max-sm:px-4 lg:grid-cols-[55%_45%]"
        >
            <div class="flex flex-col gap-5 max-lg:order-2">
                <Reveal
                    v-for="(step, i) in steps"
                    :key="step.num"
                    :delay="i * 140"
                >
                    <div
                        class="group flex items-center gap-6 max-sm:flex-col max-sm:items-start max-sm:gap-3 rounded-[24px] border border-gray-200 bg-white px-8 py-7 shadow-[0_2px_12px_rgba(15,23,42,0.04)] transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-[0_16px_40px_rgba(49,130,237,0.12)] dark:border-white/10 dark:bg-white/[0.03] dark:shadow-none dark:hover:border-primary/40"
                    >
                        <div
                            class="min-w-[80px] text-center text-6xl max-sm:text-left max-sm:text-5xl font-black text-primary transition-transform duration-300 group-hover:scale-110"
                        >
                            {{ step.num }}
                        </div>

                        <div>
                            <h4
                                class="mb-2 text-lg font-bold text-secondary dark:text-white"
                            >
                                {{ step.title }}
                            </h4>

                            <p
                                class="text-sm leading-7 text-muted dark:text-gray-400"
                            >
                                {{ step.text }}
                            </p>
                        </div>
                    </div>
                </Reveal>
            </div>

            <Reveal class="flex flex-col justify-center max-lg:order-1">
                <p
                    class="mb-5 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                >
                    HOW IT WORKS
                </p>

                <h2
                    class="mb-8 text-[clamp(2.8rem,5vw,5rem)] leading-none font-black tracking-[-0.03em] text-secondary dark:text-white"
                >
                    A

                    <span
                        class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent dark:to-primary-300"
                    >
                        gentle
                        <br />
                        workflow
                    </span>

                    for
                    <br />
                    an intense
                    <br />
                    profession.
                </h2>

                <NuxtLink
                    to="/how-booking-works"
                    class="group inline-flex w-fit items-center gap-2 font-semibold text-primary"
                >
                    Walk through the booking flow
                    <span
                        class="transition-transform duration-200 group-hover:translate-x-1"
                    >
                        →
                    </span>
                </NuxtLink>
            </Reveal>
        </div>
    </section>
</template>