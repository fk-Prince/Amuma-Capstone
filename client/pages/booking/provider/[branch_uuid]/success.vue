<template>
    <div
        class="min-h-[calc(100vh-90px)] bg-slate-200 flex items-center justify-center px-5 py-12"
    >
        <div class="max-w-lg w-full">
            <div
                class="rounded-2xl border border-gray-100 bg-white shadow-sm p-8 md:p-10 text-center"
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary/10"
                >
                    <CheckCircle2 class="h-9 w-9 text-primary" />
                </div>

                <h1 class="font-serif text-2xl text-gray-900 mt-6">
                    Booking Request Submitted
                </h1>

                <p class="text-[15px] text-gray-500 mt-2 leading-relaxed">
                    Thank you. Your
                    {{
                        category === "facility"
                            ? "facility admission"
                            : "homecare service"
                    }}
                    request has been sent to the care team for review.
                </p>

                <div
                    v-if="referenceId"
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-gray-50 border border-gray-100 px-4 py-2"
                >
                    <span class="text-xs text-gray-400">Reference No.</span>
                    <span class="text-sm font-semibold text-gray-700">
                        {{ referenceId }}
                    </span>
                </div>

                <div class="mt-8 flex flex-col gap-3">
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left"
                    >
                        <BellRing
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            You'll be notified once your booking request has
                            been reviewed and accepted.
                        </span>
                    </div>
                    <div
                        class="flex items-start gap-3 text-[13px] text-gray-500 text-left"
                    >
                        <ShieldCheck
                            class="h-4 w-4 shrink-0 mt-0.5 text-primary"
                        />
                        <span>
                            All patient information is kept confidential and
                            used only to provide the best care possible.
                        </span>
                    </div>
                </div>

                <div class="mt-8 flex flex-col sm:flex-row gap-3">
                    <BaseButton
                        variant="secondary"
                        class="w-full rounded-xl py-3"
                        @click="viewBookings"
                    >
                        View My Bookings
                    </BaseButton>
                    <BaseButton
                        variant="primary"
                        class="w-full rounded-xl py-3"
                        @click="goHome"
                    >
                        Back to Home
                    </BaseButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { CheckCircle2, BellRing, ShieldCheck } from "lucide-vue-next";
import BaseButton from "~/components/ui/BaseButton.vue";
import { useBookingStore } from "~/stores/booking";
import { fetchAuthUser } from "~/composables/useAuthUser";

useHead({ title: "Booking Submitted" });
definePageMeta({
    navVariant: 4,
    navTheme: "dark",
});

const router = useRouter();
const bookingStore = useBookingStore();

const category = computed<"homecare" | "facility">(
    () => bookingStore.category ?? "homecare",
);

const referenceId = computed(() => bookingStore.lastSubmittedId ?? "");

onMounted(() => {
    bookingStore.$reset?.();
    fetchAuthUser();
});

function goHome() {
    router.push("/");
}

function viewBookings() {
    router.push("/portal/bookings");
}
</script>
