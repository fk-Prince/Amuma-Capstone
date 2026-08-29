<template>
    <section class="relative overflow-hidden bg-slate-50 px-[6%] py-24 dark:bg-secondary">
        <!-- Blob -->

        <div
            class="absolute top-[20%] right-[-150px] h-[500px] w-[500px] rounded-full bg-blue-200/30 blur-[120px]"
        ></div>

        <div class="relative z-10">
            <!-- Header -->

            <div class="mb-12">
                <h2
                    class="mb-3 text-[clamp(2rem,3vw,3rem)] font-black leading-tight text-secondary dark:text-white"
                >
                    Trusted by

                    <span
                        class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent"
                    >
                        Care Teams
                    </span>

                    Across the Philippines
                </h2>

                <p class="text-muted dark:text-gray-400">
                    — here's what agencies say after switching to AMUMA.
                </p>
            </div>

            <!-- Cards -->

            <div
                class="flex gap-5 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-none"
            >
                <div
                    v-for="t in testimonials"
                    :key="t.name"
                    class="flex w-[320px] shrink-0 snap-start flex-col rounded-[24px] border border-gray-200 bg-white p-7 shadow-[0_2px_16px_rgba(15,23,42,0.05)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_16px_40px_rgba(49,130,237,0.10)] dark:bg-secondary dark:border-white/10"
                >
                    <!-- Quote -->

                    <div class="mb-2 text-6xl leading-none text-gray-200">
                        "
                    </div>

                    <!-- Stars -->

                    <div class="mb-4 text-sm tracking-[0.2em] text-yellow-500">
                        ★★★★★
                    </div>

                    <!-- Content -->

                    <p
                        class="flex-1 text-sm leading-7 text-muted-dark dark:text-gray-300"
                        v-html="t.quote"
                    ></p>

                    <!-- Footer -->

                    <div
                        class="mt-6 flex items-center gap-3 rounded-2xl bg-primary px-4 py-4"
                    >
                        <div
                            class="h-11 w-11 shrink-0 rounded-full border-2 border-white/30"
                            :style="{
                                background: t.avatarColor,
                            }"
                        ></div>

                        <div>
                            <p class="text-sm font-bold text-white">
                                {{ t.name }}
                            </p>

                            <p class="text-xs text-white/70">
                                {{ t.role }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
const testimonials = [
    {
        name: "Dr. Barbie Santos",

        role: "Agency Director · CareFirst Home Services",

        avatarColor: "#f87171",

        quote: `"<span class='font-bold text-primary'>AMUMA</span> completely transformed how we run our home care operations. Scheduling used to take hours — now it takes minutes."`,
    },

    {
        name: "Aytisa Winner",

        role: "Facility Administrator · Golden Year Home",

        avatarColor: "#60a5fa",

        quote: `"<span class='font-bold text-primary'>AMUMA</span> gives me a live dashboard for every location. Billing, admissions, staff — all in one place."`,
    },

    {
        name: "Azriel Makinis",

        role: "Head Nurse · Malasakit Care Center",

        avatarColor: "#34d399",

        quote: `"<span class='font-bold text-primary'>AMUMA</span> makes vitals logging and care notes incredibly simple, even during busy shifts."`,
    },

    {
        name: "Jack",

        role: "Family Member",

        avatarColor: "#a78bfa",

        quote: `"I can check my mother's status, visits, and balances online anytime. It gives our family peace of mind."`,
    },

    {
        name: "Maria Dela Cruz",

        role: "Operations Manager · SunCare Agency",

        avatarColor: "#fb923c",

        quote: `"The QR check-in alone saved us so much paperwork. AMUMA made our team significantly faster."`,
    },

    {
        name: "Renz Paglinawan",

        role: "Caregiver · HomeHeart Services",

        avatarColor: "#22d3ee",

        quote: `"eMAR is fast, clean, and easy to understand. I feel much more confident giving care."`,
    },

    {
        name: "Lorna Bautista",

        role: "Billing Staff · Malasakit Care Center",

        avatarColor: "#4ade80",

        quote: `"Invoices are automatic now. Families pay online and our month-end workload is much lighter."`,
    },

    {
        name: "Engr. Ramon Sy",

        role: "Owner · Tahanan Elderly Home",

        avatarColor: "#e879f9",

        quote: `"ROI became visible within two months. Less admin work, fewer errors, and happier staff."`,
    },
];
</script>
