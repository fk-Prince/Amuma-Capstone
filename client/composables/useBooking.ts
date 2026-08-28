import { ref } from "vue";
import type { ZodType } from "zod";
import { Stethoscope, Users } from "lucide-vue-next";
import { formatCurrency } from "~/utils/currency";

function flattenErrors(fieldErrors: Record<string, string[] | undefined>) {
    return Object.fromEntries(
        Object.entries(fieldErrors).map(([key, messages]) => [
            key,
            messages?.[0] ?? "Invalid value",
        ]),
    );
}

export function useBookingValidator<T>(schema: ZodType<T>, data: T) {
    const errors = ref<Record<string, string>>({});

    function validate(): boolean {
        const result = schema.safeParse(data);

        if (result.success) {
            errors.value = {};
            return true;
        }

        errors.value = flattenErrors(result.error.flatten().fieldErrors);
        return false;
    }

    const isValid = () => schema.safeParse(data).success;

    return { errors, validate, isValid };
}



export function useMedicalServices(
    services: any,
    selectedServices?: any,
    adlRatePerHour?: any,
    minAdlHours?: any,
    adlDescription?: string,
) {

    const serviceList = computed(() => {
        const value = toValue(services);
        return Array.isArray(value) ? value : [];
    });


    const medicalRateLabel = computed(() => {
        const list = serviceList.value;

        if (!list.length) {
            return "No services";
        }

        const prices = list
            .map((service: any) => Number(service.price))
            .filter((price: number) => !Number.isNaN(price));

        if (!prices.length) {
            return "No pricing";
        }

        const min = Math.min(...prices);
        const max = Math.max(...prices);

        if (min === max) {
            return formatCurrency(min);
        }

        return `${formatCurrency(min)} - ${formatCurrency(max)}`;
    });

    const medicalDescription = computed(() => {
        if (!serviceList.value.length) {
            return "No medical services available";
        }

        return "Professional medical care and nursing services tailored to the patient's needs.";
    });

    // const medicalDescription = computed(() => {
    //     const list = serviceList.value;

    //     if (!list.length) {
    //         return "No medical services available";
    //     }

    //     const names = list
    //         .map((service: any) => service.service_name)
    //         .filter(Boolean);

    //     if (!names.length) {
    //         return "No medical services available";
    //     }

    //     if (names.length <= 3) {
    //         return names.join(", ");
    //     }

    //     return `${names.slice(0, 3).join(", ")} and ${names.length - 3
    //         } more`;
    // });


    const selectedService = computed(() => {
        const value = toValue(selectedServices);

        if (!value) {
            return null;
        }

        if (Array.isArray(value)) {
            return value[0] ?? null;
        }

        return value;
    });

    /**
     * Selected service label
     */
    const selectedServiceLabel = computed(() => {
        return selectedService.value?.service_name ?? "";
    });

    /**
     * Selected service total
     *
     * Because only ONE service can be selected,
     * this is simply that service's price.
     */
    const selectedServicesTotal = computed(() => {
        if (!selectedService.value) {
            return 0;
        }

        return Number(selectedService.value.price || 0);
    });

    /**
     * Booking types
     */
    const bookingTypes = computed(() => {
        const list = serviceList.value;

        const adlRate = Number(toValue(adlRatePerHour) || 0);

        return [
            {
                value: "Medical",
                title: "Medical Services",
                description: medicalDescription.value,
                icon: Stethoscope,
                rateLabel: medicalRateLabel.value,

                // IMPORTANT:
                // Use the resolved array.
                visible: list.length > 0,
            },

            {
                value: "ADL",
                title: "Caregiver (ADL Services)",
                description:
                    adlDescription ||
                    "Assistance with daily living activities",
                icon: Users,
                rateLabel: `${formatCurrency(adlRate)} / hour`,
                visible: adlRate > 0,
            },
        ];
    });

    return {
        bookingTypes,
        selectedService,
        selectedServiceLabel,
        selectedServicesTotal,
        medicalRateLabel,
        medicalDescription,
        serviceList,
    };
}