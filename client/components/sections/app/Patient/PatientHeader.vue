<script setup lang="ts">
import { computed } from "vue";

import { calculateAge } from "~/utils/user";
import {
    Calendar,
    MapPin,
    Pencil,
    Printer,
    Send,
    Share2,
    UserRound,
} from "lucide-vue-next";

import type { PatientRetrieve } from "~/types/patient";
import { formatDate } from "~/utils/time";
import ActionButton from "~/components/ui/ActionButton.vue";

const { hasRole } = usePermissions();

const canManagePatient = computed(() => hasRole("admission"));

const props = defineProps<{
    patient: PatientRetrieve;
}>();

const emit = defineEmits<{ print: [] }>();

function fullName(
    firstName?: string | null,
    middleName?: string | null,
    lastName?: string | null,
) {
    return [firstName, middleName, lastName].filter(Boolean).join(" ");
}

const facts = computed(() => [
    {
        label: "Date of birth",
        value: formatDate(props.patient.date_of_birth),
        icon: Calendar,
    },
    {
        label: "Age",
        value: calculateAge(props.patient.date_of_birth, false),
        icon: UserRound,
    },
    {
        label: "Address",
        value: props.patient.location?.full_address || "No address provided",
        icon: MapPin,
    },
]);

const actions = [
    { label: "Share", icon: Share2 },
    { label: "Send", icon: Send },
    { label: "Print", icon: Printer, onClick: () => emit("print") },
    { label: "Edit", icon: Pencil },
] as const;
</script>

<template>
    <!-- Sits inside the page's single card, so it draws no border of its own. -->
    <div
        class="border-b border-gray-100 px-4 py-4 sm:px-5 sm:py-5 dark:border-white/10"
    >
        <div class="flex items-start gap-3 sm:gap-4">
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-base font-semibold text-white sm:h-14 sm:w-14 sm:text-xl"
            >
                {{ patient.first_name.charAt(0) }}
            </div>

            <div class="min-w-0 flex-1">
                <h1
                    class="truncate text-base font-semibold text-gray-900 sm:text-lg dark:text-white"
                >
                    {{
                        fullName(
                            patient.first_name,
                            patient.middle_name,
                            patient.last_name,
                        )
                    }}
                </h1>

                <!-- Label above value, packed rather than gridded, so the
                     address takes the room it needs without stranding the
                     short fields on their own row. -->
                <dl class="mt-2.5 flex flex-wrap gap-x-6 gap-y-2.5">
                    <div
                        v-for="fact in facts"
                        :key="fact.label"
                        class="flex min-w-0 max-w-full items-start gap-1.5"
                    >
                        <component
                            :is="fact.icon"
                            class="mt-0.5 h-4 w-4 shrink-0 text-primary"
                        />

                        <div class="min-w-0">
                            <dt
                                class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                            >
                                {{ fact.label }}
                            </dt>

                            <dd
                                class="truncate text-[13px] font-medium text-slate-700 dark:text-gray-200"
                                :title="fact.value"
                            >
                                {{ fact.value }}
                            </dd>
                        </div>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Below the details on a phone, where there is no room beside the
             name, and scrollable rather than wrapping into stacked rows. -->
        <div
            class="-mx-4 mt-4 flex gap-1 overflow-x-auto px-4 scrollbar-none sm:mx-0 sm:mt-3 sm:justify-end sm:overflow-visible sm:px-0"
        >
            <ActionButton
                v-for="action in actions"
                :key="action.label"
                variant="outline"
                extra-class="shrink-0 border-transparent dark:border-transparent px-3 py-1.5 text-gray-500 hover:bg-primary-50 hover:text-primary dark:text-gray-400 dark:hover:bg-primary-500/10"
                :disabled="!canManagePatient"
                :tooltip="
                    canManagePatient ? '' : 'Only admission staff can do this.'
                "
                @click="action.onClick?.()"
            >
                <component :is="action.icon" class="h-4 w-4" />
                {{ action.label }}
            </ActionButton>
        </div>
    </div>
</template>
