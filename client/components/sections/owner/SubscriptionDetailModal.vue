<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open && subscription"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    class="relative flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="`${agency.name} subscription`"
                >
                    <button
                        type="button"
                        aria-label="Close dialog"
                        class="absolute right-3 top-3 z-10 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        @click="emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>

                    <div
                        class="grid min-h-0 flex-1 grid-cols-1 overflow-y-auto md:grid-cols-[240px_minmax(0,1fr)] md:grid-rows-[auto_minmax(0,1fr)] md:overflow-hidden"
                    >
                        <div
                            class="flex items-center justify-center border-b border-slate-100 bg-slate-50/70 p-5 dark:border-white/10 dark:bg-white/5 md:border-r"
                        >
                            <div
                                class="flex aspect-square w-32 items-center justify-center overflow-hidden rounded-2xl bg-accent-50 text-accent ring-1 ring-slate-200 dark:bg-accent-500/10 dark:ring-white/10 md:w-full"
                            >
                                <img
                                    v-if="agency.image"
                                    :src="agency.image"
                                    :alt="agency.name"
                                    class="h-full w-full object-cover"
                                />
                                <Building2 v-else class="h-12 w-12" />
                            </div>
                        </div>

                        <div
                            class="min-w-0 border-b border-slate-100 p-5 pr-14 dark:border-white/10"
                        >
                            <p
                                class="text-[10px] font-semibold uppercase tracking-widest text-accent-600 dark:text-accent-300"
                            >
                                Agency
                            </p>

                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <h2
                                    class="truncate text-lg font-semibold text-secondary dark:text-white"
                                >
                                    {{ agency.name }}
                                </h2>

                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                                    :class="reviewBadge(agency.status).class"
                                >
                                    {{ reviewBadge(agency.status).label }}
                                </span>
                            </div>

                            <div
                                class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-muted dark:text-gray-400"
                            >
                                <span
                                    v-if="agency.email"
                                    class="flex items-center gap-1.5"
                                >
                                    <Mail class="h-3.5 w-3.5 text-accent" />
                                    {{ agency.email }}
                                </span>

                                <span
                                    v-if="agency.registered_by"
                                    class="flex items-center gap-1.5"
                                >
                                    <UserRound
                                        class="h-3.5 w-3.5 text-accent"
                                    />
                                    Registered by {{ agency.registered_by }}
                                </span>
                            </div>

                            <p
                                v-if="agency.address"
                                class="mt-1.5 flex items-start gap-1.5 text-xs text-muted dark:text-gray-400"
                            >
                                <MapPin
                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-accent"
                                />
                                <span>
                                    {{ agency.address }}
                                    <button
                                        type="button"
                                        class="ml-1 font-medium text-primary hover:underline"
                                        @click="
                                            openLocation(
                                                agency.name,
                                                agency.address,
                                                agency.latitude,
                                                agency.longitude,
                                            )
                                        "
                                    >
                                        View location
                                    </button>
                                </span>
                            </p>

                            <div
                                v-if="
                                    agency.id_front ||
                                    agency.id_back ||
                                    agency.document
                                "
                                class="mt-3 flex flex-wrap gap-1.5"
                            >
                                <DocumentLink
                                    v-if="agency.id_front"
                                    :url="agency.id_front"
                                    label="ID Front"
                                />
                                <DocumentLink
                                    v-if="agency.id_back"
                                    :url="agency.id_back"
                                    label="ID Back"
                                />
                                <DocumentLink
                                    v-if="agency.document"
                                    :url="agency.document"
                                    label="Agency Document"
                                />
                            </div>

                            <RejectionRecord
                                v-if="agencyRejections > 0"
                                :count="agencyRejections"
                                class="mt-3"
                                @view="openAgencyLogs"
                            />

                            <div
                                class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 text-[11px] text-muted dark:border-white/10 dark:text-gray-400"
                            >
                                <CalendarDays
                                    class="h-3.5 w-3.5 text-primary"
                                />
                                <span
                                    class="font-semibold text-secondary dark:text-white"
                                >
                                    {{ subscription.plan.name }}
                                </span>
                                <span
                                    class="rounded-md bg-primary-50 px-1.5 py-0.5 text-[10px] font-semibold text-primary dark:bg-primary-500/10 dark:text-primary-300"
                                >
                                    {{ subscription.plan.plan_code }}
                                </span>
                                <span v-if="subscription.billing_interval">
                                    · Billed
                                    {{
                                        subscription.billing_interval.toLowerCase()
                                    }}
                                </span>
                                <span>
                                    · Runs through
                                    {{ formatDate(subscription.end_date) }}
                                </span>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold capitalize"
                                    :class="subscriptionStatusClass"
                                >
                                    {{ subscriptionStatus }}
                                </span>

                                <button
                                    v-if="payments.length"
                                    type="button"
                                    class="ml-auto inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-primary hover:bg-primary-50 hover:text-primary dark:border-white/10 dark:text-gray-300 dark:hover:bg-primary-500/10"
                                    @click="showPayments = true"
                                >
                                    <CreditCard class="h-3.5 w-3.5" />
                                    {{
                                        payments.length > 1
                                            ? "View payments"
                                            : "View payment"
                                    }}
                                </button>
                            </div>
                        </div>

                        <aside
                            class="border-b border-slate-100 p-3 dark:border-white/10 md:overflow-y-auto md:border-b-0 md:border-r"
                        >
                            <div
                                class="flex items-center justify-between gap-2 px-2 pb-2 pt-1"
                            >
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-widest text-muted dark:text-gray-500"
                                >
                                    Branches
                                </p>
                                <span
                                    class="text-[10px] font-semibold tabular-nums text-muted dark:text-gray-400"
                                >
                                    {{ usedSlots }} of {{ branchLimit }} used
                                </span>
                            </div>

                            <div class="space-y-1">
                                <button
                                    v-for="branch in branches"
                                    :key="branch.uuid"
                                    type="button"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-left transition"
                                    :class="
                                        branch.uuid === selectedBranch?.uuid
                                            ? 'bg-primary/10 text-primary dark:bg-primary-500/15 dark:text-primary-300'
                                            : 'text-slate-700 hover:bg-slate-50 dark:text-gray-300 dark:hover:bg-white/5'
                                    "
                                    @click="selectedUuid = branch.uuid"
                                >
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-primary-50 text-primary dark:bg-primary-500/10"
                                    >
                                        <img
                                            v-if="branch.image"
                                            :src="branch.image"
                                            :alt="branch.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <Store v-else class="h-4 w-4" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-xs font-semibold"
                                        >
                                            {{ branch.name }}
                                        </p>
                                        <p
                                            class="mt-0.5 flex items-center gap-1 text-[10px] text-muted dark:text-gray-400"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full"
                                                :class="
                                                    reviewBadge(
                                                        branch.branch_status,
                                                    ).dot
                                                "
                                            />
                                            {{
                                                reviewBadge(
                                                    branch.branch_status,
                                                ).label
                                            }}
                                        </p>
                                    </div>
                                </button>

                                <div
                                    v-for="n in openSlots"
                                    :key="`open-${n}`"
                                    class="flex items-center gap-2.5 rounded-lg border border-dashed border-slate-200 px-2 py-2 text-[11px] text-slate-400 dark:border-white/10 dark:text-gray-500"
                                >
                                    <div
                                        class="h-8 w-8 shrink-0 rounded-lg border border-dashed border-slate-200 dark:border-white/10"
                                    />
                                    Open slot
                                </div>
                            </div>
                        </aside>

                        <section
                            v-if="selectedBranch"
                            class="min-w-0 p-6 md:overflow-y-auto"
                        >
                            <div class="flex items-start gap-4">
                                <div
                                    class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-primary-50 text-primary dark:bg-primary-500/10"
                                >
                                    <img
                                        v-if="selectedBranch.image"
                                        :src="selectedBranch.image"
                                        :alt="selectedBranch.name"
                                        class="h-full w-full object-cover"
                                    />
                                    <Store v-else class="h-7 w-7" />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-[10px] font-semibold uppercase tracking-widest text-primary dark:text-primary-300"
                                    >
                                        Branch
                                    </p>
                                    <div
                                        class="mt-1 flex flex-wrap items-center gap-2"
                                    >
                                        <h3
                                            class="truncate text-lg font-semibold text-secondary dark:text-white"
                                        >
                                            {{ selectedBranch.name }}
                                        </h3>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                                            :class="
                                                reviewBadge(
                                                    selectedBranch.branch_status,
                                                ).class
                                            "
                                        >
                                            {{
                                                reviewBadge(
                                                    selectedBranch.branch_status,
                                                ).label
                                            }}
                                        </span>
                                    </div>

                                </div>

                                <button
                                    v-if="branchLogs(selectedBranch.uuid).length"
                                    type="button"
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-primary hover:bg-primary-50 hover:text-primary dark:border-white/10 dark:text-gray-300 dark:hover:bg-primary-500/10"
                                    @click="openBranchLogs(selectedBranch)"
                                >
                                    <History class="h-3.5 w-3.5" />
                                    View logs
                                    <span
                                        v-if="rejectionsFor(selectedBranch.uuid)"
                                        class="rounded-full bg-rose-50 px-1.5 text-[10px] font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-400"
                                    >
                                        {{ rejectionsFor(selectedBranch.uuid) }}
                                        rejected
                                    </span>
                                </button>
                            </div>

                            <div
                                v-if="showRejection"
                                class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 dark:border-rose-500/30 dark:bg-rose-500/10"
                            >
                                <p
                                    class="text-[11px] font-semibold uppercase tracking-wide text-rose-500 dark:text-rose-300"
                                >
                                    Rejected
                                    <template v-if="subscription.rejected_at">
                                        on
                                        {{
                                            formatDate(subscription.rejected_at)
                                        }}
                                    </template>
                                </p>
                                <p
                                    class="mt-1 text-sm leading-6 text-rose-700 dark:text-rose-300"
                                >
                                    {{
                                        subscription.rejection_reason ||
                                        "No reason was given."
                                    }}
                                </p>
                            </div>

                            <dl
                                class="mt-6 grid grid-cols-1 gap-x-6 gap-y-5 sm:grid-cols-2"
                            >
                                <div v-if="selectedBranch.email">
                                    <dt class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted dark:text-gray-500">
                                        <Mail class="h-3.5 w-3.5" />
                                        Email
                                    </dt>
                                    <dd class="mt-1 break-all text-sm text-secondary dark:text-gray-200">
                                        {{ selectedBranch.email }}
                                    </dd>
                                </div>

                                <div v-if="selectedBranch.contact_number">
                                    <dt class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted dark:text-gray-500">
                                        <Phone class="h-3.5 w-3.5" />
                                        Contact number
                                    </dt>
                                    <dd class="mt-1 text-sm text-secondary dark:text-gray-200">
                                        {{ selectedBranch.contact_number }}
                                    </dd>
                                </div>

                                <div v-if="selectedBranch.tin">
                                    <dt class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted dark:text-gray-500">
                                        <Hash class="h-3.5 w-3.5" />
                                        TIN
                                    </dt>
                                    <dd class="mt-1 text-sm text-secondary dark:text-gray-200">
                                        {{ selectedBranch.tin }}
                                    </dd>
                                </div>

                                <div
                                    v-if="selectedBranch.address"
                                    class="sm:col-span-2"
                                >
                                    <dt class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted dark:text-gray-500">
                                        <MapPin class="h-3.5 w-3.5" />
                                        Address
                                    </dt>
                                    <dd class="mt-1 text-sm text-secondary dark:text-gray-200">
                                        {{ selectedBranch.address }}
                                    </dd>
                                    <button
                                        type="button"
                                        class="mt-1 text-xs font-medium text-primary hover:underline"
                                        @click="
                                            openLocation(
                                                selectedBranch.name,
                                                selectedBranch.address,
                                                selectedBranch.latitude,
                                                selectedBranch.longitude,
                                            )
                                        "
                                    >
                                        View location
                                    </button>
                                </div>

                                <div class="sm:col-span-2">
                                    <dt class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted dark:text-gray-500">
                                        <FileText class="h-3.5 w-3.5" />
                                        Document
                                    </dt>
                                    <dd class="mt-1.5">
                                        <DocumentLink
                                            v-if="selectedBranch.document"
                                            :url="selectedBranch.document"
                                            label="Branch Document"
                                        />
                                        <span
                                            v-else
                                            class="text-xs text-muted dark:text-gray-500"
                                        >
                                            No document on file
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    </div>
                </div>
            </div>
        </Transition>

        <SubscriptionPaymentsModal
            :open="showPayments"
            :agency-name="agency.name"
            :payments="payments"
            @close="showPayments = false"
        />

        <VerificationLogsModal
            :open="!!logsView"
            :title="logsView?.title"
            :branch-name="logsView?.subtitle ?? ''"
            :logs="logsView?.logs ?? []"
            :show-branch="logsView?.showBranch"
            @close="logsView = null"
        />

        <LocationModal
            :open="!!locationTarget"
            :branch-name="locationTarget?.name"
            :address="locationTarget?.address"
            :latitude="locationTarget?.latitude"
            :longitude="locationTarget?.longitude"
            @close="locationTarget = null"
        />
    </Teleport>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import {
    Building2,
    CalendarDays,
    CreditCard,
    FileText,
    Hash,
    History,
    Mail,
    MapPin,
    Phone,
    Store,
    UserRound,
    X,
} from "lucide-vue-next";
import DocumentLink from "~/components/ui/DocumentLink.vue";
import LocationModal from "~/components/sections/owner/LocationModal.vue";
import VerificationLogsModal from "~/components/sections/owner/VerificationLogsModal.vue";
import SubscriptionPaymentsModal from "~/components/sections/owner/SubscriptionPaymentsModal.vue";
import RejectionRecord from "~/components/sections/owner/RejectionRecord.vue";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { formatDate } from "~/utils/time";
import type {
    SubscriptionCardData,
    SubscriptionCoveredBranch,
    VerificationLogRecord,
} from "~/types/subscription";

