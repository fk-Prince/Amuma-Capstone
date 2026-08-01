<script setup lang="ts">
import {
    Calendar,
    Phone,
    Globe2,
    Ruler,
    Weight,
    Pill,
    MapPin,
    Building2,
    DoorOpen,
    BedDouble,
} from "lucide-vue-next";
import type { PatientRetrieve } from "~/types/patient";
import { formatDate } from "~/utils/time";

defineProps<{
    patient: PatientRetrieve;
    isEdit?: boolean;
}>();

function statusClasses(status?: string) {
    const value = (status ?? "").toLowerCase();

    if (value.includes("active") || value.includes("admitted")) {
        return "bg-[#E4F4EE] text-[#1F7A4D]";
    }

    if (value.includes("complete")) {
        return "bg-[#E6F1FA] text-[#2563A6]";
    }

    if (value.includes("discharge")) {
        return "bg-[#FBE8E6] text-[#B3402F]";
    }

    return "bg-[#FDF3DE] text-[#966B1F]";
}
</script>

<template>
    <div class="space-y-6">
        <section class="rounded-2xl bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-[#0E7C7B] text-xl font-semibold text-white"
                >
                    {{ patient.first_name.charAt(0) }}
                </div>

                <div>
                    <p
                        class="text-xs font-medium uppercase tracking-wide text-[#0E7C7B]"
                    >
                        Patient Overview
                    </p>

                    <h2 class="mt-1 text-xl font-semibold text-[#16302E]">
                        {{ patient.full_name }}
                    </h2>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span
                            class="rounded-full bg-[#EAF4F2] px-3 py-1 text-xs font-medium text-[#0E7C7B]"
                        >
                            {{ patient.gender }}
                        </span>

                        <span
                            class="rounded-full bg-[#F7FAF9] px-3 py-1 text-xs font-medium text-[#16302E]"
                        >
                            {{ patient.age }} years old
                        </span>

                        <span
                            class="rounded-full bg-[#F7FAF9] px-3 py-1 text-xs font-medium text-[#16302E]"
                        >
                            {{ patient.blood_type || "No blood type on file" }}
                        </span>
                    </div>
                </div>
            </div>

            <div
                class="mt-6 grid gap-6 border-t border-[#F0F4F3] pt-6 sm:grid-cols-3"
            >
                <div class="flex items-center gap-3">
                    <Calendar class="h-4 w-4 shrink-0 text-[#0E7C7B]" />
                    <div>
                        <p class="text-xs text-muted">Birthday</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ formatDate(patient.date_of_birth) }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Phone class="h-4 w-4 shrink-0 text-[#0E7C7B]" />
                    <div>
                        <p class="text-xs text-muted">Contact</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ patient.phone_number || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Globe2 class="h-4 w-4 shrink-0 text-[#0E7C7B]" />
                    <div>
                        <p class="text-xs text-muted">Citizenship</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ patient.citizenship || "—" }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="mt-6 grid gap-6 border-t border-[#F0F4F3] pt-6 sm:grid-cols-3"
            >
                <div class="flex items-center gap-3">
                    <Ruler class="h-4 w-4 shrink-0 text-[#0E7C7B]" />
                    <div>
                        <p class="text-xs text-muted">Height</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ patient.height || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Weight class="h-4 w-4 shrink-0 text-[#0E7C7B]" />
                    <div>
                        <p class="text-xs text-muted">Weight</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ patient.weight || "—" }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Pill class="h-4 w-4 shrink-0 text-[#0E7C7B]" />
                    <div>
                        <p class="text-xs text-muted">Medication</p>
                        <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                            {{ patient.medication?.length || 0 }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="mt-6 flex items-start gap-3 border-t border-[#F0F4F3] pt-6"
            >
                <MapPin class="h-4 w-4 shrink-0 text-[#0E7C7B]" />
                <div>
                    <p class="text-xs text-muted">Location</p>
                    <p class="mt-0.5 text-sm font-medium text-[#16302E]">
                        {{
                            patient.location?.full_address ||
                            "No address recorded."
                        }}
                    </p>
                </div>
            </div>
        </section>

        <section
            v-if="patient.admissions?.length"
            class="rounded-2xl bg-white p-6 shadow-sm"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Building2 class="h-4 w-4 text-[#0E7C7B]" />
                    <h3 class="font-semibold text-[#16302E]">
                        Admission{{
                            patient.admissions.length === 1 ? "" : "s"
                        }}
                    </h3>
                </div>

                <span
                    class="rounded-full bg-[#EAF4F2] px-2.5 py-1 text-xs font-medium text-[#0E7C7B]"
                >
                    {{ patient.admissions.length }} Record{{
                        patient.admissions.length === 1 ? "" : "s"
                    }}
                </span>
            </div>

            <div class="mt-5 space-y-5">
                <div
                    v-for="admission in patient.admissions"
                    :key="admission.patient_admission_id"
                    class="rounded-xl bg-[#F7FAF9] p-4 transition hover:bg-[#EAF4F2]/60"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <p
                                    class="text-sm font-semibold capitalize text-[#16302E]"
                                >
                                    {{ admission.status }}
                                </p>

                                <p
                                    v-if="
                                        admission.status
                                            ?.toLowerCase()
                                            .includes('discharge') &&
                                        admission.end_date
                                    "
                                    class="mt-0.5 text-xs text-muted"
                                >
                                    at
                                    {{ formatDate(admission.end_date) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-1">
                                <p class="mt-0.5 text-xs text-muted">
                                    Admitted at
                                    {{ formatDate(admission.admitted_at) }}
                                </p>

                                <p
                                    v-if="
                                        admission.status
                                            ?.toLowerCase()
                                            .includes('admitted') &&
                                        admission.end_date
                                    "
                                    class="mt-0.5 text-xs text-muted"
                                >
                                    till
                                    {{ formatDate(admission.end_date) }}
                                </p>
                            </div>
                        </div>

                        <span
                            class="shrink-0 rounded-full px-3 py-1 text-xs font-medium capitalize"
                            :class="statusClasses(admission.status)"
                        >
                            {{ admission.status }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div class="flex items-center gap-2">
                            <Building2 class="h-3.5 w-3.5 text-[#0E7C7B]" />
                            <div>
                                <p class="text-[11px] text-muted">Floor</p>
                                <p class="text-sm font-medium text-[#16302E]">
                                    {{ admission.room?.floor || "—" }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <DoorOpen class="h-3.5 w-3.5 text-[#0E7C7B]" />
                            <div>
                                <p class="text-[11px] text-muted">Room</p>
                                <p class="text-sm font-medium text-[#16302E]">
                                    {{ admission.room?.room_no || "—" }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <BedDouble class="h-3.5 w-3.5 text-[#0E7C7B]" />
                            <div>
                                <p class="text-[11px] text-muted">Bed</p>
                                <p class="text-sm font-medium text-[#16302E]">
                                    {{ admission.bed?.bed_no || "—" }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoices -->
                    <div
                        v-if="admission.invoices?.length"
                        class="mt-4 border-t border-[#E4EFED] pt-4"
                    >
                        <p class="mb-2 text-xs font-semibold text-[#16302E]">
                            Contracts / Invoices
                        </p>

                        <div class="space-y-2">
                            <div
                                v-for="invoice in admission.invoices"
                                :key="invoice.invoice_facility_id"
                                class="rounded-lg bg-white px-3 py-2 border border-[#E4EFED]"
                            >
                                <div class="flex justify-between">
                                    <span class="text-xs text-muted">
                                        {{ invoice.contract?.category || "—" }}
                                    </span>

                                    <span
                                        class="text-xs font-semibold text-[#0E7C7B]"
                                    >
                                        ₱{{ invoice.price }}
                                    </span>
                                </div>

                                <div class="mt-1 text-xs text-[#16302E]">
                                    {{
                                        invoice.contract?.accommodation_type ||
                                        "—"
                                    }}
                                    ·
                                    {{ invoice.contract?.billing_cycle || "—" }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
