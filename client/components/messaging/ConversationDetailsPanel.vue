<script setup lang="ts">
import { computed, ref, watch } from "vue";
import {
    X,
    Mail,
    Phone,
    Building2,
    Users,
    CalendarClock,
    LoaderCircle,
    Home,
    BedDouble,
    Pin,
    FileText,
    Download,
} from "lucide-vue-next";

import MessageAvatar from "~/components/messaging/MessageAvatar.vue";

import type { ConversationDetails, MessageSender } from "~/types/message";

const props = defineProps<{
    open: boolean;
    loading?: boolean;
    error?: string;
    details: ConversationDetails | null;
    viewer: MessageSender;
}>();

defineEmits<{
    close: [];
    retry: [];
    jump: [messageId: number];
    image: [url: string];
    download: [messageId: number];
}>();

const isFamily = computed(() => props.details?.kind === "family");

const patients = computed(() => props.details?.patients ?? []);
const today = computed(() => props.details?.today ?? []);

const person = computed(() => props.details?.person ?? null);

const pinned = computed(() => props.details?.pinned ?? []);
const media = computed(() => props.details?.media ?? []);
const images = computed(() => media.value.filter((item) => item.type === "image"));
const files = computed(() => media.value.filter((item) => item.type !== "image"));

const mediaTab = ref<"media" | "files">("media");
const showAllMedia = ref(false);
const MEDIA_PREVIEW = 9;

const visibleImages = computed(() =>
    showAllMedia.value ? images.value : images.value.slice(0, MEDIA_PREVIEW),
);

function formatDay(value: string | null) {
    if (!value) return "";

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return "";

    return parsed.toLocaleDateString("en-PH", { month: "short", day: "numeric" });
}

const otherName = computed(() => person.value?.name ?? "Them");

const mediaTabs = computed(() => [
    { value: "media" as const, label: "Media", count: images.value.length },
    { value: "files" as const, label: "Files", count: files.value.length },
]);

const todayHeading = computed(() =>
    props.viewer === "client" ? "With your loved one today" : "Assigned today",
);

const todayEmpty = computed(() =>
    props.viewer === "client"
        ? "Not scheduled with your loved one today."
        : "No patients assigned today.",
);

const todayLabel = computed(() => {
    const value = props.details?.date;

    if (!value) return "";

    const parsed = new Date(`${value}T00:00:00`);

    if (Number.isNaN(parsed.getTime())) return "";

    return parsed.toLocaleDateString("en-PH", {
        weekday: "short",
        month: "short",
        day: "numeric",
    });
});

// Large avatar (MessageAvatar only has small sizes).
const avatarFailed = ref(false);

watch(
    () => props.details?.person?.name,
    () => {
        showAllMedia.value = false;
        mediaTab.value = "media";
    },
);

watch(
    () => person.value?.avatar,
    () => {
        avatarFailed.value = false;
    },
);

const showAvatar = computed(() => {
    const src = person.value?.avatar;

    return !!src && !avatarFailed.value && !src.includes("ui-avatars.com");
});

const initials = computed(() => {
    const parts = (person.value?.name ?? "")
        .split(" ")
        .map((part) => part.trim())
        .filter(Boolean);

    if (!parts.length) return "?";

    return parts
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? "")
        .join("");
});

// A masked email ("pr•••@gmail.com") can't be opened in a mail app.
const emailHref = computed(() => {
    const email = person.value?.email;

    return email && !email.includes("•") ? `mailto:${email}` : null;
});

const contacts = computed(() => {
    const value = person.value;

    if (!value) return [];

    return [
        value.email && {
            key: "email",
            icon: Mail,
            label: "Email",
            text: value.email,
            href: emailHref.value,
        },
        value.phone && {
            key: "phone",
            icon: Phone,
            label: "Phone",
            text: formatPhone(value.phone),
            href: `tel:${value.phone}`,
        },
        props.details?.branch && {
            key: "branch",
            icon: Building2,
            label: "Branch",
            text: props.details.branch,
            href: null,
        },
    ].filter(Boolean) as {
        key: string;
        icon: any;
        label: string;
        text: string;
        href: string | null;
    }[];
});

function formatTime(value: string | null) {
    if (!value) return "";

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) return "";

    return parsed.toLocaleTimeString("en-PH", {
        hour: "numeric",
        minute: "2-digit",
    });
}

