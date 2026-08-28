<template>
    <section>
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-secondary">
                    Patient Services
                </h2>

                <p class="mt-1 text-xs text-muted">
                    Services billed to this patient.
                </p>
            </div>

            <span
                class="rounded-full bg-accent-50 px-3 py-1 text-xs font-medium text-accent-700"
            >
                {{ services.length }}
            </span>
        </div>

        <div v-if="services.length" class="space-y-3">
            <div
                v-for="(service, index) in services"
                :key="service.schedule_services_id ?? index"
                class="rounded-xl border border-primary-100 bg-white p-4 transition hover:border-accent-300 hover:bg-accent-50/20"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex min-w-0 items-start gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-accent-50 text-accent-700"
                        >
                            <Stethoscope class="h-4 w-4" :stroke-width="2" />
                        </div>

                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold text-secondary"
                            >
                                {{ service.service_name ?? "Unnamed Service" }}
                            </p>

                            <!-- <p class="mt-1 font-mono text-[10px] text-muted">
                                Schedule Service #{{
                                    service.schedule_services_id
                                }}
                            </p> -->

                            <p
                                v-if="isAdl(service) && service.hours_booked"
                                class="mt-1 text-xs text-muted"
                            >
                                {{ service.hours_booked }}
                                {{
                                    service.hours_booked === 1
                                        ? "hour"
                                        : "hours"
                                }}
                                booked
                            </p>
                        </div>
                    </div>

                    <div class="shrink-0 text-left sm:text-right">
                        <p
                            class="text-[10px] uppercase tracking-[0.12em] text-muted"
                        >
                            Amount
                        </p>

                        <p class="mt-1 text-base font-bold text-accent-700">
                            ₱{{ formatMoney(service.price) }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="service.note"
                    class="mt-4 rounded-lg bg-slate-50 px-3 py-2.5"
                >
                    <p
                        class="text-[10px] font-semibold uppercase tracking-[0.12em] text-muted"
                    >
                        Note
                    </p>

                    <p class="mt-1 text-xs leading-5 text-secondary">
                        {{ service.note }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-xl border border-dashed border-primary-100 px-6 py-10 text-center"
        >
            <p class="text-sm font-semibold text-secondary">
                No services found
            </p>

            <p class="mt-1 text-xs text-muted">
                This patient does not have any recorded service charges.
            </p>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Stethoscope } from "lucide-vue-next";

import { formatAmount } from "~/utils/currency";
import type { InvoiceServiceLine } from "~/types/invoice";

defineProps<{
    services: InvoiceServiceLine[];
}>();

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}

function isAdl(service: InvoiceServiceLine) {
    return (service.type ?? "").toUpperCase() === "ADL";
}
</script>
