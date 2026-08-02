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

                    <p class="font-5xl">FIRST DISPLAY INVOICES</p>
                    <p class="font-5xl">
                        THEN CAN BE SEARCH THIS BOOKING, SCHEDULE, PATIETN NAME
                    </p>

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
                    v-else-if="booking"
                    class="grid grid-cols-1 lg:grid-cols-[1.3fr_1fr] gap-5 items-start w-[80%] mx-auto"
                >
                    <FacilityBilling
                        v-if="search.toLowerCase().startsWith('bkn')"
                        :booking="booking"
                    />
                    <div
                        class="rounded-2xl bg-black min-h-[300px] lg:sticky lg:top-5"
                    >
                        <PaymentForm
                            :processing="processing"
                            cash-description="Enter the cash amount received. Admission will be confirmed once payment is done."
                            description="Complete your facility admission."
                            :total-amount="total"
                            currency="₱"
                            :enable-card="false"
                            :enable-g-cash="false"
                            :enable-cash="true"
                            :on-cash-pay="handleCashPay"
                        />
                    </div>
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
import { ref, onMounted } from "vue";
import { invoiceService } from "~/api/invoice/InvoiceService";
import BaseInput from "~/components/ui/BaseInput.vue";
import PageHeader from "~/components/ui/PageHeader.vue";
import { type BookingRecord } from "~/stores/booking";
import { useRoute, useRouter } from "vue-router";
import FacilityBilling from "~/components/sections/app/Billing/FacilityBilling.vue";
import PaymentForm from "~/components/forms/PaymentForm.vue";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Booking Lookup" });

const route = useRoute();
const router = useRouter();

const total = ref();
const search = ref("");
const lastSearched = ref("");
const booking = ref<BookingRecord | null>(null);
const searchError = ref("");
const notFound = ref(false);
const loading = ref(true);

const processing = ref(false);

const handleCashPay = async (cash: number) => {
    try {
        const res = await invoiceService.create({
            cash: cash,
            reference_id: route.query.reference_id,
            branch_uuid: route.params.uuid,
        });
        alert(res.message);
    } catch (err: any) {
        alert(err.message);
        console.log(err);
    }
};

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
        booking.value = res.data;
        total.value = res.total;
        if (!res) notFound.value = true;
    } catch (e) {
        console.log(e);
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
        performSearch(search.value); // this already sets loading true → false via try/finally
    } else {
        loading.value = false; // only needed if there's no query to search
    }
});
</script>
