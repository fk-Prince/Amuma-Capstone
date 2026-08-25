<script setup lang="ts">
import { ref, onMounted } from "vue";
import {
    ChevronDown,
    CalendarDays,
    MapPin,
    UserRound,
    CreditCard,
    ClipboardList,
    Clock3,
    CheckCircle2,
    AlertCircle,
    XCircle,
    Building2,
    HeartPulse,
} from "lucide-vue-next";
import Pagination from "~/components/ui/Pagination.vue";
import PatientDetails from "~/components/sections/app/Booking/PatientDetails.vue";
import GuardianAssessmentDetails from "~/components/sections/app/Booking/GuardianAssessmentDetails.vue";
import { patientAccessService } from "../../api/patient-access/PatientAccessService";
import type { BookingRetrieve } from "~/types/booking";
import { fullName } from "~/utils/user";

useHead({ title: "Bookings" });

definePageMeta({
    layout: "portal",
});

type PortalBooking = BookingRetrieve & {
    branch_name: string | null;
};

interface PageMeta {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

const isLoading = ref(true);
const loadError = ref<string | null>(null);
const bookings = ref<PortalBooking[]>([]);
const meta = ref<PageMeta | null>(null);
const itemsPerPage = 10;
const expandedId = ref<number | null>(null);

function toggleExpanded(bookingId: number) {
    expandedId.value = expandedId.value === bookingId ? null : bookingId;
}

function formatMoney(value: number | string | null | undefined) {
    if (value === null || value === undefined || value === "") return "—";

    const amount = Number(value);

    if (Number.isNaN(amount)) return "—";

    return `₱${amount.toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
}

function statusConfig(status: string) {
    const normalized = status?.toLowerCase();

    const map: Record<
        string,
        {
            label: string;
            class: string;
            icon: typeof Clock3;
        }
    > = {
        pending: {
            label: "Pending",
            class: "bg-amber-50 text-amber-700 ring-amber-100",
            icon: Clock3,
        },
        approved: {
            label: "Approved",
            class: "bg-emerald-50 text-emerald-700 ring-emerald-100",
            icon: CheckCircle2,
        },
        completed: {
            label: "Completed",
            class: "bg-blue-50 text-blue-700 ring-blue-100",
            icon: CheckCircle2,
        },
        rejected: {
            label: "Rejected",
            class: "bg-rose-50 text-rose-700 ring-rose-100",
            icon: XCircle,
        },
        expired: {
            label: "Expired",
            class: "bg-gray-100 text-gray-600 ring-gray-200",
            icon: AlertCircle,
        },
        cancelled: {
            label: "Cancelled",
            class: "bg-gray-100 text-gray-600 ring-gray-200",
            icon: XCircle,
        },
    };

    return (
        map[normalized] ?? {
            label: status || "Unknown",
            class: "bg-gray-100 text-gray-600 ring-gray-200",
            icon: AlertCircle,
        }
    );
}

function categoryLabel(category: string) {
    return category === "facility" ? "Facility Admission" : "Homecare";
}

function categoryIcon(category: string) {
    return category === "facility" ? Building2 : HeartPulse;
}

function categoryStyle(category: string) {
    return category === "facility"
        ? "bg-violet-50 text-violet-700 ring-violet-100"
        : "bg-brand-50 text-brand-700 ring-brand-100";
}

function isFacility(booking: PortalBooking) {
    return booking.category === "facility";
}

function typeLabel(booking: PortalBooking) {
    const type = booking.homecare?.type ?? booking.facility?.type;

    if (type === "Medical") return "Medical Services";
    if (type === "ADL") return "Activity of Daily Living";
    if (type === "Complete") return "Complete Admission";
    if (type === "Pre-Admission") return "Pre-Admission";

    return type || "—";
}

function serviceDateLabel(booking: PortalBooking) {
    return isFacility(booking) ? "Admission Date" : "Service Date";
}

function serviceDate(booking: PortalBooking) {
    return isFacility(booking)
        ? booking.facility?.admission_date
        : booking.homecare?.date;
}

function formatDate(value: string | null | undefined) {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return value;

    return date.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

function formatDateTime(value: string | null | undefined) {
    if (!value) return "—";

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return value;

    return date.toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

function patientName(booking: PortalBooking) {
    return (
        fullName(
            booking.patient?.first_name,
            booking.patient?.middle_name,
            booking.patient?.last_name,
        ) || "Unnamed Patient"
    );
}

async function loadBookings(page = 1) {
    isLoading.value = true;
    loadError.value = null;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "bookings",
            page,
            per_page: itemsPerPage,
        });

        bookings.value = res?.data ?? [];
        meta.value = res?.meta ?? null;
    } catch (err: any) {
        console.error("Error loading bookings:", err);
        loadError.value = err?.message || "Failed to load bookings.";
    } finally {
        isLoading.value = false;
    }
}

function onChangePage(page: number) {
    expandedId.value = null;
    loadBookings(page);
}

onMounted(() => {
    loadBookings();
});
</script>

<template>
    <div class="min-h-screen space-y-6 p-5">
        <div
            class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-brand-600 to-brand-700 px-6 py-7 text-white shadow-sm sm:px-8"
        >
            <div
                class="absolute -right-12 -top-16 h-40 w-40 rounded-full bg-white/10"
            />
            <div
                class="absolute -bottom-20 right-20 h-48 w-48 rounded-full bg-white/5"
            />

            <div class="relative">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20"
                        >
                            <ClipboardList class="h-6 w-6" />
                        </div>

                        <div>
                            <h1
                                class="text-xl font-semibold tracking-tight sm:text-2xl"
                            >
                                My Bookings
                            </h1>
                            <p
                                class="mt-1 max-w-xl text-sm leading-6 text-white/75"
                            >
                                View your booking requests, service details,
                                payment information, and current status.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="meta"
                        class="mt-6 flex flex-wrap items-center gap-3"
                    >
                        <div
                            class="rounded-xl bg-white/10 px-3.5 py-2 ring-1 ring-white/10"
                        >
                            <p
                                class="text-[11px] font-medium uppercase tracking-wider text-white/60"
                            >
                                Total bookings
                            </p>
                            <p class="mt-0.5 text-lg font-semibold">
                                {{ meta.total }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="space-y-4">
            <div
                v-for="row in 5"
                :key="row"
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
            >
                <div class="animate-pulse p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <div class="h-11 w-11 rounded-xl bg-gray-100" />

                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="h-4 w-32 rounded bg-gray-200" />
                                <div class="h-3 w-48 rounded bg-gray-100" />
                            </div>
                        </div>

                        <div class="h-7 w-24 rounded-full bg-gray-100" />
                    </div>

                    <div
                        class="mt-5 grid grid-cols-2 gap-4 border-t border-gray-100 pt-5 sm:grid-cols-3"
                    >
                        <div class="space-y-2">
                            <div class="h-2.5 w-16 rounded bg-gray-100" />
                            <div class="h-4 w-28 rounded bg-gray-200" />
                        </div>

                        <div class="space-y-2">
                            <div class="h-2.5 w-20 rounded bg-gray-100" />
                            <div class="h-4 w-24 rounded bg-gray-200" />
                        </div>

                        <div class="hidden space-y-2 sm:block">
                            <div class="h-2.5 w-20 rounded bg-gray-100" />
                            <div class="h-4 w-28 rounded bg-gray-200" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else-if="loadError"
            class="overflow-hidden rounded-3xl border border-rose-100 bg-white shadow-sm"
        >
            <div class="flex flex-col items-center px-6 py-14 text-center">
                <div
                    class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 text-rose-500"
                >
                    <AlertCircle class="h-7 w-7" />
                </div>

                <h2 class="mt-4 text-sm font-semibold text-gray-900">
                    Unable to load bookings
                </h2>

                <p class="mt-1 max-w-sm text-sm leading-6 text-gray-500">
                    {{ loadError }}
                </p>

                <button
                    type="button"
                    @click="loadBookings()"
                    class="mt-5 inline-flex items-center rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                >
                    Try again
                </button>
            </div>
        </div>

        <template v-else>
            <div
                v-if="!bookings.length"
                class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm"
            >
                <div class="flex flex-col items-center px-6 py-16 text-center">
                    <div
                        class="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-600"
                    >
                        <ClipboardList class="h-8 w-8" />
                    </div>

                    <h2 class="mt-5 text-base font-semibold text-gray-900">
                        No booking requests yet
                    </h2>

                    <p class="mt-1 max-w-sm text-sm leading-6 text-gray-500">
                        Your booking requests will appear here once you submit a
                        service request.
                    </p>
                </div>
            </div>

            <div v-else class="space-y-4">
                <div
                    v-for="booking in bookings"
                    :key="booking.booking_id"
                    class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all duration-200 hover:border-gray-200 hover:shadow-md"
                    :class="
                        expandedId === booking.booking_id
                            ? 'ring-1 ring-brand-100'
                            : ''
                    "
                >
                    <button
                        type="button"
                        class="block w-full text-left"
                        :aria-expanded="expandedId === booking.booking_id"
                        @click="toggleExpanded(booking.booking_id)"
                    >
                        <div class="p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                                        :class="
                                            isFacility(booking)
                                                ? 'bg-violet-50 text-violet-600'
                                                : 'bg-brand-50 text-brand-600'
                                        "
                                    >
                                        <component
                                            :is="categoryIcon(booking.category)"
                                            class="h-5 w-5"
                                        />
                                    </div>

                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <p
                                                class="truncate text-sm font-semibold text-gray-900 sm:text-base"
                                            >
                                                {{ booking.reference_id }}
                                            </p>

                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset"
                                                :class="
                                                    statusConfig(booking.status)
                                                        .class
                                                "
                                            >
                                                <component
                                                    :is="
                                                        statusConfig(
                                                            booking.status,
                                                        ).icon
                                                    "
                                                    class="h-3 w-3"
                                                />
                                                {{
                                                    statusConfig(booking.status)
                                                        .label
                                                }}
                                            </span>
                                        </div>

                                        <div
                                            class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500"
                                        >
                                            <span
                                                class="inline-flex items-center gap-1"
                                            >
                                                <UserRound
                                                    class="h-3.5 w-3.5 text-gray-400"
                                                />
                                                {{ patientName(booking) }}
                                            </span>

                                            <span
                                                v-if="booking.branch_name"
                                                class="hidden text-gray-300 sm:inline"
                                            >
                                                •
                                            </span>

                                            <span
                                                v-if="booking.branch_name"
                                                class="inline-flex items-center gap-1"
                                            >
                                                <MapPin
                                                    class="h-3.5 w-3.5 text-gray-400"
                                                />
                                                {{ booking.branch_name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <span
                                        class="hidden items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset sm:inline-flex"
                                        :class="categoryStyle(booking.category)"
                                    >
                                        <component
                                            :is="categoryIcon(booking.category)"
                                            class="h-3.5 w-3.5"
                                        />
                                        {{ categoryLabel(booking.category) }}
                                    </span>

                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-50 text-gray-400 transition-colors group-hover:bg-brand-50 group-hover:text-brand-600"
                                    >
                                        <ChevronDown
                                            class="h-4 w-4 transition-transform duration-200"
                                            :class="
                                                expandedId ===
                                                booking.booking_id
                                                    ? 'rotate-180'
                                                    : ''
                                            "
                                        />
                                    </span>
                                </div>
                            </div>

                            <div
                                class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3"
                            >
                                <div
                                    class="rounded-xl bg-gray-50/80 px-3.5 py-3"
                                >
                                    <div
                                        class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wider text-gray-400"
                                    >
                                        <ClipboardList class="h-3.5 w-3.5" />
                                        Type
                                    </div>

                                    <p
                                        class="mt-1.5 truncate text-sm font-semibold text-gray-800"
                                    >
                                        {{ typeLabel(booking) }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl bg-gray-50/80 px-3.5 py-3"
                                >
                                    <div
                                        class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wider text-gray-400"
                                    >
                                        <CalendarDays class="h-3.5 w-3.5" />
                                        {{ serviceDateLabel(booking) }}
                                    </div>

                                    <p
                                        class="mt-1.5 text-sm font-semibold text-gray-800"
                                    >
                                        {{ formatDate(serviceDate(booking)) }}
                                    </p>
                                </div>

                                <div
                                    class="col-span-2 rounded-xl bg-gray-50/80 px-3.5 py-3 sm:col-span-1"
                                >
                                    <div
                                        class="flex items-center gap-1.5 text-[11px] font-medium uppercase tracking-wider text-gray-400"
                                    >
                                        <Clock3 class="h-3.5 w-3.5" />
                                        Valid Until
                                    </div>

                                    <p
                                        class="mt-1.5 text-sm font-semibold text-gray-800"
                                    >
                                        {{ formatDate(booking.valid_until) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </button>

                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-2"
                    >
                        <div
                            v-if="expandedId === booking.booking_id"
                            class="border-t border-gray-100 bg-gray-50/50"
                        >
                            <div class="space-y-6 p-5 sm:p-6">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600"
                                    >
                                        <ClipboardList class="h-4 w-4" />
                                    </div>

                                    <div>
                                        <h3
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            Booking Details
                                        </h3>
                                        <p class="text-xs text-gray-500">
                                            Patient and assessment information
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-white p-4 sm:p-5"
                                >
                                    <PatientDetails :booking="booking" />
                                </div>

                                <div
                                    class="rounded-2xl border border-gray-100 bg-white p-4 sm:p-5"
                                >
                                    <GuardianAssessmentDetails
                                        :booking="booking"
                                    />
                                </div>

                                <div
                                    v-if="booking.payment"
                                    class="rounded-2xl border border-gray-100 bg-white p-4 sm:p-5"
                                >
                                    <div class="mb-4 flex items-center gap-3">
                                        <div
                                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"
                                        >
                                            <CreditCard class="h-4 w-4" />
                                        </div>

                                        <div>
                                            <h3
                                                class="text-sm font-semibold text-gray-900"
                                            >
                                                Payment
                                            </h3>
                                            <p class="text-xs text-gray-500">
                                                Payment and transaction details
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                                    >
                                        <div
                                            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3.5"
                                        >
                                            <p
                                                class="text-[11px] font-medium uppercase tracking-wider text-gray-400"
                                            >
                                                Status
                                            </p>

                                            <span
                                                class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize ring-1 ring-inset"
                                                :class="
                                                    booking.payment
                                                        .payment_status ===
                                                    'paid'
                                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-100'
                                                        : booking.payment
                                                                .payment_status ===
                                                            'refunded'
                                                          ? 'bg-gray-100 text-gray-600 ring-gray-200'
                                                          : 'bg-amber-50 text-amber-700 ring-amber-100'
                                                "
                                            >
                                                {{
                                                    booking.payment
                                                        .payment_status ||
                                                    "pending"
                                                }}
                                            </span>
                                        </div>

                                        <div
                                            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3.5"
                                        >
                                            <p
                                                class="text-[11px] font-medium uppercase tracking-wider text-gray-400"
                                            >
                                                Amount
                                            </p>

                                            <p
                                                class="mt-2 text-sm font-semibold text-gray-900"
                                            >
                                                {{
                                                    formatMoney(
                                                        booking.payment
                                                            .total_amount,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3.5"
                                        >
                                            <p
                                                class="text-[11px] font-medium uppercase tracking-wider text-gray-400"
                                            >
                                                Method
                                            </p>

                                            <p
                                                class="mt-2 truncate text-sm font-semibold capitalize text-gray-900"
                                            >
                                                {{
                                                    (booking.payment as any)
                                                        .payment_method || "—"
                                                }}
                                            </p>
                                        </div>

                                        <div
                                            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3.5"
                                        >
                                            <p
                                                class="text-[11px] font-medium uppercase tracking-wider text-gray-400"
                                            >
                                                Account
                                            </p>

                                            <p
                                                class="mt-2 truncate text-sm font-semibold text-gray-900"
                                            >
                                                {{
                                                    (booking.payment as any)
                                                        .masked_card_number ||
                                                    "—"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>

            <Pagination
                v-if="meta && meta.total > itemsPerPage"
                :current-page="meta.current_page"
                :total-pages="meta.last_page"
                :total-items="meta.total"
                :items-per-page="meta.per_page"
                @change-page="onChangePage"
            />
        </template>
    </div>
</template>
