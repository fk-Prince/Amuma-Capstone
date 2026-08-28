<template>
    <div class="bg-white">
        <section
            class="relative z-10 w-full overflow-hidden bg-light/40 px-6 pb-12 pt-24 sm:px-[5%] sm:pb-16 sm:pt-32 lg:px-[10%]"
        >
            <div
                class="pointer-events-none absolute -top-24 right-[-100px] h-[380px] w-[380px] rounded-full bg-primary-200/40 blur-[100px]"
            />

            <img
                src="~/assets/images/Rectangle_15.png"
                alt=""
                class="pointer-events-none absolute inset-y-0 right-0 hidden w-[48%] object-cover object-left lg:block"
                style="
                    mask-image: linear-gradient(
                        to right,
                        transparent,
                        black 30%
                    );
                    -webkit-mask-image: linear-gradient(
                        to right,
                        transparent,
                        black 30%
                    );
                "
            />

            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 h-28 bg-gradient-to-b from-transparent to-white"
            />

            <div class="relative z-10 mx-auto w-full">
                <div
                    class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary"
                >
                    <Bookmark class="h-6 w-6" />
                </div>

                <h1 class="text-3xl font-black text-secondary md:text-4xl">
                    Resources
                </h1>

                <p
                    class="mt-3 max-w-2xl text-sm leading-7 text-muted md:text-base"
                >
                    Access healthcare guides, caregiver resources, patient
                    education, FAQs, and support materials — all in one place.
                </p>
            </div>
        </section>

        <main
            class="relative z-0 mx-auto w-full md:px-[5%] lg:px-[10%] space-y-20 bg-white px-6 py-10"
        >
            <section>
                <div class="mb-8 flex items-end justify-between gap-4">
                    <div>
                        <p
                            class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                        >
                            Guides
                        </p>

                        <h2 class="text-2xl font-bold text-secondary">
                            Featured Resources
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="group hidden shrink-0 items-center gap-1.5 text-sm font-semibold text-primary sm:inline-flex"
                    >
                        View all resources

                        <ArrowRight
                            class="h-4 w-4 transition-transform group-hover:translate-x-1"
                        />
                    </button>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <NuxtLink
                        v-for="item in featuredResources"
                        :key="item.title"
                        :to="item.to"
                        class="group rounded-3xl border border-gray-200 bg-white p-7 transition-all duration-300 hover:-translate-y-1 hover:border-primary/25 hover:shadow-[0_20px_40px_rgba(49,130,237,0.08),0_4px_12px_rgba(15,23,42,0.04)]"
                    >
                        <div
                            class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl"
                            :class="item.tint"
                        >
                            <component :is="item.icon" class="h-6 w-6" />
                        </div>

                        <h3 class="mb-2 text-lg font-bold text-secondary">
                            {{ item.title }}
                        </h3>

                        <p class="mb-6 text-sm leading-6 text-muted">
                            {{ item.description }}
                        </p>

                        <span
                            class="inline-flex items-center gap-2 text-sm font-semibold"
                            :class="item.link"
                        >
                            {{ item.cta }}

                            <ArrowRight
                                class="h-4 w-4 transition-transform group-hover:translate-x-1"
                            />
                        </span>
                    </NuxtLink>
                </div>
            </section>

            <section>
                <p
                    class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                >
                    Browse
                </p>

                <h2 class="mb-8 text-2xl font-bold text-secondary">
                    Resource Categories
                </h2>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <NuxtLink
                        v-for="cat in categories"
                        :key="cat.title"
                        :to="cat.to"
                        class="rounded-[20px] border border-gray-200 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:border-primary/25 hover:shadow-[0_20px_40px_rgba(49,130,237,0.08),0_4px_12px_rgba(15,23,42,0.04)]"
                    >
                        <div
                            class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl"
                            :class="cat.tint"
                        >
                            <component :is="cat.icon" class="h-5 w-5" />
                        </div>

                        <h3 class="mb-3 text-base font-bold text-secondary">
                            {{ cat.title }}
                        </h3>

                        <ul class="mb-5 space-y-2">
                            <li
                                v-for="point in cat.items"
                                :key="point"
                                class="flex items-center gap-2 text-sm text-muted"
                            >
                                <CheckCircle2
                                    class="h-3.5 w-3.5 shrink-0"
                                    :class="cat.accent"
                                />

                                {{ point }}
                            </li>
                        </ul>

                        <span
                            class="inline-flex items-center gap-1.5 text-sm font-semibold"
                            :class="cat.accent"
                        >
                            {{ cat.cta }}

                            <ArrowRight class="h-3.5 w-3.5" />
                        </span>
                    </NuxtLink>
                </div>
            </section>

            <section
                class="grid gap-10 lg:grid-cols-[1.15fr_1fr] lg:items-center"
            >
                <div>
                    <p
                        class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                    >
                        Support
                    </p>

                    <h2 class="mb-6 text-2xl font-bold text-secondary">
                        Frequently Asked Questions
                    </h2>

                    <div class="space-y-3">
                        <div
                            v-for="(faq, i) in faqs"
                            :key="faq.q"
                            class="overflow-hidden rounded-2xl border border-gray-200 transition-colors"
                            :class="openFaq === i ? 'border-primary/30' : ''"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                                @click="openFaq = openFaq === i ? null : i"
                            >
                                <span
                                    class="text-sm font-semibold text-secondary"
                                >
                                    {{ faq.q }}
                                </span>

                                <ChevronDown
                                    class="h-4 w-4 shrink-0 text-muted transition-transform duration-200"
                                    :class="
                                        openFaq === i
                                            ? 'rotate-180 text-primary'
                                            : ''
                                    "
                                />
                            </button>

                            <div
                                v-show="openFaq === i"
                                class="px-5 pb-4 text-sm leading-6 text-muted"
                            >
                                {{ faq.a }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden justify-center lg:flex">
                    <div
                        class="flex h-64 w-64 items-center justify-center rounded-full bg-light"
                    >
                        <MessageCircleQuestion class="h-24 w-24 text-primary" />
                    </div>
                </div>
            </section>

            <section>
                <div class="mb-8 flex items-end justify-between gap-4">
                    <div>
                        <p
                            class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                        >
                            Materials
                        </p>

                        <h2 class="text-2xl font-bold text-secondary">
                            Download Center
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="group hidden shrink-0 items-center gap-1.5 text-sm font-semibold text-primary sm:inline-flex"
                    >
                        View all downloads

                        <ArrowRight
                            class="h-4 w-4 transition-transform group-hover:translate-x-1"
                        />
                    </button>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="doc in downloads"
                        :key="doc.title"
                        class="rounded-2xl border border-gray-200 bg-white p-5"
                    >
                        <div
                            class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl"
                            :class="doc.tint"
                        >
                            <FileText class="h-5 w-5" />
                        </div>

                        <h3 class="mb-1 text-sm font-bold text-secondary">
                            {{ doc.title }}
                        </h3>

                        <p class="mb-4 text-xs text-muted">
                            PDF Document · {{ doc.size }}
                        </p>

                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 py-2 text-xs font-semibold text-secondary transition-colors hover:border-primary/30 hover:text-primary"
                        >
                            Download PDF

                            <Download class="h-3.5 w-3.5" />
                        </button>
                    </article>
                </div>
            </section>

            <section>
                <div class="mb-8 flex items-end justify-between gap-4">
                    <div>
                        <p
                            class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                        >
                            Learn
                        </p>

                        <h2 class="text-2xl font-bold text-secondary">
                            Video Tutorials
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="group hidden shrink-0 items-center gap-1.5 text-sm font-semibold text-primary sm:inline-flex"
                    >
                        View all videos

                        <ArrowRight
                            class="h-4 w-4 transition-transform group-hover:translate-x-1"
                        />
                    </button>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="video in videos"
                        :key="video.title"
                        class="group overflow-hidden rounded-2xl border border-gray-200 bg-white"
                    >
                        <div
                            class="relative flex h-32 items-center justify-center bg-gradient-to-br"
                            :class="video.gradient"
                        >
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-full bg-white/90 text-primary shadow-sm transition-transform duration-200 group-hover:scale-110"
                            >
                                <Play
                                    class="ml-0.5 h-4.5 w-4.5"
                                    fill="currentColor"
                                />
                            </div>

                            <span
                                class="absolute bottom-2 right-2 rounded-md bg-black/50 px-1.5 py-0.5 text-[10px] font-semibold text-white"
                            >
                                {{ video.duration }}
                            </span>
                        </div>

                        <div class="p-4">
                            <h3 class="mb-1 text-sm font-bold text-secondary">
                                {{ video.title }}
                            </h3>

                            <p class="text-xs leading-5 text-muted">
                                {{ video.description }}
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <!-- <section>
                <div class="mb-8">
                    <p
                        class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                    >
                        Quick Access
                    </p>

                    <h2 class="text-2xl font-bold text-secondary">
                        Helpful Links
                    </h2>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <NuxtLink
                        v-for="link in helpfulLinks"
                        :key="link.title"
                        :to="link.to ?? '#'"
                        class="rounded-2xl border border-gray-200 bg-white p-5 transition-colors hover:border-primary/25"
                    >
                        <div
                            class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl"
                            :class="link.tint"
                        >
                            <component :is="link.icon" class="h-5 w-5" />
                        </div>

                        <h3 class="mb-1 text-sm font-bold text-secondary">
                            {{ link.title }}
                        </h3>

                        <p class="mb-4 text-xs leading-5 text-muted">
                            {{ link.description }}
                        </p>

                        <span
                            class="inline-flex items-center gap-1.5 text-xs font-semibold"
                            :class="link.accent"
                        >
                            {{ link.cta }}

                            <ArrowRight class="h-3 w-3" />
                        </span>
                    </NuxtLink>
                </div>
            </section> -->
        </main>

        <section
            class="relative overflow-hidden bg-light/50 px-6 py-14 sm:px-[6%]"
        >
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-28 bg-gradient-to-b from-white to-transparent"
            />

            <div
                class="mx-auto flex max-w-6xl flex-col items-start justify-between gap-8 rounded-3xl border border-gray-200 bg-white p-8 md:flex-row md:items-center md:p-10"
            >
                <div>
                    <h2 class="mb-2 text-2xl font-bold text-secondary">
                        Need more help?
                    </h2>

                    <p class="mb-6 max-w-md text-sm leading-6 text-muted">
                        Our support team is here for you 24/7 — reach out
                        anytime.
                    </p>

                    <div
                        class="flex flex-wrap gap-x-8 gap-y-3 text-sm text-muted"
                    >
                        <div class="flex items-center gap-2">
                            <Phone class="h-4 w-4 text-primary" />
                            +63 912 345 6789
                        </div>

                        <div class="flex items-center gap-2">
                            <Mail class="h-4 w-4 text-primary" />
                            support@amuma.com
                        </div>
                    </div>
                </div>

                <NuxtLink
                    to="/company"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-600"
                >
                    Contact Support

                    <ArrowRight class="h-4 w-4" />
                </NuxtLink>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import dashboardImg from "~/assets/images/dashboard-preview.jpg";
import {
    Bookmark,
    ArrowRight,
    CheckCircle2,
    ChevronDown,
    FileText,
    Download,
    Play,
    Clock,
    Phone,
    Mail,
    Headphones,
    Heart,
    Building2,
    Users,
    LayoutDashboard,
    CreditCard,
    Calendar,
    MessageCircleQuestion,
} from "lucide-vue-next";

const openFaq = ref<number | null>(0);

const featuredResources = [
    {
        icon: Heart,
        title: "Homecare Guide",
        description:
            "Search for a provider and book in-home caregiver visits — scheduling, patient details, and care assessment in one flow.",
        cta: "Read Guide",
        tint: "bg-primary-50 text-primary",
        link: "text-primary",
        to: "/booking/search",
    },
    {
        icon: Building2,
        title: "Facility Guide",
        description:
            "A complete guide to facility care — admission, room assignment, billing cycles, and discharge procedures.",
        cta: "View Resource",
        tint: "bg-accent-50 text-accent",
        link: "text-accent",
        to: "/booking/search",
    },
    {
        icon: LayoutDashboard,
        title: "Branch Guide",
        description:
            "For branch owners — manage patients, staff scheduling, billing, and subscriptions from one dashboard.",
        cta: "Open Guide",
        tint: "bg-primary-50 text-primary",
        link: "text-primary",
        to: "/product",
    },
];

const categories = [
    {
        icon: Calendar,
        title: "Booking & Scheduling",
        items: [
            "Search & Compare Providers",
            "Homecare vs Facility Booking",
            "Patient & Guardian Details",
            "Different type of services",
        ],
        cta: "Explore Booking",
        tint: "bg-primary-50 text-primary",
        accent: "text-primary",
        to: "/booking/search",
    },
    {
        icon: Users,
        title: "Family Portal",
        items: [
            "Monitoring Schedules",
            "Messaging Caregivers",
            "Managing Medications",
            "Tracking Balance & Invoices",
        ],
        cta: "Open Family Portal",
        tint: "bg-accent-50 text-accent",
        accent: "text-accent",
        to: "/portal/overview",
    },
    {
        icon: Building2,
        title: "Homecare, Facility & Branch Management",
        items: [
            "Patient Admissions & Homecare Visits",
            "Room & Bed Assignment",
            "Staff Scheduling",
            "CCTV Access Policy & QR-Based Attendance",
        ],
        cta: "View Facility Tools",
        tint: "bg-primary-50 text-primary",
        accent: "text-primary",
        to: "/product",
    },
    {
        icon: CreditCard,
        title: "Billing & Subscriptions",
        items: [
            "Choosing a Plan",
            "Monthly vs Yearly Billing",
            "3D Secure Payments",
            "Viewing Invoices",
        ],
        cta: "View Plans",
        tint: "bg-accent-50 text-accent",
        accent: "text-accent",
        to: "/product",
    },
];

const faqs = [
    {
        q: "How do I book homecare or facility care?",
        a: "Search for a provider by location, choose homecare or facility, then complete the booking form with patient, guardian, and assessment details before submitting.",
    },
    {
        q: "How do I access the Family Portal?",
        a: "Sign in with your account to view your loved one's schedule, medications, messages, and balance from the Family Portal.",
    },
    {
        q: "How is billing and subscription handled?",
        a: "Branches subscribe to a monthly or yearly plan, and payments are processed securely with 3D Secure verification.",
    },
    {
        q: "Can I message my caregiver or branch directly?",
        a: "Yes — both the Family Portal and the branch dashboard include real-time messaging with your assigned caregiver or branch.",
    },
    {
        q: "How do I get notified about updates?",
        a: "You'll receive real-time notifications for booking updates, messages, and schedule changes as they happen.",
    },
];

const downloads = [
    {
        title: "Patient Admission Form",
        size: "245 KB",
        tint: "bg-primary-50 text-primary",
    },
    {
        title: "Assessment Form Guide",
        size: "1.2 MB",
        tint: "bg-accent-50 text-accent",
    },
    {
        title: "Family Portal Manual",
        size: "1.5 MB",
        tint: "bg-primary-50 text-primary",
    },
    {
        title: "Homecare Service Brochure",
        size: "3.4 MB",
        tint: "bg-accent-50 text-accent",
    },
];

const videos = [
    {
        title: "Booking Homecare or Facility Care",
        description:
            "A walkthrough of search, provider selection, and submitting your request.",
        duration: "6:24",
        gradient: "from-primary-500 to-primary-700",
    },
    {
        title: "Using the Family Portal",
        description:
            "Schedules, medications, messages, and balance in one place.",
        duration: "6:35",
        gradient: "from-accent-500 to-accent-700",
    },
    {
        title: "Managing Patients & Admissions",
        description:
            "Admitting patients and managing records from the branch dashboard.",
        duration: "4:38",
        gradient: "from-primary-500 to-primary-700",
    },
    {
        title: "Billing, Plans & Payments",
        description: "Choosing a plan and completing a secure checkout.",
        duration: "5:22",
        gradient: "from-accent-500 to-accent-700",
    },
];

const helpfulLinks = [
    {
        icon: Heart,
        title: "Book a Service",
        description: "Search providers and book homecare or facility care.",
        cta: "Go to Booking",
        to: "/booking/search",
        tint: "bg-primary-50 text-primary",
        accent: "text-primary",
    },
    {
        icon: Users,
        title: "Family Portal",
        description:
            "View schedules, medications, and messages for your loved one.",
        cta: "Open Portal",
        to: "/portal/overview",
        tint: "bg-accent-50 text-accent",
        accent: "text-accent",
    },
    {
        icon: CreditCard,
        title: "Plans & Pricing",
        description: "Compare AMUMA's homecare, facility, and hybrid plans.",
        cta: "View Plans",
        to: "/product",
        tint: "bg-primary-50 text-primary",
        accent: "text-primary",
    },
    {
        icon: Headphones,
        title: "Contact Support",
        description: "Get help from our support team anytime.",
        cta: "Contact Now",
        to: "/company",
        tint: "bg-accent-50 text-accent",
        accent: "text-accent",
    },
];
</script>
