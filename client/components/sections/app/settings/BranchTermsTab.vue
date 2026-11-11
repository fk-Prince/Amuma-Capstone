<template>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            <div class="grid gap-2 sm:grid-cols-2" role="group" aria-label="Which terms">
                <button
                    v-for="item in DOCS"
                    :key="item.key"
                    type="button"
                    :aria-pressed="item.key === doc"
                    class="flex items-start gap-3 rounded-xl border px-4 py-3 text-left transition"
                    :class="
                        item.key === doc
                            ? 'border-primary bg-primary-50/60 ring-1 ring-primary/20 dark:bg-primary-500/10'
                            : 'border-slate-200 hover:border-primary-200 dark:border-white/10'
                    "
                    @click="switchDoc(item.key)"
                >
                    <span class="min-w-0 flex-1">
                        <span
                            class="flex items-center gap-2 text-sm font-semibold"
                            :class="item.key === doc ? 'text-primary dark:text-primary-300' : 'text-slate-800 dark:text-white'"
                        >
                            {{ item.name }}
                            <span
                                v-if="docDirty(item.key)"
                                class="h-1.5 w-1.5 rounded-full bg-amber-500"
                                title="Unsaved changes"
                            />
                        </span>
                        <span class="mt-0.5 block text-xs text-slate-500 dark:text-gray-400">
                            {{ item.hint }}
                        </span>
                    </span>
                    <span
                        class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:bg-white/10 dark:text-gray-400"
                    >
                        {{ branchCount(item.key) }} yours
                    </span>
                </button>
            </div>

            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                    {{ current.name }}
                </h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                    {{ current.intro }}
                </p>
            </div>

            <section class="space-y-3">
                <div
                    class="flex items-center justify-between gap-3 border-b border-slate-200 pb-2 dark:border-white/10"
                >
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-white">
                            Your branch policies
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">
                            {{ current.policiesHint }}
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span
                            class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500 dark:bg-white/10 dark:text-gray-400"
                        >
                            {{ branchCount(doc) }} of {{ BRANCH_TERMS_MAX_SECTIONS }}
                        </span>
                        <button
                            v-if="canEdit"
                            type="button"
                            :disabled="loading || !data || branchCount(doc) >= BRANCH_TERMS_MAX_SECTIONS"
                            :title="
                                branchCount(doc) >= BRANCH_TERMS_MAX_SECTIONS
                                    ? `You've reached ${BRANCH_TERMS_MAX_SECTIONS} sections. Remove one to add another.`
                                    : ''
                            "
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="openEditor(null)"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            Add section
                        </button>
                    </div>
                </div>

                <div
                    v-if="loading"
                    class="h-24 animate-pulse rounded-xl bg-slate-100 dark:bg-white/5"
                />

                <ul
                    v-else
                    class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 dark:divide-white/10 dark:border-white/10"
                >
                    <li
                        v-if="!branchCount(doc)"
                        class="px-4 py-6 text-center text-sm text-slate-400 dark:text-gray-500"
                    >
                        No branch policies yet. Families only see AMUMA's sections.
                        <template v-if="canEdit">Use Add section to write your first one.</template>
                    </li>

                    <template v-for="(item, index) in order" :key="index">
                        <li v-if="item.kind === 'branch'" class="flex items-start gap-3 px-4 py-3">
                            <span
                                class="mt-0.5 flex h-6 min-w-6 shrink-0 items-center justify-center rounded-md bg-primary-50 px-1 text-xs font-bold text-primary dark:bg-primary-500/15 dark:text-primary-300"
                            >
                                {{ index + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-white">
                                    {{ item.section.title }}
                                </p>
                                <p class="mt-0.5 line-clamp-1 text-xs text-slate-500 dark:text-gray-400">
                                    {{ item.section.body }}
                                </p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                    <template v-if="current.typed">
                                        <span
                                            v-for="type in item.section.show_on ?? []"
                                            :key="type"
                                            class="rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                                        >
                                            {{ TYPE_LABEL[type] }}
                                        </span>
                                    </template>
                                    <span
                                        v-else
                                        class="rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                                    >
                                        Every portal payment
                                    </span>
                                    <span class="truncate text-[11px] text-slate-400 dark:text-gray-500">
                                        after “{{ itemTitle(order[index - 1]) }}”
                                    </span>
                                </div>
                            </div>
                            <div v-if="canEdit" class="flex shrink-0 items-center gap-0.5">
                                <button
                                    v-for="action in rowActions(index)"
                                    :key="action.key"
                                    type="button"
                                    :disabled="action.disabled"
                                    :aria-label="action.label"
                                    :title="action.label"
                                    class="rounded-lg p-1.5 text-slate-400 transition disabled:opacity-30"
                                    :class="
                                        action.key === 'remove'
                                            ? 'hover:bg-rose-50 hover:text-rose-500 dark:hover:bg-rose-500/10'
                                            : 'hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-white/10 dark:hover:text-gray-200'
                                    "
                                    @click="action.run()"
                                >
                                    <component :is="action.icon" class="h-4 w-4" />
                                </button>
                            </div>
                        </li>
                    </template>
                </ul>

                <p class="flex items-start gap-2 text-xs leading-5 text-slate-400 dark:text-gray-500">
                    <Lock class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                    <span>
                        Your sections are marked “Branch policy” in the terms, and families
                        always see: “If one conflicts with a section set by AMUMA, AMUMA's
                        section applies.”
                    </span>
                </p>
            </section>

            <section class="space-y-3">
                <div
                    class="flex items-center justify-between gap-3 border-b border-slate-200 pb-2 dark:border-white/10"
                >
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800 dark:text-white">
                            Required by AMUMA
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">
                            These match how the system works, so they can't be edited.
                        </p>
                    </div>
                    <span
                        class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500 dark:bg-white/10 dark:text-gray-400"
                    >
                        {{ lockedSections.length }} sections
                    </span>
                </div>

                <div
                    class="flex items-start gap-2.5 rounded-xl border border-primary-100 bg-primary-50/60 px-3.5 py-2.5 text-xs leading-5 text-primary-700 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-200"
                >
                    <Info class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{{ current.lockedNote }}</span>
                </div>

                <ul
                    class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 dark:divide-white/10 dark:border-white/10"
                >
                    <template v-for="(item, index) in order" :key="index">
                        <li v-if="item.kind === 'locked'">
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-slate-50 dark:hover:bg-white/5"
                                :aria-expanded="openLocked === item.section.key"
                                @click="openLocked = openLocked === item.section.key ? null : item.section.key ?? null"
                            >
                                <span
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-gray-500"
                                >
                                    <Lock class="h-3.5 w-3.5" />
                                </span>
                                <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-700 dark:text-gray-200">
                                    {{ index + 1 }}. {{ item.section.title }}
                                </span>
                                <span
                                    class="hidden shrink-0 text-[11px] font-semibold uppercase tracking-wide text-slate-400 sm:inline dark:text-gray-500"
                                >
                                    {{ index === 0 ? "Always first" : "Set by AMUMA" }}
                                </span>
                                <ChevronDown
                                    class="h-4 w-4 shrink-0 text-slate-400 transition"
                                    :class="openLocked === item.section.key ? 'rotate-180' : ''"
                                />
                            </button>
                            <div
                                v-if="openLocked === item.section.key"
                                class="bg-slate-50/70 px-4 pb-3 pl-[3.25rem] text-xs leading-5 text-slate-500 dark:bg-white/[0.03] dark:text-gray-400"
                            >
                                <template v-for="(block, blockIndex) in filledLocked(item.section).blocks" :key="blockIndex">
                                    <p v-if="block.type === 'paragraph'" class="mt-2 first:mt-0">
                                        {{ block.text }}
                                    </p>
                                    <ul v-else class="mt-2 list-disc space-y-1 pl-5">
                                        <li v-for="line in block.items" :key="line">{{ line }}</li>
                                    </ul>
                                </template>
                            </div>
                        </li>
                    </template>
                </ul>
            </section>
        </div>

        <aside class="min-w-0 xl:sticky xl:top-4 xl:self-start">
            <div class="mb-2 flex min-h-[28px] flex-wrap items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500">
                    {{ current.typed ? "What families see on" : "What families see" }}
                </p>
                <div
                    v-if="current.typed"
                    class="inline-flex rounded-lg bg-slate-100 p-0.5 text-xs dark:bg-white/10"
                    role="group"
                    aria-label="Booking type"
                >
                    <button
                        v-for="type in BOOKING_TERMS_TYPES"
                        :key="type.value"
                        type="button"
                        :aria-pressed="previewType === type.value"
                        class="rounded-md px-2.5 py-1 font-semibold transition"
                        :class="
                            previewType === type.value
                                ? 'bg-white text-primary shadow-sm dark:bg-secondary dark:text-primary-300'
                                : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                        @click="previewType = type.value"
                    >
                        {{ type.label }}
                        <span class="font-normal opacity-70">{{ typeCount(type.value) }}</span>
                    </button>
                </div>
            </div>

            <div
                class="flex max-h-[min(78vh,760px)] flex-col overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-black/5 dark:bg-secondary-800 dark:ring-white/10"
            >
                <div class="shrink-0 border-b border-slate-100 px-6 py-5 dark:border-white/10">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                        {{ preview?.heading ?? current.name }}
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                        <template v-if="preview?.branchName">{{ preview.branchName }} · </template>
                        Last updated {{ preview?.lastUpdated }}
                    </p>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                    <PaymentTermsBody v-if="preview" :terms="preview" />
                </div>
            </div>
        </aside>

        <TermsSectionModal
            v-if="editor"
            :doc-name="current.name"
            :typed="current.typed"
            :section="editor.index === null ? null : (order[editor.index]?.section as BranchTermsSection)"
            :position="editor.index === null ? order.length + 1 : editor.index + 1"
            :positions="positionOptions"
            :default-type="previewType"
            @close="editor = null"
            @save="saveSection"
        />

        <ConfirmDialog
            :open="removing !== null"
            title="Remove this section?"
            :message="removeMessage"
            confirm-label="Remove"
            cancel-label="Keep it"
            variant="danger"
            @cancel="removing = null"
            @confirm="removeSection"
        />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { ChevronDown, ChevronUp, Info, Lock, Pencil, Plus, Trash2 } from "lucide-vue-next";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import PaymentTermsBody from "~/components/ui/PaymentTermsBody.vue";
import TermsSectionModal from "~/components/sections/app/settings/TermsSectionModal.vue";
import { branchSettingService } from "~/api/branch-setting/BranchSettingService";
import { loadBranchTerms } from "~/composables/useBranchTerms";
import { usePermissions } from "~/composables/usePermission";
import { useToast } from "~/composables/useToast";
import { Modules } from "~/types/module";
import {
    BOOKING_TERMS_TYPES,
    BRANCH_TERMS_MAX_SECTIONS,
    branchSectionsFromOrder,
    buildPaymentTerms,
    lockedTermsSections,
    mergeTermsOrder,
    termsContextFor,
    type BookingTermsType,
    type BranchTermsData,
    type BranchTermsDocKey,
    type BranchTermsSection,
    type PaymentTermsSection,
    type TermsOrderItem,
} from "~/utils/paymentTerms";

const props = defineProps<{
    uuid: string;
}>();

const { success, error } = useToast();
const { canUpdate } = usePermissions();
const canEdit = computed(() => canUpdate(Modules.BranchSettings));

const DOCS: {
    key: BranchTermsDocKey;
    name: string;
    hint: string;
    intro: string;
    policiesHint: string;
    lockedNote: string;
    typed: boolean;
}[] = [
    {
        key: "booking_terms",
        name: "Booking & Payment Terms",
        hint: "Shown when families book",
        intro: "Families agree to these before they submit a booking. AMUMA's sections are the same for every branch; your own policies go between or after them.",
        policiesHint: `Pick where each section goes and which bookings it shows on. Section 1 always stays AMUMA's. Up to ${BRANCH_TERMS_MAX_SECTIONS} sections.`,
        lockedNote: "Your reservation fee, homecare downpayment and the share of the bill that must be paid before a branch discharge are filled in from Operation Settings. Change them there and the terms update.",
        typed: true,
    },
    {
        key: "balance_terms",
        name: "Online Payment Terms",
        hint: "Shown when families pay a balance in the portal",
        intro: "Families agree to these each time they pay a balance in the family portal. AMUMA's sections are the same for every branch; your own payment policies go between or after them.",
        policiesHint: `Pick where each section goes. Shown on every portal payment. Section 1 always stays AMUMA's. Up to ${BRANCH_TERMS_MAX_SECTIONS} sections.`,
        lockedNote: "The early-discharge credit rule (half of a month within 14 days, half of a year within 6 months) is how the system works for every branch, so it is part of AMUMA's sections.",
        typed: false,
    },
];

const TYPE_LABEL = Object.fromEntries(
    BOOKING_TERMS_TYPES.map((type) => [type.value, type.label]),
) as Record<BookingTermsType, string>;

const doc = ref<BranchTermsDocKey>("booking_terms");
const data = ref<BranchTermsData | null>(null);
const loading = ref(true);
const saving = ref(false);
const previewType = ref<BookingTermsType>("homecare");
const openLocked = ref<string | null>(null);
const editor = ref<{ index: number | null } | null>(null);
const removing = ref<number | null>(null);

const orders = ref<Record<BranchTermsDocKey, TermsOrderItem[]>>({
    booking_terms: mergeTermsOrder(lockedTermsSections("booking_terms"), []),
    balance_terms: mergeTermsOrder(lockedTermsSections("balance_terms"), []),
});
const saved = ref<Record<BranchTermsDocKey, string>>({
    booking_terms: "[]",
    balance_terms: "[]",
});

const current = computed(() => DOCS.find((item) => item.key === doc.value)!);
const order = computed(() => orders.value[doc.value]);
const lockedSections = computed(() => lockedTermsSections(doc.value));

const sectionsOf = (key: BranchTermsDocKey) => branchSectionsFromOrder(orders.value[key]);
const branchCount = (key: BranchTermsDocKey) =>
    orders.value[key].filter((item) => item.kind === "branch").length;
const docDirty = (key: BranchTermsDocKey) =>
    JSON.stringify(sectionsOf(key)) !== saved.value[key];
const dirty = computed(() => DOCS.some((item) => docDirty(item.key)));

const typeCount = (type: BookingTermsType) =>
    sectionsOf("booking_terms").filter((section) => section.show_on?.includes(type)).length;

function itemTitle(item?: TermsOrderItem) {
    return item?.section.title ?? "";
}

function dataWith(key: BranchTermsDocKey, sections: BranchTermsSection[]): BranchTermsData | null {
    if (!data.value) return null;

    return {
        ...data.value,
        [key]: { updated_at: data.value[key]?.updated_at ?? null, sections },
    };
}

const preview = computed(() =>
    buildPaymentTerms(
        termsContextFor(doc.value),
        dataWith(doc.value, sectionsOf(doc.value)),
        current.value.typed ? previewType.value : null,
    ),
);

const filledLockedMap = computed(() => {
    const terms = buildPaymentTerms(termsContextFor(doc.value), dataWith(doc.value, []));
    return new Map((terms?.sections ?? []).map((section) => [section.key, section]));
});

function filledLocked(section: PaymentTermsSection) {
    return filledLockedMap.value.get(section.key) ?? section;
}

const positionOptions = computed(() => {
    const rest = order.value.filter((_, index) => index !== editor.value?.index);

    return rest.map((item, index) => ({
        value: index + 2,
        label: `${index + 2} · after “${itemTitle(item)}”`,
    }));
});

const removeMessage = computed(() => {
    const item = removing.value === null ? null : order.value[removing.value];

    return item
        ? `“${item.section.title}” will no longer show in your ${current.value.name} after you save. The sections after it move up by one.`
        : "";
});

function setOrder(next: TermsOrderItem[]) {
    orders.value = { ...orders.value, [doc.value]: next };
}

function move(index: number, by: number) {
    const next = [...order.value];
    const target = index + by;
    if (target < 1 || target >= next.length) return;

    const [moved] = next.splice(index, 1);
    if (moved) next.splice(target, 0, moved);
    setOrder(next);
}

function rowActions(index: number) {
    return [
        { key: "up", label: "Move up one", icon: ChevronUp, disabled: index <= 1, run: () => move(index, -1) },
        { key: "down", label: "Move down one", icon: ChevronDown, disabled: index >= order.value.length - 1, run: () => move(index, 1) },
        { key: "edit", label: "Edit section", icon: Pencil, disabled: false, run: () => openEditor(index) },
        { key: "remove", label: "Remove section", icon: Trash2, disabled: false, run: () => (removing.value = index) },
    ];
}

function openEditor(index: number | null) {
    editor.value = { index };
}

function saveSection(section: BranchTermsSection, position: number) {
    const next = [...order.value];
    if (editor.value?.index !== null && editor.value?.index !== undefined) {
        next.splice(editor.value.index, 1);
    }

    next.splice(Math.max(1, position - 1), 0, { kind: "branch", section });
    setOrder(next);
    editor.value = null;
}

function removeSection() {
    if (removing.value !== null) {
        const next = [...order.value];
        next.splice(removing.value, 1);
        setOrder(next);
    }

    removing.value = null;
}

function switchDoc(key: BranchTermsDocKey) {
    doc.value = key;
    openLocked.value = null;
}

function apply(terms: BranchTermsData | null) {
    data.value = terms;

    for (const item of DOCS) {
        const sections = terms?.[item.key]?.sections ?? [];
        orders.value[item.key] = mergeTermsOrder(lockedTermsSections(item.key), sections);
        saved.value[item.key] = JSON.stringify(sectionsOf(item.key));
    }
}

function reset() {
    apply(data.value);
}

async function save() {
    if (!dirty.value) return;

    saving.value = true;
    try {
        const payload: Record<string, unknown> = { branch_uuid: props.uuid };
        for (const item of DOCS) {
            if (docDirty(item.key)) payload[item.key] = { sections: sectionsOf(item.key) };
        }

        const res = await branchSettingService.update(props.uuid, payload);
        const fresh = await loadBranchTerms(props.uuid, true);
        if (fresh) apply(fresh);
        success(res?.message ?? "Terms saved.");
    } catch (err: any) {
        error(err?.message ?? "Could not save the terms.");
    } finally {
        saving.value = false;
    }
}

onMounted(async () => {
    const terms = await loadBranchTerms(props.uuid, true);
    if (terms) apply(terms);
    else error("Could not load this branch's terms. Refresh to try again.");
    loading.value = false;
});

defineExpose({ save, saving, dirty, reset });
</script>
