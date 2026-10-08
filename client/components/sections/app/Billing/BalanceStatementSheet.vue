<template>
    <div class="flex min-h-[255mm] flex-col bg-white text-black">
        <header class="flex items-start justify-between gap-6 border-b border-gray-300 pb-5">
            <div class="flex items-start gap-4">
                <img
                    v-if="branch.image"
                    :src="branch.image"
                    :alt="branch.name"
                    class="h-16 w-16 rounded-lg object-cover"
                />

                <div class="min-w-0">
                    <p class="text-lg font-bold">{{ branch.name }}</p>
                    <p v-if="branch.address" class="mt-0.5 text-xs leading-5 text-gray-600">
                        {{ branch.address }}
                    </p>
                    <p v-if="branch.contact" class="text-xs text-gray-600">
                        {{ branch.contact }}
                    </p>
                </div>
            </div>

            <div class="shrink-0 text-right">
                <p class="text-base font-bold uppercase tracking-wide">
                    Statement of Balance
                </p>
                <p class="mt-1 text-xs text-gray-600">{{ issuedOn }}</p>
            </div>
        </header>

        <section class="mt-5">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-500">
                Billed to
            </p>
            <p class="mt-0.5 text-sm font-semibold">{{ patientName }}</p>
        </section>

        <table class="mt-6 w-full border-collapse text-sm">
            <thead>
                <tr class="border-b-2 border-gray-800">
                    <th class="py-2 text-left font-semibold">Description</th>
                    <th class="py-2 text-right font-semibold">Amount due</th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="(line, index) in lines"
                    :key="index"
                    class="border-b border-gray-200"
                >
                    <td class="py-2.5 pr-4 align-top">{{ line.description }}</td>
                    <td class="whitespace-nowrap py-2.5 text-right align-top tabular-nums">
                        ₱{{ formatAmount(line.amount) }}
                    </td>
                </tr>
            </tbody>

            <tfoot>
                <tr class="border-t-2 border-gray-800">
                    <td class="py-3 font-bold">Total amount due</td>
                    <td class="whitespace-nowrap py-3 text-right text-base font-bold tabular-nums">
                        ₱{{ formatAmount(total) }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <footer class="mt-auto flex items-end justify-between gap-10 border-t border-gray-300 pt-5">
            <p class="max-w-md text-[11px] leading-5 text-gray-600">
                This statement lists the balances still to be paid on this
                patient's account as of the date above. Payments made after it
                was printed are not reflected. Please settle at the branch
                counter or through the AMUMA family portal, and keep this copy
                for your records.
            </p>

            <div class="w-56 shrink-0 text-center">
                <p class="border-b border-gray-800 pb-1 text-sm font-semibold">
                    {{ issuedBy }}
                </p>
                <p class="mt-1 text-[10px] uppercase tracking-widest text-gray-500">
                    Issued by
                </p>
            </div>
        </footer>

        <section
            v-if="transactions?.length"
            class="mt-8 break-before-page pt-2 [page-break-before:always]"
        >
            <header class="flex items-start justify-between gap-6 border-b border-gray-300 pb-4">
                <div class="min-w-0">
                    <p class="text-lg font-bold">{{ branch.name }}</p>
                    <p class="mt-0.5 text-xs text-gray-600">{{ patientName }}</p>
                </div>

                <div class="shrink-0 text-right">
                    <p class="text-base font-bold uppercase tracking-wide">
                        Transaction History
                    </p>
                    <p class="mt-1 text-xs text-gray-600">{{ issuedOn }}</p>
                </div>
            </header>

            <table class="mt-5 w-full border-collapse text-xs">
                <thead>
                    <tr class="border-b-2 border-gray-800">
                        <th class="py-2 pr-2 text-left font-semibold">Date</th>
                        <th class="py-2 pr-2 text-left font-semibold">Transaction</th>
                        <th class="py-2 pr-2 text-left font-semibold">Type</th>
                        <th class="py-2 pr-2 text-left font-semibold">Status</th>
                        <th class="py-2 pr-2 text-left font-semibold">Method</th>
                        <th class="py-2 text-right font-semibold">Amount</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="(row, index) in transactions"
                        :key="index"
                        class="break-inside-avoid border-b border-gray-200"
                    >
                        <td class="py-2 pr-2 align-top">{{ formatDateTime(row.created_at) }}</td>
                        <td class="py-2 pr-2 align-top font-semibold">
                            {{ row.transaction_code ?? "—" }}
                        </td>
                        <td class="py-2 pr-2 align-top">
                            {{ label(row.type) }} · {{ label(row.direction) }}
                        </td>
                        <td class="py-2 pr-2 align-top">{{ label(row.status) }}</td>
                        <td class="py-2 pr-2 align-top">{{ label(row.method) }}</td>
                        <td class="whitespace-nowrap py-2 text-right align-top tabular-nums">
                            ₱{{ formatAmount(row.amount) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

<script setup lang="ts">
import { formatAmount } from "~/utils/currency";

function label(value?: string | null) {
    if (!value) return "—";

    return value
        .replace(/[_-]+/g, " ")
        .toLowerCase()
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatDateTime(value?: string | null) {
    if (!value) return "—";

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime())
        ? "—"
        : parsed.toLocaleString("en-US", {
              month: "short",
              day: "numeric",
              year: "numeric",
              hour: "numeric",
              minute: "2-digit",
          });
}

defineProps<{
    branch: {
        name: string;
        image?: string | null;
        address?: string | null;
        contact?: string | null;
    };
    patientName: string;
    lines: { description: string; amount: number }[];
    total: number;
    issuedBy: string;
    issuedOn: string;
    transactions?: {
        transaction_code?: string | null;
        type?: string | null;
        direction?: string | null;
        status?: string | null;
        method?: string | null;
        amount: number;
        created_at?: string | null;
    }[];
}>();
</script>