function when(item: { time: string | null; shift: string | null }) {
    if (item.time) return formatTime(item.time);
    if (item.shift) return `Shift ${item.shift}`;

    return "";
}

const STATUS_STYLE: Record<string, { pill: string; bar: string }> = {
    pending: {
        pill: "bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300",
        bar: "bg-amber-400",
    },
    ongoing: {
        pill: "bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300",
        bar: "bg-sky-500",
    },
    completed: {
        pill: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300",
        bar: "bg-emerald-500",
    },
};

const FALLBACK_STYLE = {
    pill: "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300",
    bar: "bg-primary",
};

const styleFor = (status: string | null) =>
    STATUS_STYLE[(status ?? "").toLowerCase()] ?? FALLBACK_STYLE;
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-x-6 opacity-0"
        leave-active-class="transition duration-150 ease-in"
        leave-to-class="translate-x-6 opacity-0"
    >
        <aside
            v-if="open"
            class="absolute inset-y-0 right-0 z-20 flex w-full flex-col bg-white shadow-2xl sm:w-[340px] lg:static lg:z-auto lg:w-[320px] lg:shrink-0 lg:border-l lg:border-slate-200 lg:shadow-none dark:bg-secondary dark:lg:border-white/10"
            aria-label="Conversation details"
        >
            <button
                type="button"
                aria-label="Close details"
                class="absolute right-3 top-3 z-10 rounded-full bg-white/80 p-1.5 text-slate-500 backdrop-blur transition hover:bg-white hover:text-slate-800 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20"
                @click="$emit('close')"
            >
                <X class="h-4 w-4" />
            </button>

            <div
                v-if="loading"
                class="flex flex-1 items-center justify-center text-slate-400"
            >
                <LoaderCircle class="h-5 w-5 animate-spin" />
            </div>

            <div
                v-else-if="error || !details || !person"
                class="flex flex-1 flex-col items-center justify-center gap-3 px-6 text-center"
            >
                <p class="text-sm text-slate-500 dark:text-gray-400">
                    {{ error || "No details to show." }}
                </p>

                <button
                    type="button"
                    class="rounded-xl border border-primary px-4 py-1.5 text-xs font-bold text-primary transition hover:bg-primary hover:text-white"
                    @click="$emit('retry')"
                >
                    Try again
                </button>
            </div>

            <div v-else class="min-h-0 flex-1 overflow-y-auto">
                <div
                    class="h-24 bg-gradient-to-br from-primary-100 via-sky-50 to-white dark:from-primary-500/20 dark:via-white/5 dark:to-transparent"
                />

                <div class="-mt-12 flex flex-col items-center px-5 text-center">
                    <div
                        class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-full bg-primary-50 text-2xl font-bold text-primary-700 shadow-md ring-4 ring-white dark:bg-primary-500/20 dark:text-primary-200 dark:ring-secondary"
                    >
                        <img
                            v-if="showAvatar"
                            :src="person.avatar as string"
                            :alt="person.name ?? ''"
                            class="h-full w-full object-cover"
                            @error="avatarFailed = true"
                        />

                        <span v-else>{{ initials }}</span>
                    </div>

                    <p
                        class="mt-3 text-lg font-bold leading-tight text-slate-800 dark:text-white"
                    >
                        {{ person.name ?? "Branch team" }}
                    </p>

                    <span
                        v-if="person.role"
                        class="mt-1.5 rounded-full bg-primary-50 px-3 py-0.5 text-[11px] font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-200"
                    >
                        {{ person.role }}
                    </span>
                </div>

                <div class="space-y-5 p-5">
                    <section v-if="contacts.length">
                        <h3
                            class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                        >
                            Contact
                        </h3>

                        <ul
                            class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-100 dark:divide-white/5 dark:border-white/10"
                        >
                            <li v-for="row in contacts" :key="row.key">
                                <component
                                    :is="row.href ? 'a' : 'div'"
                                    :href="row.href ?? undefined"
                                    class="flex items-center gap-3 px-3 py-2.5 transition"
                                    :class="
                                        row.href
                                            ? 'hover:bg-slate-50 dark:hover:bg-white/5'
                                            : ''
                                    "
                                >
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary dark:bg-primary-500/15"
                                    >
                                        <component :is="row.icon" class="h-4 w-4" />
                                    </span>

                                    <span class="min-w-0">
                                        <span
                                            class="block text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                        >
                                            {{ row.label }}
                                        </span>

                                        <span
                                            class="block break-all text-sm font-medium text-slate-700 dark:text-gray-100"
                                        >
                                            {{ row.text }}
                                        </span>
                                    </span>
                                </component>
                            </li>
                        </ul>
                    </section>

                    <section v-if="isFamily">
                        <div class="mb-2 flex items-center justify-between">
                            <h3
                                class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                            >
                                <Users class="h-3.5 w-3.5" />
                                Patients
                            </h3>

                            <span
                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 dark:bg-white/10 dark:text-gray-300"
                            >
                                {{ patients.length }}
                            </span>
                        </div>

                        <p
                            v-if="!patients.length"
                            class="rounded-2xl border border-dashed border-slate-200 px-3 py-5 text-center text-xs text-slate-400 dark:border-white/10"
                        >
                            No patients linked to this family member.
                        </p>

                        <ul v-else class="space-y-2">
                            <li
                                v-for="patient in patients"
                                :key="patient.patient_id"
                                class="flex items-center gap-3 rounded-2xl border border-slate-100 bg-white p-2.5 transition hover:border-primary-200 hover:bg-primary-50/40 dark:border-white/10 dark:bg-white/[0.03] dark:hover:bg-white/5"
                            >
                                <MessageAvatar
                                    :src="patient.avatar"
                                    :name="patient.name"
                                    size="md"
                                />

                                <span
                                    class="min-w-0 truncate text-sm font-semibold text-slate-700 dark:text-gray-100"
                                >
                                    {{ patient.name }}
                                </span>
                            </li>
                        </ul>
                    </section>

                    <section v-else>
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <h3
                                class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                            >
                                <CalendarClock class="h-3.5 w-3.5" />
                                {{ todayHeading }}
                            </h3>

                            <span
                                class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 dark:bg-white/10 dark:text-gray-300"
                            >
                                {{ todayLabel || today.length }}
                            </span>
                        </div>

                        <p
                            v-if="!today.length"
                            class="rounded-2xl border border-dashed border-slate-200 px-3 py-5 text-center text-xs text-slate-400 dark:border-white/10"
                        >
                            {{ todayEmpty }}
                        </p>

                        <ul v-else class="space-y-2">
                            <li
                                v-for="(item, index) in today"
                                :key="`${item.patient_id}-${index}`"
                                class="relative overflow-hidden rounded-2xl border border-slate-100 bg-white py-2.5 pl-4 pr-3 dark:border-white/10 dark:bg-white/[0.03]"
                            >
                                <span
                                    class="absolute inset-y-0 left-0 w-1"
                                    :class="styleFor(item.status).bar"
                                />

                                <div class="flex items-center justify-between gap-2">
                                    <span
                                        class="min-w-0 truncate text-sm font-semibold text-slate-700 dark:text-gray-100"
                                    >
                                        {{ item.name }}
                                    </span>

                                    <span
                                        v-if="item.status"
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize"
                                        :class="styleFor(item.status).pill"
                                    >
                                        {{ item.status }}
                                    </span>
                                </div>

                                <p
                                    class="mt-1 flex items-center gap-1.5 text-[11px] text-slate-400"
                                >
                                    <template v-if="item.category === 'Facility'">
                                        <BedDouble class="h-3 w-3" />
                                    </template>
                                    <template v-else-if="item.category">
                                        <Home class="h-3 w-3" />
                                    </template>

                                    <span v-if="when(item)">{{ when(item) }}</span>
                                    <span v-if="when(item) && item.category">·</span>
                                    <span v-if="item.category">
                                        {{ item.category }}
                                    </span>
                                </p>
                            </li>
                        </ul>
                    </section>

                    <section v-if="pinned.length">
                        <div class="mb-2 flex items-center justify-between">
                            <h3
                                class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                            >
                                <Pin class="h-3.5 w-3.5" />
                                Pinned
                            </h3>

                            <span
                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-600 dark:bg-white/10 dark:text-gray-300"
                            >
                                {{ pinned.length }}
                            </span>
                        </div>

                        <ul class="space-y-2">
                            <li v-for="item in pinned" :key="item.message_id">
                                <button
                                    type="button"
                                    class="w-full rounded-2xl border border-slate-100 bg-white px-3 py-2.5 text-left transition hover:border-primary-200 hover:bg-primary-50/40 dark:border-white/10 dark:bg-white/[0.03] dark:hover:bg-white/5"
                                    @click="$emit('jump', item.message_id)"
                                >
                                    <span
                                        class="line-clamp-2 text-sm text-slate-700 dark:text-gray-100"
                                    >
                                        {{ item.text ?? "Attachment" }}
                                    </span>

                                    <span
                                        class="mt-1 block text-[11px] text-slate-400"
                                    >
                                        {{ item.is_mine ? "You" : otherName }}
                                        · {{ formatDay(item.created_at) }}
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </section>

                    <section>
                        <div
                            class="mb-3 inline-flex w-full rounded-xl bg-slate-100 p-1 dark:bg-white/10"
                        >
                            <button
                                v-for="tab in mediaTabs"
                                :key="tab.value"
                                type="button"
                                class="flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                :class="
                                    mediaTab === tab.value
                                        ? 'bg-white text-primary shadow-sm dark:bg-secondary'
                                        : 'text-slate-500 hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'
                                "
                                @click="mediaTab = tab.value"
                            >
                                {{ tab.label }}

                                <span class="text-[10px] opacity-70">
                                    {{ tab.count }}
                                </span>
                            </button>
                        </div>

                        <template v-if="mediaTab === 'media'">
                            <p
                                v-if="!images.length"
                                class="rounded-2xl border border-dashed border-slate-200 px-3 py-5 text-center text-xs text-slate-400 dark:border-white/10"
                            >
                                No photos shared yet.
                            </p>

                            <template v-else>
                                <div class="grid grid-cols-3 gap-1.5">
                                    <button
                                        v-for="item in visibleImages"
                                        :key="item.message_id"
                                        type="button"
                                        class="aspect-square overflow-hidden rounded-xl bg-slate-100 transition hover:opacity-90 dark:bg-white/10"
                                        :title="item.name ?? 'Photo'"
                                        @click="$emit('image', item.url)"
                                    >
                                        <img
                                            :src="item.url"
                                            :alt="item.name ?? 'Photo'"
                                            loading="lazy"
                                            class="h-full w-full object-cover"
                                        />
                                    </button>
                                </div>

                                <button
                                    v-if="images.length > MEDIA_PREVIEW"
                                    type="button"
                                    class="mt-2 w-full rounded-xl py-1.5 text-xs font-semibold text-primary transition hover:bg-primary-50 dark:hover:bg-white/5"
                                    @click="showAllMedia = !showAllMedia"
                                >
                                    {{
                                        showAllMedia
                                            ? "Show less"
                                            : `Show all (${images.length})`
                                    }}
                                </button>
                            </template>
                        </template>

                        <template v-else>
                            <p
                                v-if="!files.length"
                                class="rounded-2xl border border-dashed border-slate-200 px-3 py-5 text-center text-xs text-slate-400 dark:border-white/10"
                            >
                                No files shared yet.
                            </p>

                            <ul v-else class="space-y-2">
                                <li v-for="item in files" :key="item.message_id">
                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-3 rounded-2xl border border-slate-100 bg-white p-2.5 text-left transition hover:border-primary-200 hover:bg-primary-50/40 dark:border-white/10 dark:bg-white/[0.03] dark:hover:bg-white/5"
                                        @click="$emit('download', item.message_id)"
                                    >
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary dark:bg-primary-500/15"
                                        >
                                            <FileText class="h-4 w-4" />
                                        </span>

                                        <span class="min-w-0 flex-1">
                                            <span
                                                class="block truncate text-sm font-medium text-slate-700 dark:text-gray-100"
                                            >
                                                {{ item.name ?? "Document.pdf" }}
                                            </span>

                                            <span
                                                class="block text-[11px] text-slate-400"
                                            >
                                                {{ item.is_mine ? "You" : otherName }}
                                                · {{ formatDay(item.created_at) }}
                                            </span>
                                        </span>

                                        <Download
                                            class="h-4 w-4 shrink-0 text-slate-400"
                                        />
                                    </button>
                                </li>
                            </ul>
                        </template>
                    </section>
                </div>
            </div>
        </aside>
    </Transition>
</template>