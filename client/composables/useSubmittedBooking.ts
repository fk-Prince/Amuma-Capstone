import { onBeforeUnmount, ref } from "vue";
import { patientAccessService } from "~/api/patient-access/PatientAccessService";
import { paymentService } from "~/api/payment/PaymentService";
import type { PaymentReceipt } from "~/types/receipt";

const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

export const useSubmittedBooking = () => {
    const booking = ref<any>(null);
    const waiting = ref(false);
    const receipt = ref<PaymentReceipt | null>(null);
    const openingReceipt = ref(false);

    let cancelled = false;
    onBeforeUnmount(() => {
        cancelled = true;
    });

    const find = async (reference: string) => {
        const res = await patientAccessService.retrieveAction({
            action: "bookings",
            page: 1,
            per_page: 10,
        });

        return (
            (res?.data ?? []).find((item: any) => item.reference_id === reference) ??
            null
        );
    };

    const waitForReceipt = async (reference: string, attempts = 8, delay = 1500) => {
        waiting.value = true;

        try {
            for (let attempt = 0; attempt < attempts && !cancelled; attempt++) {
                try {
                    booking.value = (await find(reference)) ?? booking.value;
                } catch {}

                if (booking.value?.receipt_code) break;

                await sleep(delay);
            }
        } finally {
            waiting.value = false;
        }

        return booking.value;
    };

    const openReceipt = async () => {
        const code = booking.value?.receipt_code;
        if (!code || openingReceipt.value) return;

        openingReceipt.value = true;

        try {
            const res: any = await paymentService.receipt({ payment_code: code });
            receipt.value = res?.data ?? res ?? null;
        } finally {
            openingReceipt.value = false;
        }
    };

    return { booking, waiting, receipt, openingReceipt, waitForReceipt, openReceipt };
};
