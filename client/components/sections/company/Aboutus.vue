<template>
    <div class="bg-white">
        <section
            class="relative overflow-hidden bg-light/40 px-6 pb-14 pt-28 sm:px-[5%] sm:pb-16 sm:pt-32 lg:px-[10%]"
        >
            <div
                class="pointer-events-none absolute -top-24 left-[-100px] h-[380px] w-[380px] rounded-full bg-primary-200/40 blur-[100px]"
            />

            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 h-28 bg-gradient-to-b from-transparent to-white"
            />

            <div class="relative z-10 mx-auto max-w-3xl text-center">
                <p
                    class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                >
                    About Us
                </p>

                <h1 class="text-3xl font-black text-secondary md:text-4xl">
                    Built to make caregiving simpler
                </h1>

                <p class="mt-4 text-sm leading-7 text-muted md:text-base">
                    AMUMA is a centralized platform that brings homecare and
                    in-house facility operations together — patient admissions,
                    scheduling, medication tracking, billing, and family
                    communication, all in one place. It's built for caregiving
                    agencies in Davao City and beyond who are ready to move on
                    from paper-based, fragmented workflows.
                </p>
            </div>
        </section>

        <section class="mx-auto w-full md:px-[5%] lg:px-[10%] px-6 py-16">
            <div class="grid gap-10 lg:grid-cols-[1fr_1.1fr] lg:items-center">
                <div>
                    <p
                        class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                    >
                        Why We Built This
                    </p>
                    <h2 class="mb-4 text-2xl font-bold text-secondary">
                        Caregiving deserves better than paper and Messenger
                    </h2>
                    <p class="text-sm leading-7 text-muted">
                        Most caregiving agencies still run on paper —
                        handwritten admission forms, medication logs kept in
                        notebooks, and family updates sent one conversation at a
                        time. We built AMUMA to bring all of that into a single
                        connected platform: bookings, admissions, scheduling,
                        medication tracking, billing, and family communication,
                        so agencies can spend less time on paperwork and more
                        time on care.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div
                        v-for="stat in stats"
                        :key="stat.label"
                        class="rounded-2xl border border-gray-200 bg-white p-5 text-center shadow-sm"
                    >
                        <p class="text-2xl font-black text-primary md:text-3xl">
                            {{ stat.value }}
                        </p>
                        <p class="mt-2 text-xs leading-5 text-muted">
                            {{ stat.label }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="mt-12 rounded-2xl border border-gray-200 bg-light/40 p-6 shadow-sm sm:p-8"
            >
                <p
                    class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                >
                    Partner Agencies
                </p>
                <p class="text-sm leading-7 text-muted">
                    AMUMA was built together with four caregiving agencies in
                    Davao City —
                    <span class="font-semibold text-secondary"
                        >ENJ USDI Inc. Corp.</span
                    >,
                    <span class="font-semibold text-secondary"
                        >Davao Viel Geriatric Home Care Center</span
                    >,
                    <span class="font-semibold text-secondary"
                        >Tahanan Caregiving Care Services</span
                    >, and
                    <span class="font-semibold text-secondary"
                        >Riemon Haven's Homecare Nursing Home</span
                    >
                    — whose day-to-day operations shaped every module in the
                    system.
                </p>
            </div>
        </section>

        <section class="mx-auto w-full px-6 py-16 md:px-[5%] lg:px-[10%]">
            <div class="mb-14 text-center">
                <p
                    class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                >
                    The Team
                </p>

                <h2 class="text-2xl font-bold text-secondary md:text-3xl">
                    Meet the People Behind AMUMA
                </h2>

                <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-muted">
                    The people responsible for the research, design, and
                    development of the platform.
                </p>
            </div>

            <div class="relative mx-auto w-full">
                <div
                    class="absolute left-1/2 top-0 hidden h-full w-px -translate-x-1/2 bg-gradient-to-b from-primary/10 via-primary/30 to-primary/10 md:block"
                />

                <div class="space-y-8 md:space-y-10">
                    <article
                        v-for="(member, index) in team"
                        :key="member.name"
                        class="relative md:w-[78%]"
                        :class="
                            index % 2 === 0
                                ? 'md:mr-auto md:pr-10'
                                : 'md:ml-auto md:pl-10'
                        "
                    >
                        <div
                            class="group relative rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-primary/25 hover:shadow-[0_20px_40px_rgba(49,130,237,0.08),0_4px_12px_rgba(15,23,42,0.04)] sm:p-6"
                        >
                            <div
                                class="absolute top-1/2 hidden h-3 w-3 -translate-y-1/2 rounded-full border-2 border-white bg-primary shadow-[0_0_0_3px_rgba(49,130,237,0.12)] md:block"
                                :class="
                                    index % 2 === 0
                                        ? 'right-[-6px]'
                                        : 'left-[-6px]'
                                "
                            />

                            <div
                                class="flex flex-col gap-5 sm:flex-row sm:items-start"
                            >
                                <div
                                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl"
                                    :class="member.badge"
                                >
                                    <User class="h-7 w-7" />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <h3
                                                class="text-base font-bold text-secondary"
                                            >
                                                {{ member.name }}
                                            </h3>

                                            <p
                                                class="mt-1 text-xs font-semibold text-primary"
                                            >
                                                {{ member.title }}
                                            </p>
                                        </div>
                                    </div>

                                    <p
                                        class="mt-3 text-sm leading-6 text-muted"
                                    >
                                        {{ member.blurb }}
                                    </p>

                                    <div class="mt-4 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="role in member.roles"
                                            :key="role"
                                            class="rounded-full px-2.5 py-1 text-[11px] font-medium"
                                            :class="member.badge"
                                        >
                                            {{ role }}
                                        </span>
                                    </div>

                                    <div
                                        class="mt-4 flex flex-col gap-2 border-t border-gray-100 pt-4 text-xs text-muted sm:flex-row sm:items-center sm:gap-5"
                                    >
                                        <a
                                            :href="`mailto:${member.email}`"
                                            class="flex min-w-0 items-center gap-2 transition-colors hover:text-primary"
                                        >
                                            <Mail
                                                class="h-3.5 w-3.5 shrink-0 text-primary"
                                            />

                                            <span class="truncate">
                                                {{ member.email }}
                                            </span>
                                        </a>

                                        <a
                                            :href="`tel:${member.phone}`"
                                            class="flex items-center gap-2 transition-colors hover:text-primary"
                                        >
                                            <Phone
                                                class="h-3.5 w-3.5 shrink-0 text-primary"
                                            />

                                            {{ member.phone }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </div>
</template>

<script lang="ts" setup>
import { User, Mail, Phone } from "lucide-vue-next";

const stats = [
    {
        value: "4",
        label: "Caregiving agencies partnered with us in Davao City",
    },
    {
        value: "8+",
        label: "Integrated modules — booking, billing, eMAR, and more",
    },
    { value: "24/7", label: "Family Portal access for real-time updates" },
];
const team = [
    {
        name: "Prince I. Sestoso",
        title: "System Analysis & Development",
        blurb: "Prince leads the technical direction of AMUMA, from the database schema up to the code that ties every module together. He built the Laravel backend and worked across the Nuxt frontend to make sure bookings, billing, and the Family Portal all work together seamlessly.",
        roles: [
            "System Analysis",
            "Backend Developer",
            "Frontend Developer",
            "Full-stack Developer",
            "System Integration",
        ],
        email: "sestoso.342499@davao.sti.edu.ph",
        phone: "+63 977 117 1913",
        badge: "bg-primary-50 text-primary",
    },
    {
        name: "Nicollette G. Libunao",
        title: "UI/UX & Frontend Development",
        blurb: "Nicollette shapes how AMUMA looks and feels, translating caregiving workflows into interfaces that stay simple for admission staff, caregivers, and families alike. She also leads the project's documentation, keeping every design decision clearly recorded.",
        roles: [
            "Primary Documentation",
            "Frontend Developer",
            "UI/UX Design",
        ],
        email: "libunao.342146@davao.sti.edu.ph",
        phone: "+63 960 260 2240",
        badge: "bg-accent-50 text-accent",
    },
    {
        name: "Gem S. Mamale",
        title: "Business Analysis & Testing",
        blurb: "Gem grounds AMUMA in the real needs of caregiving agencies, running the requirements analysis that shapes every module. She also leads functional testing, working through each booking, billing, and admission flow to catch issues before they reach real users.",
        roles: [
            "Test Documentation",
            "Business Analysis",
            "Requirements Analysis",
            "Functional Testing",
        ],
        email: "mamale.375787@davao.sti.edu.ph",
        phone: "+63 981 140 0436",
        badge: "bg-primary-50 text-primary",
    },
    {
        name: "Hannah P. Magsipoc",
        title: "Documentation & Quality Assurance",
        blurb: "Hannah supports the team's documentation and testing efforts, helping verify that features work as intended and that the project's written records stay accurate and complete throughout development.",
        roles: [
            "Documentation Assistance",
            "Functional Testing",
        ],
        email: "magsipoc.357005@davao.sti.edu.ph",
        phone: "+63 909 332 491",
        badge: "bg-accent-50 text-accent",
    },
];

</script>
