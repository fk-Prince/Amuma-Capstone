<template>
    <div class="min-h-screen-header w-full p-3 rounded-lg">
        <div class="w-full space-y-5 pb-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <button
                    type="button"
                    @click="goBack"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-[#6B8A87] hover:text-[#16302E] transition dark:hover:text-white dark:text-gray-400"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none">
                        <path
                            d="M12.5 15L7.5 10L12.5 5"
                            stroke="currentColor"
                            stroke-width="1.75"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Back
                </button>
            </div>

            <div
                v-if="loading"
                class="grid animate-pulse items-start gap-5 xl:grid-cols-[minmax(0,1fr)_500px]"
            >
                <div
                    class="min-w-0 overflow-hidden rounded-[24px] border border-[#DDECEC] bg-white shadow-sm ring-1 ring-black/5 dark:border-white/10 dark:bg-secondary"
                >
                    <div
                        class="flex items-center justify-between gap-5 border-b border-[#EDF4F3] px-7 py-6 dark:border-white/10"
                    >
                        <div class="space-y-3">
                            <div
                                class="h-5 w-24 rounded-md bg-[#EAF4F2] dark:bg-white/10"
                            />
                            <div
                                class="h-5 w-48 rounded bg-[#EAF4F2] dark:bg-white/10"
                            />
                            <div
                                class="h-3.5 w-36 rounded bg-[#F1F7F6] dark:bg-white/5"
                            />
                        </div>

                        <div class="space-y-2">
                            <div
                                class="ml-auto h-2.5 w-14 rounded bg-[#F1F7F6] dark:bg-white/5"
                            />
                            <div
                                class="h-4 w-24 rounded bg-[#EAF4F2] dark:bg-white/10"
                            />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 border-b border-[#EDF4F3] sm:grid-cols-3 sm:divide-x divide-[#EDF4F3] dark:divide-white/10 dark:border-white/10"
                    >
                        <div
                            v-for="n in 3"
                            :key="n"
                            class="space-y-2 px-4 py-4 sm:px-7 sm:py-5"
                        >
                            <div
                                class="h-2.5 w-16 rounded bg-[#F1F7F6] dark:bg-white/5"
                            />
                            <div
                                class="h-7 w-32 rounded bg-[#EAF4F2] dark:bg-white/10"
                            />
                        </div>
                    </div>

                    <div class="space-y-8 px-7 py-6">
                        <div v-for="n in 2" :key="n" class="space-y-3">
                            <div
                                class="h-3 w-32 rounded bg-[#EAF4F2] dark:bg-white/10"
                            />
                            <div
                                class="divide-y divide-[#EDF4F3] rounded-xl border border-[#EDF4F3] px-5 dark:divide-white/10 dark:border-white/10"
                            >
                                <div
                                    v-for="row in 2"
                                    :key="row"
                                    class="flex items-center justify-between gap-4 py-4"
                                >
                                    <div
                                        class="h-3.5 w-2/3 rounded bg-[#F1F7F6] dark:bg-white/5"
                                    />
                                    <div
                                        class="h-3.5 w-20 rounded bg-[#EAF4F2] dark:bg-white/10"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="space-y-4 rounded-3xl border border-[#DDECEC] bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary"
                >
                    <div
                        class="h-5 w-40 rounded bg-[#EAF4F2] dark:bg-white/10"
                    />
                    <div
                        class="h-3.5 w-32 rounded bg-[#F1F7F6] dark:bg-white/5"
                    />
                    <div
                        class="h-24 rounded-2xl bg-[#F1F7F6] dark:bg-white/5"
                    />
                    <div class="h-12 rounded-xl bg-[#F1F7F6] dark:bg-white/5" />
                    <div
                        class="h-11 rounded-xl bg-[#EAF4F2] dark:bg-white/10"
                    />
                </div>
            </div>

            <div
                v-else-if="errorLabel"
                class="bg-white rounded-2xl shadow-sm ring-1 ring-black/5 p-10 text-center text-[#B3402F] dark:text-rose-300 dark:bg-secondary"
            >
                {{ errorLabel }}
            </div>

            <div
                v-else-if="invoice"
                class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_500px]"
            >
                <div
                    class="min-w-0 overflow-hidden rounded-[24px] border border-[#DDECEC] bg-white shadow-sm ring-1 ring-black/5 dark:border-white/10 dark:bg-secondary"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-5 px-7 py-6 border-b border-[#EDF4F3] bg-gradient-to-b from-[#0E7C7B]/[0.04] to-transparent dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="font-mono text-[15px] font-semibold text-primary-700 dark:text-primary-300"
                                >
                                    {{ invoice.invoice_code }}
                                </span>

                                <span
                                    class="rounded-full px-2 py-0.5 text-[11px] font-semibold capitalize"
                                    :class="statusClasses(invoice.status)"
                                >
                                    {{ statusLabel(invoice.status) }}
                                </span>

                                <span
                                    v-if="isAdjusted"
                                    class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"
                                >
                                    Adjusted
                                </span>
                            </div>

                            <p
                                v-if="invoice.description"
                                class="mt-1 text-[13px] text-muted dark:text-gray-400"
                            >
                                {{ invoice.description }}
                            </p>

                            <p
                                class="mt-1 text-[13px] font-medium text-[#16302E] dark:text-white"
                            >
                                {{ invoice.patient?.full_name ?? "—" }}
                                <span
                                    class="font-normal text-[#6B8A87] dark:text-gray-400"
                                >
                                    · {{ invoice.branch?.name ?? "—" }}
                                </span>
                            </p>

                            <p
                                v-if="isWrittenOff && invoice.write_off_reason"
                                class="mt-1 text-[12px] text-amber-600 dark:text-amber-300"
                            >
                                Written off: {{ invoice.write_off_reason }}
                            </p>

                            <p
                                class="mt-1 text-[12px] text-gray-400 dark:text-gray-500"
                            >
                                {{ formatDate(invoice.created_at) }}
                            </p>
                        </div>

                        <div class="shrink-0 sm:text-right">
                            <p
                                v-if="isAdjusted"
                                class="text-[12px] text-gray-400 line-through dark:text-gray-500"
                            >
                                ₱{{ formatMoney(invoice.total) }}
                            </p>

                            <p
                                class="text-[15px] font-semibold text-secondary dark:text-white"
                            >
                                ₱{{
                                    formatMoney(
                                        invoice.adjusted_total ?? invoice.total,
                                    )
                                }}
                            </p>

                            <p
                                class="mt-0.5 text-[12px]"
                                :class="
                                    Number(invoice.balance_due) > 0
                                        ? 'font-semibold text-danger'
                                        : 'text-gray-400 dark:text-gray-500'
                                "
                            >
                                {{
                                    Number(invoice.balance_due) > 0
                                        ? `₱${formatMoney(invoice.balance_due)} due`
                                        : isWrittenOff
                                          ? "Written off"
                                          : "Settled"
                                }}
                            </p>

                            <p
                                v-if="
                                    isWrittenOff &&
                                    Number(invoice.written_off_amount ?? 0) > 0
                                "
                                class="mt-0.5 text-[12px] font-semibold text-amber-700 dark:text-amber-300"
                            >
                                ₱{{ formatMoney(invoice.written_off_amount) }}
                                bad debt
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 sm:divide-x divide-[#EDF4F3] border-b border-[#EDF4F3] bg-[#FAFDFC] dark:border-white/10 dark:bg-white/5"
                        :class="
                            hasRefunds ? 'sm:grid-cols-4' : 'sm:grid-cols-3'
                        "
                    >
                        <div class="px-4 sm:px-7 py-4 sm:py-5">
                            <p
                                class="text-[10px] uppercase tracking-[0.15em] text-[#6B8A87] font-mono mb-1 dark:text-gray-400"
                            >
                                Total
                            </p>
                            <p
                                class="text-2xl font-bold text-[#16302E] dark:text-white"
                            >
                                ₱{{
                                    formatMoney(
                                        invoice.adjusted_total ?? invoice.total,
                                    )
                                }}
                            </p>
                        </div>

                        <div
                            class="px-4 sm:px-7 py-4 sm:py-5 bg-[#E4F4EE]/40 dark:bg-emerald-500/10"
                        >
                            <p
                                class="text-[10px] uppercase tracking-[0.15em] text-[#1F7A4D]/80 font-mono mb-1 dark:text-emerald-300/80"
                            >
                                Amount Paid
                            </p>
                            <p
                                class="text-2xl font-bold text-[#1F7A4D] dark:text-emerald-300"
                            >
                                ₱{{ formatMoney(invoice.amount_paid) }}
                            </p>
                        </div>

                        <div
                            v-if="hasRefunds"
                            class="px-4 sm:px-7 py-4 sm:py-5 bg-[#FDF3DE]/60 dark:bg-amber-500/10"
                        >
                            <p
                                class="text-[10px] uppercase tracking-[0.15em] text-[#966B1F]/80 font-mono mb-1 dark:text-amber-300/80"
                            >
                                Refunded
                            </p>
                            <p
                                class="text-2xl font-bold text-[#966B1F] dark:text-amber-300"
                            >
                                ₱{{ formatMoney(invoice.refunded_amount) }}
                            </p>
                        </div>

                        <div
                            v-if="isWrittenOff"
                            class="px-4 sm:px-7 py-4 sm:py-5 bg-[#FDF3DE]/60 dark:bg-amber-500/10"
                        >
                            <p
                                class="text-[10px] uppercase tracking-[0.15em] text-[#966B1F]/80 font-mono mb-1 dark:text-amber-300/80"
                            >
                                Written Off
                            </p>
                            <p
                                class="text-2xl font-bold text-[#966B1F] dark:text-amber-300"
                            >
                                ₱{{ formatMoney(invoice.written_off_amount) }}
                            </p>
                        </div>

                        <div
                            v-else
                            class="px-4 sm:px-7 py-4 sm:py-5 bg-[#FBE8E6]/40 dark:bg-rose-500/10"
                        >
                            <p
                                class="text-[10px] uppercase tracking-[0.15em] text-[#B3402F]/80 font-mono mb-1 dark:text-rose-300/80"
                            >
                                Balance Due
                            </p>
                            <p
                                class="text-2xl font-bold text-[#B3402F] dark:text-rose-300"
                            >
                                ₱{{ formatMoney(invoice.balance_due) }}
                            </p>
                        </div>
                    </div>

                    <div class="px-7 py-6 space-y-8">
                        <section v-if="invoice.services?.length">
                            <SectionHeader>
                                <template #icon>
                                    <Stethoscope
                                        class="h-3.5 w-3.5"
                                        :stroke-width="2"
                                    />
                                </template>
                                Services
                            </SectionHeader>

                            <div
                                class="overflow-x-auto rounded-xl border border-[#EDF4F3] dark:border-white/10"
                            >
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr
                                            class="border-b border-[#EDF4F3] text-xs uppercase tracking-wide text-[#6B8A87] dark:border-white/10 dark:text-gray-400"
                                        >
                                            <th
                                                class="px-4 py-3 text-left font-medium"
                                            >
                                                Qty
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left font-medium"
                                            >
                                                Description
                                            </th>
                                            <th
                                                class="px-4 py-3 text-right font-medium"
                                            >
                                                Price
                                            </th>
                                            <th
                                                class="px-4 py-3 text-right font-medium"
                                            >
                                                Amount
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr
                                            v-for="item in invoice.services"
                                            :key="item.schedule_services_id"
                                            class="border-b border-[#EDF4F3] last:border-0 dark:border-white/10"
                                        >
                                            <td
                                                class="whitespace-nowrap px-4 py-3 align-top text-[#16302E] dark:text-white"
                                            >
                                                {{
                                                    item.type === "ADL"
                                                        ? formatDuration(
                                                              item.quantity ??
                                                                  0,
                                                          ) || "0 hrs"
                                                        : (item.quantity ?? 1)
                                                }}
                                            </td>

                                            <td class="px-4 py-3 align-top">
                                                <p
                                                    class="font-medium text-[#16302E] dark:text-white"
                                                >
                                                    {{
                                                        item.description ??
                                                        item.service_name ??
                                                        "Service"
                                                    }}
                                                </p>
                                                <p
                                                    v-if="item.note"
                                                    class="mt-0.5 text-xs text-[#6B8A87] dark:text-gray-400"
                                                >
                                                    {{ item.note }}
                                                </p>
                                            </td>

                                            <td
                                                class="whitespace-nowrap px-4 py-3 align-top text-right text-[#16302E] dark:text-white"
                                            >
                                                ₱{{ formatMoney(item.price)
                                                }}<span
                                                    v-if="item.type === 'ADL'"
                                                    >/hr</span
                                                >
                                            </td>

                                            <td
                                                class="whitespace-nowrap px-4 py-3 align-top text-right font-semibold text-[#16302E] dark:text-white"
                                            >
                                                ₱{{
                                                    formatMoney(
                                                        item.amount ??
                                                            item.price,
                                                    )
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section v-if="facilityCharges.length">
                            <SectionHeader>
                                <template #icon>
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
                                            d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4"
                                        />
                                        <path d="M15 3h6v6" />
                                        <path d="M10 14 21 3" />
                                    </svg>
                                </template>
                                Facility Charges
                            </SectionHeader>

                            <ul
                                class="divide-y divide-gray-100 rounded-xl border border-[#EDF4F3] px-5 dark:divide-white/10 dark:border-white/10"
                            >
                                <li
                                    v-for="facility in facilityCharges"
                                    :key="facility.description"
                                    class="flex items-start justify-between gap-4 py-4"
                                >
                                    <p
                                        class="min-w-0 text-[13px] text-muted dark:text-gray-400"
                                    >
                                        {{ facility.description }}
                                    </p>

                                    <p
                                        class="shrink-0 text-[13px] font-semibold text-secondary dark:text-white"
                                    >
                                        ₱{{ formatMoney(facility.price) }}
                                    </p>
                                </li>
                            </ul>
                        </section>

                        <section v-if="invoice.patient">
                            <SectionHeader>
                                <template #icon>
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
                                </template>
                                Patient Information
                            </SectionHeader>

                            <div
                                class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm"
                            >
                                <Field
                                    label="Name"
                                    :value="invoice.patient.full_name"
                                />
                                <Field
                                    label="Gender"
                                    :value="invoice.patient.gender"
                                />
                                <Field
                                    label="Birth Date"
                                    :value="
                                        formatDate(
                                            invoice.patient.date_of_birth,
                                        )
                                    "
                                />
                                <Field
                                    label="Age"
                                    :value="invoice.patient.age"
                                />
                                <Field
                                    label="Blood Type"
                                    :value="invoice.patient.blood_type"
                                />
                                <Field
                                    label="Phone"
                                    :value="
                                        formatPhone(
                                            invoice.patient.phone_number,
                                        )
                                    "
                                />
                                <Field
                                    label="Citizenship"
                                    :value="invoice.patient.citizenship"
                                />
                            </div>
                        </section>

                        <section
                            class="pt-5 border-t border-[#EDF4F3] dark:border-white/10"
                        >
                            <SectionHeader>
                                <template #icon>
                                    <svg
                                        class="h-3.5 w-3.5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <rect
                                            x="2"
                                            y="5"
                                            width="20"
                                            height="14"
                                            rx="2"
                                        />
                                        <path d="M2 10h20" />
                                    </svg>
                                </template>
                                Payments
                            </SectionHeader>

                            <ul
                                v-if="invoice.payments?.length"
                                class="divide-y divide-gray-100 rounded-xl border border-[#EDF4F3] px-5 dark:divide-white/10 dark:border-white/10"
                            >
                                <li
                                    v-for="payment in invoice.payments"
                                    :key="payment.payment_id"
                                    class="py-4"
                                >
                                    <div
                                        class="flex items-start justify-between gap-4"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="flex flex-wrap items-center gap-2 text-[13px] font-semibold text-secondary dark:text-white"
                                            >
                                                {{
                                                    paymentMethodLabel(
                                                        payment.payment_method,
                                                    )
                                                }}

                                                <span
                                                    v-if="
                                                        payment.payment_method ===
                                                        'CREDIT'
                                                    "
                                                    class="rounded-full bg-[#E7F0FB] px-2 py-0.5 text-[10px] font-medium text-[#1F5C9E] dark:bg-blue-500/15 dark:text-blue-300"
                                                >
                                                    Paid from account credit
                                                </span>
                                            </p>

                                            <p
                                                class="mt-0.5 text-[12px] text-muted dark:text-gray-400"
                                            >
                                                {{
                                                    payment.payment_code ??
                                                    payment.reference_id ??
                                                    "—"
                                                }}
                                                ·
                                                {{
                                                    formatDate(
                                                        payment.created_at,
                                                    )
                                                }}
                                            </p>
                                        </div>

                                        <p
                                            class="shrink-0 text-[13px] font-semibold text-[#1F7A4D] dark:text-emerald-300"
                                        >
                                            ₱{{ formatMoney(payment.amount) }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="payment.refunds?.length"
                                        class="mt-3 space-y-2 border-t border-[#EDF4F3] pt-3 dark:border-white/10"
                                    >
                                        <div
                                            v-for="refund in payment.refunds"
                                            :key="refund.refund_id"
                                            class="rounded-lg bg-[#FDF3DE]/50 px-4 py-3 dark:bg-amber-500/10"
                                        >
                                            <div
                                                class="flex items-center justify-between gap-3"
                                            >
                                                <span
                                                    class="text-xs font-mono text-[#966B1F] dark:text-amber-300"
                                                >
                                                    {{
                                                        refund.refund_code ??
                                                        "Credit issued"
                                                    }}
                                                </span>

                                                <span
                                                    class="rounded-full px-2 py-0.5 text-[10px] font-medium capitalize"
                                                    :class="
                                                        refundStatusClasses(
                                                            refund.status ??
                                                                'credited',
                                                        )
                                                    "
                                                >
                                                    {{
                                                        refund.status ??
                                                        "credited"
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                class="mt-1.5 flex items-center justify-between gap-3"
                                            >
                                                <p
                                                    class="text-xs text-[#6B8A87] dark:text-gray-400"
                                                >
                                                    {{
                                                        refund.declined_reason ??
                                                        refund.reason ??
                                                        "No reason provided."
                                                    }}
                                                </p>

                                                <span
                                                    class="shrink-0 text-sm font-semibold text-[#966B1F] dark:text-amber-300"
                                                >
                                                    ₱{{
                                                        formatMoney(
                                                            refund.amount,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>

                            <p
                                v-else
                                class="rounded-xl border border-dashed border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
                            >
                                No payments recorded yet
                            </p>
                        </section>

                        <section
                            v-if="invoice.adjustments?.length"
                            class="pt-5 border-t border-[#EDF4F3] dark:border-white/10"
                        >
                            <SectionHeader>
                                <template #icon>
                                    <svg
                                        class="h-3.5 w-3.5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M3 6h18" />
                                        <path d="M6 12h12" />
                                        <path d="M9 18h6" />
                                    </svg>
                                </template>
                                Adjustments
                            </SectionHeader>

                            <ul
                                class="divide-y divide-gray-100 rounded-xl border border-[#EDF4F3] px-5 dark:divide-white/10 dark:border-white/10"
                            >
                                <li
                                    v-for="adjustment in invoice.adjustments"
                                    :key="adjustment.invoice_adjustment_id"
                                    class="flex items-start justify-between gap-4 py-4"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="text-[13px] font-semibold capitalize text-secondary dark:text-white"
                                        >
                                            {{
                                                formatAdjustmentType(
                                                    adjustment.type,
                                                )
                                            }}
                                        </p>

                                        <p
                                            class="mt-0.5 text-[12px] font-medium text-amber-600 dark:text-amber-300"
                                        >
                                            {{
                                                adjustment.reason ??
                                                "No reason provided."
                                            }}
                                        </p>

                                        <p
                                            class="mt-0.5 text-[12px] text-gray-400 dark:text-gray-500"
                                        >
                                            {{
                                                formatDate(
                                                    adjustment.created_at,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <p
                                        class="shrink-0 text-[13px] font-semibold"
                                        :class="
                                            Number(adjustment.amount) < 0
                                                ? 'text-[#1F7A4D] dark:text-emerald-300'
                                                : 'text-amber-600 dark:text-amber-300'
                                        "
                                    >
                                        {{
                                            Number(adjustment.amount) < 0
                                                ? "−"
                                                : "+"
                                        }}₱{{
                                            formatMoney(
                                                Math.abs(
                                                    Number(adjustment.amount),
                                                ),
                                            )
                                        }}
                                    </p>
                                </li>
                            </ul>
                        </section>
                    </div>
                </div>

                <div
                    v-if="showPayment"
                    class="xl:sticky xl:top-6 rounded-3xl border border-[#DDECEC] bg-white shadow-sm overflow-hidden dark:border-white/10 dark:bg-secondary"
                >
                    <div class="p-6">
                        <PaymentForm
                            :processing="processingPayment"
                            :total-amount="invoice.balance_due"
                            :enable-card="false"
                            :enable-g-cash="false"
                            :enable-cash="true"
                            title="Complete Payment"
                            :description="`Balance due: ₱${formatMoney(invoice.balance_due)}`"
                            cash-label="Confirm Cash Payment"
                            cash-processing-label="Confirming payment..."
                            cash-description="Enter the cash amount received from the patient to settle this invoice."
                            @cash-pay="handleCashPay"
                        />
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-2xl shadow-sm ring-1 ring-black/5 bg-white p-6 text-center text-sm text-[#6B8A87] xl:sticky xl:top-6 dark:text-gray-400 dark:bg-secondary"
                >
                    This invoice is fully paid.
                </div>

                <div
                    v-if="paymentChange > 0"
                    class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center dark:border-emerald-500/20 dark:bg-emerald-500/10"
                >
                    <p
                        class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300"
                    >
                        Change Returned
                    </p>

                    <p
                        class="mt-1 text-3xl font-bold text-emerald-800 dark:text-emerald-300"
                    >
                        ₱{{ formatMoney(paymentChange) }}
                    </p>
                </div>
            </div>

            <ConfirmDialog
                :open="showConfirmPayment"
                title="Confirm Cash Payment"
                :message="`Confirm payment of ₱${formatMoney(pendingCash)}?`"
                description="This will record the cash payment and update the invoice balance."
                confirm-label="Confirm Payment"
                cancel-label="Cancel"
                :loading="processingPayment"
                @confirm="confirmCashPayment"
                @cancel="cancelPayment"
                :allow-short-cash="true"
            />

            <PaymentReceipt
                v-if="activeReceipt"
                :receipt="activeReceipt"
                @close="activeReceipt = null"
            />
        </div>
    </div>
</template>

<script lang="ts" setup>
import { ref, reactive, computed, onMounted, h } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Stethoscope } from "lucide-vue-next";
import { formatAmount } from "~/utils/currency";
import { formatDuration } from "~/utils/time";
import PaymentForm from "~/components/forms/PaymentForm.vue";
import PaymentReceipt from "~/components/billing/PaymentReceipt.vue";
import { invoiceService } from "~/api/invoice/InvoiceService";
import type { InvoiceDetail } from "~/types/invoice";
import type { PaymentReceipt as PaymentReceiptData } from "~/types/receipt";
import { useToast } from "~/composables/useToast";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({
    title: "Invoice Details",
});

const { success, error } = useToast();
const route = useRoute();
const router = useRouter();
const showConfirmPayment = ref(false);
const pendingCash = ref<number | null>(null);
const paymentChange = ref(0);
const uuid = computed(() => route.params.uuid as string);
const invoiceCode = computed(() => route.params.invoice_code as string);

const invoice = ref<InvoiceDetail | null>(null);
const loading = ref(true);
const errorLabel = ref("");
const processingPayment = ref(false);

const activeReceipt = ref<PaymentReceiptData | null>(null);

const showPayment = computed(
    () => !!invoice.value && invoice.value.balance_due > 0,
);

const facilityCharges = computed(() => {
    const groups = new Map<string, { description: string; price: number }>();

    for (const line of invoice.value?.facilities ?? []) {
        const description = line.description || "Accommodation";
        const group = groups.get(description) ?? { description, price: 0 };

        group.price =
            Math.round((group.price + Number(line.price)) * 100) / 100;
        groups.set(description, group);
    }

    return [...groups.values()].filter((group) => group.price > 0);
});

const isWrittenOff = computed(() => invoice.value?.status === "written_off");

const hasRefunds = computed(
    () => Number(invoice.value?.refunded_amount ?? 0) > 0,
);

const isAdjusted = computed(
    () =>
        !!invoice.value &&
        Math.abs(
            Number(invoice.value.adjusted_total ?? invoice.value.total) -
                Number(invoice.value.total),
        ) >= 0.01,
);

function paymentMethodLabel(method: string | null | undefined) {
    const normalized = (method ?? "").toUpperCase();

    if (normalized === "CREDIT") return "Account credit";
    if (normalized === "CASH") return "Cash";
    if (normalized === "CREDIT-CARD") return "Credit card";
    if (normalized === "GCASH") return "GCash";

    return method || "Payment";
}

async function fetchInvoice() {
    loading.value = true;
    errorLabel.value = "";

    try {
        const response = await invoiceService.show(
            {
                invoice_code: invoiceCode.value,
                branch_uuid: uuid.value,
                mode: route.query.mode,
            },
            invoiceCode.value,
        );

        invoice.value = response.data ?? response;
    } catch (err) {
        console.error(err);
        errorLabel.value = "Unable to load this invoice.";
    } finally {
        loading.value = false;
    }
}

async function handleCashPay(cash: any) {
    pendingCash.value = cash;
    showConfirmPayment.value = true;
}
async function confirmCashPayment() {
    if (!invoice.value || !pendingCash.value) return;

    processingPayment.value = true;

    try {
        const res = await invoiceService.create({
            cash: pendingCash.value,
            mode: route.query.mode as string,
            payment_method: "CASH",
            invoice_code: invoiceCode.value,
            branch_uuid: uuid.value,
        });

        success(res.message ?? "Payment completed successfully.");

        paymentChange.value = Number(res.change ?? 0);

        if (res.receipt) {
            activeReceipt.value = res.receipt;
        }

        showConfirmPayment.value = false;
        pendingCash.value = null;

        await fetchInvoice();
    } catch (err: any) {
        error(err?.message ?? "Payment failed. Please try again.");
    } finally {
        processingPayment.value = false;
    }
}
function cancelPayment() {
    if (processingPayment.value) return;
    showConfirmPayment.value = false;
    pendingCash.value = null;
}

function goBack() {
    router.back();
}

function statusLabel(status: string) {
    return (status ?? "").replace(/_/g, " ");
}

function statusClasses(status: string) {
    const normalized = (status ?? "").toLowerCase();

    if (normalized === "written_off") {
        return "bg-[#FDF3DE] text-[#966B1F] dark:text-amber-300 dark:bg-amber-500/15";
    }
    if (normalized === "void") {
        return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";
    }

    if (normalized === "paid") {
        return "bg-[#E4F4EE] text-[#1F7A4D] dark:text-emerald-300 dark:bg-emerald-500/15";
    }
    if (normalized === "partial") {
        return "bg-[#E6F1FA] text-[#2563A6] dark:text-blue-300 dark:bg-blue-500/15";
    }
    if (normalized === "overdue") {
        return "bg-[#FBE8E6] text-[#B3402F] dark:text-rose-300 dark:bg-rose-500/15";
    }
    return "bg-[#FDF3DE] text-[#966B1F] dark:text-amber-300 dark:bg-amber-500/15";
}

function refundStatusClasses(status: string) {
    const normalized = (status ?? "").toLowerCase();

    if (normalized === "completed") {
        return "bg-[#E4F4EE] text-[#1F7A4D] dark:text-emerald-300 dark:bg-emerald-500/15";
    }
    if (normalized === "credited") {
        return "bg-[#E7F0FB] text-[#1F5C9E] dark:text-blue-300 dark:bg-blue-500/15";
    }
    if (
        normalized === "processing" ||
        normalized === "pending" ||
        normalized === "requested"
    ) {
        return "bg-[#FDF3DE] text-[#966B1F] dark:text-amber-300 dark:bg-amber-500/15";
    }
    if (
        normalized === "failed" ||
        normalized === "cancelled" ||
        normalized === "rejected"
    ) {
        return "bg-[#FBE8E6] text-[#B3402F] dark:text-rose-300 dark:bg-rose-500/15";
    }
    return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";
}

function formatAdjustmentType(type: string) {
    return (type ?? "")
        .replace(/_/g, " ")
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatMoney(amount: number | string | null | undefined) {
    return formatAmount(amount, { treatMissingAsZero: true });
}

function formatDate(value: string | null | undefined) {
    if (!value) return "—";

    return new Date(value).toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

onMounted(() => {
    fetchInvoice();
});

const Field = (fieldProps: { label: string; value: any }, { slots }: any) =>
    h("p", { class: "flex flex-col gap-0.5" }, [
        h(
            "span",
            { class: "text-xs text-[#6B8A87] dark:text-gray-400" },
            fieldProps.label,
        ),
        h(
            "span",
            { class: "text-[#16302E] font-medium dark:text-white" },
            slots.value ? slots.value() : (fieldProps.value ?? "—"),
        ),
    ]);
Field.props = ["label", "value"];

const SectionHeader = (_props: unknown, { slots }: any) =>
    h(
        "h3",
        {
            class: "flex items-center gap-2 text-xs font-mono uppercase tracking-widest text-[#0E7C7B] mb-4 dark:text-accent-300",
        },
        [slots.icon?.(), slots.default?.()],
    );
</script>
