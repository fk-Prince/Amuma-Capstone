<template>
    <tr class="group hover:bg-[#F7FAF9] transition-colors">
        <td class="py-4 pl-6 pr-3 whitespace-nowrap">
            <span
                class="inline-flex items-center gap-2 text-xs px-2 py-1 rounded-md bg-[#EAF4F2] text-[#0E7C7B]"
            >
                <span
                    class="w-1.5 h-1.5 rounded-full"
                    :class="statusDotClasses(booking.status)"
                />
                #{{ booking.reference_id }}
            </span>
        </td>

        <td class="py-4 px-3 min-w-[220px]">
            <div class="flex items-center gap-3 min-w-0">
                <!-- <img
                    :src="booking.user.avatar"
                    alt="User Avatar"
                    class="w-9 h-9 rounded-full object-cover shrink-0"
                /> -->

                <div class="min-w-0">
                    <p class="font-semibold text-[#16302E] truncate text-sm">
                        {{
                            fullName(
                                booking.booking_data.patient?.first_name,
                                booking.booking_data.patient?.middle_name,
                                booking.booking_data.patient?.last_name,
                            )
                        }}
                    </p>
                    <p class="text-xs text-muted truncate">
                        {{ booking.booking_data.service.address }}
                    </p>
                </div>
            </div>
        </td>

        <td class="py-4 px-3 text-sm text-muted whitespace-nowrap">
            {{
                booking.booking_data?.service?.type === "Medical"
                    ? "Medical Services"
                    : (booking.booking_data?.service?.type ?? "—")
            }}
        </td>

        <td class="py-4 px-3 text-sm text-muted whitespace-nowrap">
            {{
                booking.booking_data?.service?.date
                    ? formatDate(booking.booking_data.service.date)
                    : "—"
            }}
        </td>

        <!-- <td
            class="py-4 px-3 text-sm font-medium text-[#16302E] whitespace-nowrap"
        >
            {{ formatCurrency(totalPrice(booking)) }}
        </td> -->

        <td class="py-4 px-3 whitespace-nowrap">
            <span
                class="px-3 py-1 rounded-full text-xs font-medium capitalize"
                :class="statusClasses(booking.status)"
            >
                {{ booking.status }}
            </span>
        </td>

        <td class="py-4 pl-3 pr-6 whitespace-nowrap">
            <div class="flex items-center justify-end gap-2">
                <button
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

const props = defineProps<{
    booking: any;
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

    await router.push({
        path: route.path,
        query: {
            ...route.query,
            reference_id: String(props.booking.reference_id),
        },
    });
}

function totalPrice(booking: any) {
    const services = booking.booking_data?.service?.services ?? [];
    return services.reduce(
        (sum: number, s: any) => sum + (Number(s.price) || 0),
        0,
    );
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

function formatCurrency(value?: number) {
    if (value === undefined || value === null || isNaN(Number(value)))
        return "—";
    return `₱${Number(value).toLocaleString("en-PH", { minimumFractionDigits: 2 })}`;
}

function statusClasses(status?: string) {
    const s = (status ?? "").toLowerCase();

    if (s.includes("confirm") || s.includes("approved")) {
        return "bg-[#E4F4EE] text-[#1F7A4D]";
    }

    if (s.includes("complete")) {
        return "bg-[#E6F1FA] text-[#2563A6]";
    }

    if (s.includes("reject") || s.includes("declin") || s.includes("cancel")) {
        return "bg-[#FBE8E6] text-[#B3402F]";
    }

    return "bg-[#FDF3DE] text-[#966B1F]";
}

function statusDotClasses(status?: string) {
    const s = (status ?? "").toLowerCase();

    if (s.includes("confirm") || s.includes("approved")) return "bg-[#1F7A4D]";
    if (s.includes("complete")) return "bg-[#2563A6]";
    if (s.includes("reject") || s.includes("declin") || s.includes("cancel"))
        return "bg-[#B3402F]";

    return "bg-[#966B1F]";
}
</script>
