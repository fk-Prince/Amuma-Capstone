<template>
    <div class="min-h-[calc(100vh-90px)] bg-slate-100 p-6">
        <div
            class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5 mb-6"
        >
            <div class="w-full flex flex-col gap-6 min-h-0">
                <div class="space-y-5 flex justify-between">
                    <PageHeader
                        title="Booking"
                        subtitle="Care Coordination"
                        description="Search for a booking by reference ID to view its details and payment."
                    />

                    <div
                        class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-end max-w-2xl"
                    >
                        <div class="flex-1">
                            <BaseInput
                                v-model="search"
                                placeholder="Enter booking reference ID (e.g. BKN0001)"
                                is-search
                                :error="searchError"
                                @keyup.enter="handleSearch"
                            />
                        </div>

                        <button
                            type="button"
                            :disabled="loading || !search.trim()"
                            @click="handleSearch"
                            class="h-[42px] shrink-0 rounded-lg bg-primary text-white px-6 text-sm font-medium transition hover:bg-primary/90 disabled:opacity-40 disabled:cursor-not-allowed inline-flex items-center justify-center gap-2"
                        >
                            <svg
                                v-if="loading"
                                class="animate-spin h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                />
                            </svg>

                            {{ loading ? "Searching..." : "Search" }}
                        </button>
                    </div>
                </div>

                <div
                    v-if="loading"
                    class="grid grid-cols-1 lg:grid-cols-[1.3fr_1fr] gap-5 w-[80%] mx-auto"
                >
                    <div
                        class="rounded-2xl border border-[#E4EFED] bg-white overflow-hidden animate-pulse shadow-sm"
                    >
                        <div class="h-16 bg-slate-100"></div>
                        <div class="p-5 space-y-3">
                            <div class="h-3 bg-slate-100 rounded w-1/3"></div>
                            <div class="h-3 bg-slate-100 rounded w-1/2"></div>
                            <div class="h-3 bg-slate-100 rounded w-2/5"></div>
                        </div>
                        <div class="px-5 pb-5 space-y-3">
                            <div class="h-3 bg-slate-100 rounded w-1/4"></div>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="h-3 bg-slate-100 rounded"></div>
                                <div class="h-3 bg-slate-100 rounded"></div>
                                <div class="h-3 bg-slate-100 rounded"></div>
                                <div class="h-3 bg-slate-100 rounded"></div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="rounded-2xl bg-black animate-pulse min-h-[300px]"
                    ></div>
                </div>

                <div
                    v-else-if="mapped"
                    class="grid grid-cols-1 lg:grid-cols-[1.3fr_1fr] gap-5 items-start w-[80%] mx-auto"
                >
                    <div
                        class="rounded-2xl border border-[#E4EFED] bg-white overflow-hidden shadow-sm"
                    >
                        <div
                            class="px-5 py-4 flex items-start justify-between bg-gradient-to-r from-primary/5 to-transparent border-b border-[#E4EFED]"
                        >
                            <div>
                                <p
                                    class="text-[11px] font-medium text-slate-400 uppercase tracking-wide"
                                >
                                    Reference No.
                                </p>
                                <p
                                    class="text-base font-semibold text-slate-900 tracking-tight"
                                >
                                    {{ mapped.referenceId }}
                                </p>
                            </div>
                            <span
                                class="text-xs font-semibold px-2.5 py-1 rounded-full capitalize whitespace-nowrap"
                                :class="statusClass(mapped.status)"
                            >
                                {{ mapped.status }}
                            </span>
                        </div>

                        <div class="px-5 py-4 border-b border-[#E4EFED]">
                            <p
                                class="text-[11px] font-medium text-slate-400 uppercase tracking-wide mb-1"
                            >
                                Booking Detail
                            </p>

                            <div class="flex items-baseline gap-1.5 mb-4">
                                <span
                                    class="text-sm font-semibold text-slate-800 capitalize"
                                >
                                    {{ mapped.category }}
                                </span>
                                <span
                                    v-if="categoryType"
                                    class="text-sm text-slate-400"
                                >
                                    ({{ categoryType }})
                                </span>
                            </div>

                            <dl
                                v-if="
                                    mapped.category === 'facility' &&
                                    mapped.facility
                                "
                                class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm"
                            >
                                <Field
                                    label="Admission Date"
                                    :value="
                                        formatDate(
                                            mapped.facility.admission_date,
                                        )
                                    "
                                />
                                <Field
                                    label="Accommodation Type"
                                    :value="
                                        mapped.reserved.accommodation_type ??
                                        mapped.reserved.room?.room_type
                                    "
                                />
                                <Field
                                    label="Billing Cycle"
                                    :value="mapped.reserved.billing_cycle"
                                    capitalize
                                />
                                <Field
                                    label="Price"
                                    :value="
                                        formatCurrency(mapped.reserved.price)
                                    "
                                />
                            </dl>

                            <div
                                v-else-if="
                                    mapped.category === 'homecare' &&
                                    mapped.homecare
                                "
                                class="space-y-3 text-sm"
                            >
                                <dl class="grid grid-cols-2 gap-y-3 gap-x-4">
                                    <Field
                                        label="Date"
                                        :value="
                                            formatDate(mapped.homecare.date)
                                        "
                                    />
                                    <Field
                                        label="Preferred Time"
                                        :value="mapped.homecare.prefered_time"
                                    />
                                    <Field
                                        label="Duration"
                                        :value="mapped.homecare.time_span"
                                    />
                                    <Field
                                        label="Address"
                                        :value="mapped.homecare.address"
                                    />
                                </dl>
                                <div v-if="mapped.homecare.services?.length">
                                    <p class="text-slate-400 text-xs mb-1.5">
                                        Services
                                    </p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="(s, i) in mapped.homecare
                                                .services"
                                            :key="i"
                                            class="text-xs bg-slate-100 text-slate-700 px-2 py-1 rounded-md"
                                        >
                                            {{ s }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="
                                mapped.category === 'facility' &&
                                (mapped.reserved?.room || mapped.reserved?.bed)
                            "
                            class="px-5 py-4 border-b border-[#E4EFED]"
                        >
                            <p
                                class="text-[11px] font-medium text-slate-400 uppercase tracking-wide mb-3"
                            >
                                Room Assignment
                            </p>
                            <dl
                                class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm"
                            >
                                <Field
                                    v-if="mapped.reserved.room"
                                    label="Room No."
                                    :value="`${mapped.reserved.room.room_no} · ${mapped.reserved.room.floor} Floor`"
                                />
                                <Field
                                    v-if="mapped.reserved.bed"
                                    label="Bed No."
                                    :value="mapped.reserved.bed.bed_no"
                                />
                                <Field
                                    v-if="mapped.reserved.room"
                                    label="Room Type"
                                    :value="mapped.reserved.room.room_type"
                                />
                                <Field
                                    v-if="mapped.reserved.room"
                                    label="Capacity"
                                    :value="mapped.reserved.room.capacity"
                                />
                            </dl>
                        </div>

                        <div class="px-5 py-4 border-b border-[#E4EFED]">
                            <h3
                                class="flex items-center gap-2 text-[11px] font-mono font-medium uppercase tracking-widest text-primary mb-3"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"
                                    />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                Patient Information
                            </h3>
                            <dl
                                class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm"
                            >
                                <Field label="Name" :value="patientName" />
                                <Field
                                    label="Contact"
                                    :value="mapped.patient.phone_number"
                                />
                                <Field
                                    label="Address"
                                    :value="mapped.patient.address"
                                />
                            </dl>
                        </div>

                        <div
                            v-if="hasGuardian"
                            class="px-5 py-4 border-b border-[#E4EFED]"
                        >
                            <h3
                                class="flex items-center gap-2 text-[11px] font-mono font-medium uppercase tracking-widest text-primary mb-3"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path
                                        d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"
                                    />
                                    <circle cx="10" cy="7" r="4" />
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                                Guardian
                            </h3>
                            <dl
                                class="grid grid-cols-2 gap-y-3 gap-x-4 text-sm"
                            >
                                <Field label="Name" :value="guardianName" />
                                <Field
                                    label="Contact"
                                    :value="mapped.guardian.phone_number"
                                />
                                <Field
                                    label="Address"
                                    :value="mapped.guardian.address"
                                />
                            </dl>
                        </div>

                        <div
                            class="px-5 py-4 bg-[#F7FAF9] flex justify-between text-sm"
                        >
                            <span class="text-slate-500">Valid Until</span>
                            <span class="font-medium text-slate-700">{{
                                formatDate(mapped.validUntil)
                            }}</span>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl bg-black min-h-[300px] lg:sticky lg:top-5"
                    ></div>
                </div>

                <div
                    v-else-if="notFound"
                    class="w-[80%] mx-auto flex justify-center"
                >
                    <div
                        class="rounded-2xl border border-dashed border-[#E4EFED] bg-white py-14 px-6 text-center max-w-md w-full"
                    >
                        <svg
                            class="mx-auto h-10 w-10 text-slate-300 mb-3"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <p class="text-sm font-medium text-slate-600">
                            No booking found
                        </p>
                        <p class="text-sm text-slate-400 mt-0.5">
                            for "{{ lastSearched }}"
                        </p>
                    </div>
                </div>

                <div
                    v-else
                    class="w-full max-w-[90%] mx-auto flex justify-center"
                >
                    <div
                        class="w-full min-h-[65vh] rounded-2xl border border-[#E4EFED] bg-white flex flex-col items-center justify-center px-6 py-14 text-center shadow-sm"
                    >
                        <div
                            class="h-14 w-14 rounded-full bg-primary/10 flex items-center justify-center mb-4"
                        >
                            <svg
                                class="h-7 w-7 text-primary"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >
                                <circle cx="11" cy="11" r="7" />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m20 20-3.5-3.5"
                                />
                            </svg>
                        </div>

                        <p class="text-base font-semibold text-slate-700">
                            Search for a booking
                        </p>

                        <p
                            class="text-sm text-slate-400 mt-2 max-w-md leading-relaxed"
                        >
                            Enter a booking reference ID to view reservation
                            details, patient information, and payment status.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, h, onMounted } from "vue";
