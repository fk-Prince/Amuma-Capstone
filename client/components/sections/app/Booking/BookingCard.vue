<template>
    <tr class="group hover:bg-[#F7FAF9] transition-colors">
        <td class="py-4 pl-6 pr-3 whitespace-nowrap">
            <div class="flex flex-col gap-1">
                <span
                    class="inline-flex items-center gap-2 text-xs px-2 py-1 rounded-md bg-[#EAF4F2] text-[#0E7C7B] w-fit"
                >
                    <span
                        class="inline-block w-1.5 h-1.5 rounded-full shrink-0"
                        :class="statusDotClasses(booking.status)"
                    />
                    #{{ booking.reference_id }}
                </span>

                <span class="text-[11px] text-gray-400">
                    {{ stringToDateTime(booking.created_at) }}
                </span>
            </div>
        </td>

        <td class="py-4 px-3 min-w-[220px]">
            <div class="flex items-center gap-3 min-w-0">
                <div class="min-w-0">
                    <p class="font-semibold text-[#16302E] truncate text-sm">
                        {{
                            fullName(
                                booking.patient?.first_name,
                                booking.patient?.middle_name,
                                booking.patient?.last_name,
                            )
                        }}
                    </p>
                    <p class="text-xs text-muted truncate">
                        {{
                            booking.homecare?.address ||
                            booking.patient?.address ||
                            ""
                        }}
                    </p>
                </div>
            </div>
        </td>

        <td class="py-4 px-3 text-sm text-muted whitespace-nowrap capitalize">
            {{ booking.category ?? "—" }}
        </td>

        <td class="py-4 px-3 text-sm text-muted whitespace-nowrap">
            {{
                booking.homecare?.type === "Medical"
                    ? "Medical Services"
                    : booking.facility?.type === "Complete"
                      ? "Complete Admission"
                      : booking.facility?.type === "Pre-Admission"
                        ? booking.facility?.type
                        : booking.homecare?.type === "ADL"
                          ? "Activity of Daily Living (ADL)"
                          : "—"
            }}
        </td>

        <td class="py-4 px-3 text-sm text-muted whitespace-nowrap">
            {{
                booking.category === "facility"
                    ? booking.facility?.admission_date
                        ? formatDate(booking.facility.admission_date)
                        : "—"
                    : booking.homecare?.date
                      ? formatDate(booking.homecare.date)
                      : "—"
            }}
        </td>
        <td v-if="booking.valid_until" class="py-4 px-3 whitespace-nowrap">
            <span class="px-3 py-1 rounded-full text-xs font-medium capitalize">
                {{ stringToDateTime(booking.valid_until) }}
            </span>
        </td>

        <td class="py-4 px-3 whitespace-nowrap">
            <span
                class="px-3 py-1 rounded-full text-xs font-medium capitalize"
                :class="statusClasses(booking.status)"
            >
                {{ formatStatus(booking.status) }}
            </span>
        </td>

        <td class="py-4 pl-3 pr-6 whitespace-nowrap">
            <div class="flex items-center justify-end gap-2">
                <button
                    v-if="
                        booking.status.toLowerCase() === 'pending' &&
                        booking.facility?.type !== 'Pre-Admission'
                    "
                    type="button"
                    @click.stop="emit('reject', booking)"
                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-red-300 text-red-600 hover:bg-red-50 transition"
                >
                    Reject
                </button>

                <button
                    type="button"
                    @click.stop="viewDetails"
                    class="px-3 py-1.5 text-xs font-medium rounded-md border border-[#E4EFED] text-[#16302E] hover:bg-[#F0F5F4] transition flex items-center gap-1 shrink-0"
                >
                    View
                    <svg
                        class="w-3.5 h-3.5 text-[#6B8A87]"
                        viewBox="0 0 20 20"
                        fill="none"
                    >
                        <path
                            d="M7.5 5L12.5 10L7.5 15"
                            stroke="currentColor"
                            stroke-width="1.75"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </button>
            </div>
        </td>
    </tr>
</template>

<script lang="ts" setup>
import { useRoute, useRouter } from "vue-router";
import { fullName } from "~/utils/user";
import { stringToDateTime } from "~/utils/time";
import {
    formatStatus,
    statusClasses,
    type BookingRetrieve,
} from "~/types/booking";

const props = defineProps<{
    booking: BookingRetrieve;
}>();

const emit = defineEmits<{
    (e: "reject", booking: any): void;
    (e: "confirm", booking: any): void;
    (e: "view-details", booking: any): void;
}>();

const route = useRoute();
const router = useRouter();

async function viewDetails() {
    if (!props.booking?.reference_id) return;

    emit("view-details", props.booking);
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
function statusDotClasses(status?: string) {
    const s = (status ?? "").toLowerCase().replace("-", "_");
    switch (s) {
        case "approved":
            return "bg-[#1F7A4D]";

        // case "in_progress":
        //     return "bg-[#2563A6]";

        // case "completed":
        //     return "bg-[#0E7C7B]";

        case "rejected":
        case "cancelled":
            return "bg-[#B3402F]";

        case "expired":
            return "bg-gray-400";

        case "pending":
        default:
            return "bg-[#966B1F]";
    }
}
</script>
