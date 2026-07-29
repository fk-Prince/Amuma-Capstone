import { ref } from "vue";
import type { ZodType } from "zod";
import { Stethoscope, Users } from "lucide-vue-next";

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


import { computed, type MaybeRefOrGetter, toValue } from "vue";


export function useMedicalServices(
    services: any,
    selectedServices?: any,
    adlRatePerHour?: any,
    minAdlHours?: any
) {
    const medicalRateLabel = computed(() => {
        const list = toValue(services);

        if (!list.length) return "No services";

        const prices = list
            .map((service: any) => Number(service.price))
            .filter((price: any) => !isNaN(price));

        if (!prices.length) return "No pricing";

        const min = Math.min(...prices);
        const max = Math.max(...prices);

        if (min === max) {
            return `₱${min.toLocaleString()}`;
        }

        return `₱${min.toLocaleString()} - ₱${max.toLocaleString()}`;
    });

    const medicalDescription = computed(() => {
        const list = toValue(services);

        if (!list.length) {
            return "No medical services available";
        }

        const names = list.map((service: any) => service.service_name);

        if (names.length <= 3) {
            return names.join(", ");
        }

        return `${names.slice(0, 3).join(", ")} and ${names.length - 3} more`;
    });

    const selectedServiceLabel = computed(() => {
        const list = toValue(selectedServices) ?? [];

        return list.length
            ? list.map((service: any) => service.service_name).join(", ")
            : "";
    });

    const selectedServicesTotal = computed(() => {
        const list = toValue(selectedServices) ?? [];

        return list.reduce(
            (sum: any, service: any) => sum + Number(service.price || 0),
            0
        );
    });

    const bookingTypes = computed(() => [
        {
            value: "Medical",
            title: "Medical Services",
            description: medicalDescription.value,
            icon: Stethoscope,
            rateLabel: medicalRateLabel.value,
        },
        {
            value: "ADL",
            title: "Caregiver (ADL Services)",
            description: "Daily assistance like bathing, feeding, dressing",
            icon: Users,
            rateLabel: `₱${toValue(adlRatePerHour).toLocaleString()} / hour • Min ${toValue(minAdlHours)} hrs`,
        },
    ]);



    return {
        bookingTypes,
        selectedServiceLabel,
        selectedServicesTotal,
        medicalRateLabel,
        medicalDescription,
    };
}

