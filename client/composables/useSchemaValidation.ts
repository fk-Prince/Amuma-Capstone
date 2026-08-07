// import { computed, ref, type ComputedRef } from "vue";
// import type { ZodTypeAny } from "zod";

// export function flattenErrors(
//     fieldErrors: Record<string, string[] | undefined>,
// ): Record<string, string> {
//     return Object.fromEntries(
//         Object.entries(fieldErrors).map(([key, messages]) => [
//             key,
//             messages?.[0] ?? "Invalid value",
//         ]),
//     );
// }

// export function useSchemaValidation<T>(
//     schema: ZodTypeAny | ComputedRef<ZodTypeAny>,
//     data: T,
// ) {
//     const errors = ref<Record<string, string>>({});

//     const resolvedSchema = computed<ZodTypeAny>(() =>
//         (schema as ComputedRef<ZodTypeAny>)?.value !== undefined
//             ? (schema as ComputedRef<ZodTypeAny>).value
//             : (schema as ZodTypeAny),
//     );

//     const isValid = computed(() =>
//         resolvedSchema.value.safeParse(data).success
//     );

//     function validate(): boolean {
//         const result = resolvedSchema.value.safeParse(data);

//         if (result.success) {
//             errors.value = {};
//             return true;
//         }

//         errors.value = flattenErrors(
//             result.error.flatten().fieldErrors,
//         );
//         return false;
//     }

//     function clearError(field: string) {
//         if (!errors.value[field]) return;

//         const updated = {
//             ...errors.value,
//         };

//         delete updated[field];

//         errors.value = updated;
//     }

//     function reset() {
//         errors.value = {};
//     }

//     return {
//         errors,
//         isValid,
//         validate,
//         clearError,
//         reset,
//     };
// }

import { computed, ref, toValue, type ComputedRef } from "vue";
import type { ZodTypeAny } from "zod";

export function flattenErrors(
    fieldErrors: Record<string, string[] | undefined>,
): Record<string, string> {
    return Object.fromEntries(
        Object.entries(fieldErrors).map(([key, messages]) => [
            key,
            messages?.[0] ?? "Invalid value",
        ]),
    );
}

export function useSchemaValidation<T>(
    schema: ZodTypeAny | ComputedRef<ZodTypeAny>,
    data: T,
) {
    const errors = ref<Record<string, string>>({});

    const resolvedSchema = computed<ZodTypeAny>(() =>
        (schema as ComputedRef<ZodTypeAny>)?.value !== undefined
            ? (schema as ComputedRef<ZodTypeAny>).value
            : (schema as ZodTypeAny),
    );

    const resolvedData = computed(() => toValue(data));

    const isValid = computed(() =>
        resolvedSchema.value.safeParse(
            resolvedData.value,
        ).success,
    );

    function validate(): boolean {
        const result = resolvedSchema.value.safeParse(
            resolvedData.value,
        );

        if (result.success) {
            errors.value = {};
            return true;
        }

        errors.value = flattenErrors(
            result.error.flatten().fieldErrors,
        );

        return false;
    }

    function clearError(field: string) {
        if (!errors.value[field]) return;

        const updated = {
            ...errors.value,
        };

        delete updated[field];

        errors.value = updated;
    }

    function reset() {
        errors.value = {};
    }

    return {
        errors,
        isValid,
        validate,
        clearError,
        reset,
    };
}