const props = defineProps<{
    open: boolean;
    subscription: SubscriptionCardData | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const agency = computed(
    () =>
        props.subscription?.branch.agency ??
        ({} as SubscriptionCardData["branch"]["agency"]),
);

const ownBranch = computed<SubscriptionCoveredBranch | null>(() => {
    const branch = props.subscription?.branch;
    if (!branch) return null;

    return {
        uuid: branch.uuid,
        name: branch.name,
        email: branch.email,
        contact_number: branch.contact_number,
        address: branch.address,
        latitude: branch.latitude,
        longitude: branch.longitude,
        document: branch.document,
        image: branch.image,
        tin: branch.tin,
        branch_status: branch.status as SubscriptionCoveredBranch["branch_status"],
        status:
            props.subscription?.status === "rejected" ? "rejected" : "approved",
    };
});

const coveredBranches = computed(
    () => props.subscription?.subscription?.covered_branches ?? [],
);

const branches = computed(() => {
    const own = ownBranch.value;
    const covered = coveredBranches.value;

    if (!own || covered.some((branch) => branch.uuid === own.uuid)) {
        return covered;
    }

    return [own, ...covered];
});

const branchLimit = computed(
    () => props.subscription?.subscription?.branch_limit ?? 5,
);

const usedSlots = computed(() => coveredBranches.value.length);

const openSlots = computed(() =>
    Math.max(0, branchLimit.value - usedSlots.value),
);

const selectedUuid = ref<string | null>(null);

watch(
    () => [props.open, props.subscription?.uuid],
    () => {
        selectedUuid.value = props.subscription?.branch.uuid ?? null;
    },
    { immediate: true },
);

const selectedBranch = computed(
    () =>
        branches.value.find((branch) => branch.uuid === selectedUuid.value) ??
        branches.value[0] ??
        null,
);

const showRejection = computed(
    () =>
        props.subscription?.status === "rejected" &&
        selectedBranch.value?.uuid === props.subscription.branch.uuid,
);

const subscriptionStatus = computed(
    () => props.subscription?.subscription?.status ?? props.subscription?.status,
);

const subscriptionStatusClass = computed(() => {
    switch (subscriptionStatus.value) {
        case "active":
            return "bg-accent-50 text-accent-600 dark:bg-accent-500/10 dark:text-accent-300";
        case "pending":
            return "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300";
        case "expired":
        case "rejected":
            return "bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-300";
        default:
            return "bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-300";
    }
});

const reviewBadge = (status?: string | null) => {
    switch (status) {
        case "verified":
            return {
                label: "Verified",
                dot: "bg-accent-500",
                class: "bg-accent-50 text-accent-600 dark:bg-accent-500/10 dark:text-accent-300",
            };
        case "rejected":
            return {
                label: "Rejected",
                dot: "bg-rose-400",
                class: "bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400",
            };
        default:
            return {
                label: "Pending",
                dot: "bg-amber-400",
                class: "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300",
            };
    }
};

const locationTarget = ref<{
    name?: string | null;
    address?: string | null;
    latitude?: number | string | null;
    longitude?: number | string | null;
} | null>(null);

const openLocation = (
    name?: string | null,
    address?: string | null,
    latitude?: number | string | null,
    longitude?: number | string | null,
) => {
    locationTarget.value = { name, address, latitude, longitude };
};

const showPayments = ref(false);

const payments = computed(() => props.subscription?.payments ?? []);

const logs = ref<VerificationLogRecord[]>([]);

const logsView = ref<{
    title: string;
    subtitle: string;
    logs: VerificationLogRecord[];
    showBranch: boolean;
} | null>(null);

const agencyLogs = computed(() =>
    logs.value.filter((log) => log.scope !== "branch"),
);

const agencyRejections = computed(
    () => agencyLogs.value.filter((log) => log.action === "rejected").length,
);

const branchLogs = (uuid: string) =>
    logs.value.filter((log) => log.branch_uuid === uuid);

const rejectionsFor = (uuid: string) =>
    branchLogs(uuid).filter((log) => log.action === "rejected").length;

const openAgencyLogs = () => {
    logsView.value = {
        title: "Agency verification logs",
        subtitle: agency.value.name,
        logs: agencyLogs.value,
        showBranch: true,
    };
};

const openBranchLogs = (branch: SubscriptionCoveredBranch) => {
    logsView.value = {
        title: "Branch verification logs",
        subtitle: branch.name,
        logs: branchLogs(branch.uuid),
        showBranch: false,
    };
};

let logsRequest = 0;

const loadLogs = async () => {
    const uuid = props.subscription?.uuid;
    const thisRequest = ++logsRequest;

    logs.value = [];
    if (!uuid) return;

    try {
        const res = await subscriptionService.action({
            action: "logs",
            for: "agency",
            branch_subscription_uuid: uuid,
        });

        if (thisRequest === logsRequest) logs.value = res?.data ?? [];
    } catch {
        if (thisRequest === logsRequest) logs.value = [];
    }
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key !== "Escape" || !props.open) return;

    if (showPayments.value) {
        showPayments.value = false;
        return;
    }

    if (logsView.value) {
        logsView.value = null;
        return;
    }

    if (locationTarget.value) {
        locationTarget.value = null;
        return;
    }

    emit("close");
};

watch(
    () => [props.open, props.subscription?.uuid] as const,
    ([open]) => {
        if (!import.meta.client) return;

        if (open) {
            window.addEventListener("keydown", onKeydown);
            loadLogs();
        } else {
            window.removeEventListener("keydown", onKeydown);
            logsView.value = null;
            showPayments.value = false;
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (import.meta.client) window.removeEventListener("keydown", onKeydown);
});
</script>
