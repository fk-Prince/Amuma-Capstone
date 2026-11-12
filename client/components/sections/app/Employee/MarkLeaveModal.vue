<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { CalendarOff, X } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import WorkloadNotice from "~/components/sections/app/Employee/WorkloadNotice.vue";
import { leaveTypes, type LeaveDetails, type LeaveType } from "~/types/employee";
import { toLocalDateString } from "~/utils/time";

const props = defineProps<{
    open: boolean;
    name: string;
    employeeUuid?: string | null;
    branchUuid: string;
    loading?: boolean;
}>();

const unassign = ref(true);
const work = ref({ loading: true, blocked: false, count: 0 });

const emit = defineEmits<{
    close: [];
    confirm: [details: LeaveDetails];
}>();

const type = ref<LeaveType | "">("");
const reason = ref("");
const endsOn = ref("");
const errors = ref<Record<string, string>>({});

const tomorrow = computed(() => {
    const date = new Date();
    date.setDate(date.getDate() + 1);
    return toLocalDateString(date);
});

watch(
    () => props.open,
    (open) => {
        if (!open) return;

        type.value = "";
        reason.value = "";
        endsOn.value = "";
        errors.value = {};
        unassign.value = true;
    },
);

function close() {
    if (props.loading) return;
    emit("close");
}

function submit() {
    const next: Record<string, string> = {};

    if (!type.value) next.type = "Choose a leave type.";
    if (!reason.value.trim()) next.reason = "Give the reason for this leave.";

    errors.value = next;

    if (Object.keys(next).length || work.value.loading || work.value.blocked) return;

    emit("confirm", {
        type: type.value as LeaveType,
        reason: reason.value.trim(),
        ends_on: endsOn.value || null,
        unassign_work: work.value.count > 0 && unassign.value,
    });
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
            @click.self="close"
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="mark-leave-title"
                class="flex max-h-[90dvh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary"
            >
                <header
                    class="flex items-start gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                >
                    <span
                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600 ring-1 ring-amber-100 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20"
                    >
                        <CalendarOff class="h-5 w-5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <h3
                            id="mark-leave-title"
                            class="text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            Mark {{ name }} as on leave
                        </h3>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-gray-400">
                            They'll be left out of scheduling until they're back.
                        </p>
                    </div>

                    <button
                        type="button"
                        aria-label="Close"
                        :disabled="loading"
                        class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:opacity-40 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-white"
                        @click="close"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </header>

                <div class="space-y-5 overflow-y-auto px-6 py-5">
                    <div>
                        <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            Type <span class="text-red-500">*</span>
                        </p>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="item in leaveTypes"
                                :key="item.value"
                                type="button"
                                :disabled="loading"
                                class="rounded-full border px-3.5 py-1.5 text-sm font-medium transition"
                                :class="
                                    type === item.value
                                        ? 'border-primary bg-primary/10 text-primary dark:border-primary-400 dark:text-primary-300'
                                        : 'border-slate-200 text-slate-600 hover:border-slate-300 dark:border-white/10 dark:text-gray-300 dark:hover:border-white/20'
                                "
                                @click="
                                    type = item.value;
                                    delete errors.type;
                                "
                            >
                                {{ item.label }}
                            </button>
                        </div>

                        <p
                            v-if="errors.type"
                            class="mt-1.5 text-xs text-red-500 dark:text-red-400"
                        >
                            {{ errors.type }}
                        </p>
                    </div>

                    <BaseInput
                        v-model="reason"
                        label="Reason"
                        mode="textarea"
                        :rows="3"
                        :textMax="255"
                        placeholder="e.g. Hospitalized for dengue, doctor advised 2 weeks rest"
                        :error="errors.reason"
                        :disabled="loading"
                        required
                        @update:modelValue="delete errors.reason"
                    />

                    <div>
                        <DatePickerField
                            v-model="endsOn"
                            label="Back on (optional)"
                            :min="tomorrow"
                            :default-to-today="false"
                            placeholder="Select a date"
                            :disabled="loading"
                        />
                        <p class="mt-1.5 text-xs text-slate-400 dark:text-gray-500">
                            They return to Active automatically on this date.
                            Leave it empty if you don't know yet.
                        </p>
                    </div>

                    <WorkloadNotice
                        :open="open"
                        :employee-uuid="employeeUuid"
                        :branch-uuid="branchUuid"
                        :name="name"
                        mode="leave"
                        :until="endsOn || null"
                        v-model:unassign="unassign"
                        @state="work = $event"
                    />
                </div>

                <footer
                    class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 dark:border-white/10 dark:bg-white/5"
                >
                    <button
                        type="button"
                        :disabled="loading"
                        class="rounded-lg px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-200/60 disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/15"
                        @click="close"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="loading || work.loading || work.blocked"
                        class="rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="submit"
                    >
                        {{ loading ? "Saving…" : "Mark as on leave" }}
                    </button>
                </footer>
            </div>
        </div>
    </Teleport>
</template>