import { invoiceService } from "~/api/invoice/InvoiceService";
import BaseInput from "~/components/ui/BaseInput.vue";
import PageHeader from "~/components/ui/PageHeader.vue";
import BookingSidebar from "~/components/sections/app/Booking/BookingSidebar.vue";
import { mapBookingResponse, type BookingRecord } from "~/stores/booking";
import { useRoute, useRouter } from "vue-router";
import type { RoomContract } from "~/types/contract";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Booking Lookup" });

const route = useRoute();
const router = useRouter();

const search = ref("");
const lastSearched = ref("");
const booking = ref<BookingRecord | null>(null);
const contract = ref<RoomContract | null>(null);
const searchError = ref("");
const notFound = ref(false);
const loading = ref(false);

const mapped = computed(() =>
    booking.value ? mapBookingResponse(booking.value) : null,
);

const categoryType = computed(() => {
    if (!mapped.value) return "";
    if (mapped.value.category === "facility") {
        return mapped.value.facility?.type ?? "";
    }
    return mapped.value.homecare?.type ?? "";
});

const patientName = computed(() => {
    if (!mapped.value) return "—";
    const { first_name, middle_name, last_name } = mapped.value.patient;
    return (
        [first_name, middle_name, last_name].filter(Boolean).join(" ") || "—"
    );
});

