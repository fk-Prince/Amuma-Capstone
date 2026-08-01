import { useBookingStore } from "~/stores/booking";

export default defineNuxtRouteMiddleware(async (to) => {
    const bookingStore = useBookingStore();

    const hasPatient =
        Object.keys(bookingStore.patient ?? {}).length > 0;

    const hasGuardian =
        Object.keys(bookingStore.guardian ?? {}).length > 0;

    const hasAssessment =
        Object.keys(bookingStore.assessment ?? {}).length > 0;

    const hasService =
        bookingStore.category === "facility"
            ? Object.keys(bookingStore.facility ?? {}).length > 0
            : Object.keys(bookingStore.homecare ?? {}).length > 0;

    const hasBooking =
        hasPatient &&
        hasGuardian &&
        hasAssessment &&
        hasService;

    if (!hasBooking) {

        if (bookingStore.category) {
            return await navigateTo({
                path: `/booking/provider/${to.params.branch_uuid}/details`,
                query: {
                    category: bookingStore.category,
                },
            });
        } else {
            return await navigateTo(`/booking/provider/${to.params.branch_uuid}`);
        }
    }
});