const patientInitials = computed(() => {
    if (!mapped.value) return "?";
    const { first_name, last_name } = mapped.value.patient;
    const initials =
        `${first_name?.[0] ?? ""}${last_name?.[0] ?? ""}`.toUpperCase();
    return initials || "?";
});

const hasGuardian = computed(() => {
    if (!mapped.value?.guardian) return false;
    const g = mapped.value.guardian;
    return !!(g.first_name || g.last_name || g.phone_number || g.address);
});

const guardianName = computed(() => {
    if (!mapped.value?.guardian) return "—";
    const { first_name, middle_name, last_name } = mapped.value.guardian;
    return (
        [first_name, middle_name, last_name].filter(Boolean).join(" ") || "—"
    );
});

function formatCurrency(amount: number) {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(amount ?? 0);
}

function formatDate(value?: string) {
    if (!value) return "—";
    const d = new Date(value);
    if (isNaN(d.getTime())) return value;
    return d.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function statusClass(status: string) {
    switch ((status || "").toLowerCase()) {
        case "confirmed":
        case "active":
            return "bg-emerald-100 text-emerald-700";
        case "pending":
            return "bg-amber-100 text-amber-700";
        case "cancelled":
        case "expired":
            return "bg-red-100 text-red-700";
        default:
            return "bg-slate-100 text-slate-600";
    }
}

async function performSearch(query: string) {
    searchError.value = "";
    notFound.value = false;
    booking.value = null;
    loading.value = true;
    lastSearched.value = query;

    try {
        const res = await invoiceService.show(
            { branch_uuid: route.params.uuid },
            query,
        );
        booking.value = res;
        if (!res) notFound.value = true;
    } catch (e) {
        searchError.value = "Something went wrong. Please try again.";
        notFound.value = true;
    } finally {
        loading.value = false;
    }
}

async function handleSearch() {
    const query = search.value.trim();
    if (!query) {
        searchError.value = "Please enter a booking reference.";
        return;
    }
    router.replace({
        query: { ...route.query, reference_id: query },
    });
    await performSearch(query);
}

onMounted(() => {
    const refFromQuery = route.query.reference_id;

    if (typeof refFromQuery === "string" && refFromQuery.trim()) {
        search.value = refFromQuery.trim();
        performSearch(search.value);
    }
});

const Field = (props: {
    label: string;
    value?: string | number;
    capitalize?: boolean;
}) =>
    h("div", [
        h("dt", { class: "text-slate-400 text-xs" }, props.label),
        h(
            "dd",
            {
                class: [
                    "font-medium text-slate-700 mt-0.5",
                    props.capitalize ? "capitalize" : "",
                ],
            },
            props.value || "—",
        ),
    ]);
</script>
