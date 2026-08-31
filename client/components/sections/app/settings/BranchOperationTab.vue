<template>
    <div class="w-full space-y-8">
        <div>
            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">
                Operation Settings
            </h2>

            <p class="text-sm text-slate-500 mt-1 dark:text-gray-400">
                Configure billing and branch operation preferences.
            </p>
        </div>

        <div class="border-b border-slate-200 pb-2 dark:border-white/10">
            <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Facility</h3>

            <p class="text-xs text-slate-400 mt-0.5 dark:text-gray-500">
                In-house admission, rooms and resident billing.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-1 dark:bg-transparent">
                <BaseInput
                    v-model="setting.reserved_walkin_slots"
                    label="Reserved Walk-in Slots"
                    mode="number"
                    placeholder="Example: 3"
                    :error="errors.reserved_walkin_slots"
                    @update:modelValue="clearError('reserved_walkin_slots')"
                />

                <p class="text-xs text-slate-400 mt-2 dark:text-gray-500">
                    Number of slots reserved for walk-in customers.
                </p>
            </div>

            <div class="bg-white p-1 dark:bg-transparent">
                <BaseInput
                    v-model="setting.termination_fee_percent"
                    label="Early Discharge Termination Fee (%)"
                    mode="number"
                    placeholder="Example: 0"
                    :error="errors.termination_fee_percent"
                    @update:modelValue="clearError('termination_fee_percent')"
                />

                <p class="text-xs text-slate-400 mt-2 dark:text-gray-500">
                    Percentage kept from what a resident already paid when they
                    are discharged within 7 days of admission. Leave at 0% to
                    always refund in full.
                </p>
            </div>

            <div class="bg-white p-1 md:col-span-2 dark:bg-transparent">
                <div class="flex gap-5 items-start">
                    <label class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            v-model="setting.enable_booking_pre_admission"
                            @change="clearError('enable_booking_pre_admission')"
                            class="h-4 w-4 mt-1 rounded border-slate-300 text-primary focus:ring-primary dark:border-white/20 dark:bg-white/5"
                        />

                        <div>
                            <p class="text-sm font-medium text-slate-700 dark:text-gray-300">
                                Enable Booking Pre-Admission
                            </p>

                            <p class="text-xs text-slate-400 dark:text-gray-500">
                                Allow client to fill up required details before
                                admission online.
                            </p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            v-model="setting.enable_booking_complete_admission"
                            @change="
                                clearError('enable_booking_complete_admission')
                            "
                            class="h-4 w-4 mt-1 rounded border-slate-300 text-primary focus:ring-primary dark:border-white/20 dark:bg-white/5"
                        />

                        <div>
                            <p class="text-sm font-medium text-slate-700 dark:text-gray-300">
                                Enable Booking Complete Admission
                            </p>

                            <p class="text-xs text-slate-400 dark:text-gray-500">
                                Allow bookings with payment and room reservation
                                before admission approval online.
                            </p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- <div class="bg-white p-1 dark:bg-transparent">
                <BaseInput
                    v-model="setting.billing_due_date"
                    label="Billing Due Date"
                    mode="number"
                    placeholder="Example: 15"
                    :error="errors.billing_due_date"
                    @update:modelValue="clearError('billing_due_date')"
                />

                <p class="text-xs text-slate-400 mt-2 dark:text-gray-500">
                    Day of the month when payment is due.
                </p>
            </div> -->
        </div>

        <div class="border-b border-slate-200 pb-2 dark:border-white/10">
            <h3 class="text-sm font-semibold text-slate-800 dark:text-white">Homecare</h3>

            <p class="text-xs text-slate-400 mt-0.5 dark:text-gray-500">
                Visits delivered at the client's own address.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-1 dark:bg-transparent">
                <BaseInput
                    v-model="setting.minimum_adl_hours"
                    label="Minimum Homecare Hours"
                    mode="number"
                    placeholder="Example: 4"
                    :error="errors.minimum_adl_hours"
                    @update:modelValue="clearError('minimum_adl_hours')"
                />

                <p class="text-xs text-slate-400 mt-2 dark:text-gray-500">
                    Shortest booking a client may request for a homecare visit.
                </p>
            </div>
        </div>

        <div class="border-b border-slate-200 pb-2 dark:border-white/10">
            <h3 class="text-sm font-semibold text-slate-800 dark:text-white">General</h3>

            <p class="text-xs text-slate-400 mt-0.5 dark:text-gray-500">
                Applies to the whole branch, both facility and homecare.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-1 dark:bg-transparent">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-gray-300">
                            Branch Status
                        </p>

                        <p class="text-xs text-slate-400 mt-1 dark:text-gray-500">
                            Open or close branch operation.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="
                            setting.is_open = !setting.is_open;
                            clearError('is_open');
                        "
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                        :class="setting.is_open ? 'bg-primary' : 'bg-slate-300 dark:bg-white/10'"
                    >
                        <span
                            class="h-4 w-4 rounded-full bg-white shadow transition-transform"
                            :class="
                                setting.is_open
                                    ? 'translate-x-6'
                                    : 'translate-x-1'
                            "
                        />
                    </button>
                </div>

                <div class="mt-3">
                    <span
                        class="inline-flex rounded-full px-3 py-1 text-xs font-medium"
                        :class="
                            setting.is_open
                                ? 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400'
                                : 'bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400'
                        "
                    >
                        {{ setting.is_open ? "Open" : "Closed" }}
                    </span>
                </div>
            </div>
        </div>

        <div class="pt-2">
            <div class="grid grid-cols-2 gap-4">
                <Combobox
                    v-model="setting.currency"
                    :items="currencies"
                    placeholder="Select currency"
                    class="w-full"
                    label="Currency"
                    :error="errors.currency"
                    @update:modelValue="clearError('currency')"
                />

                <Combobox
                    v-model="setting.time_zone"
                    :items="timeZones"
                    placeholder="Select time-zone"
                    class="w-full"
                    label="Time-Zone"
                    :error="errors.time_zone"
                    @update:modelValue="clearError('time_zone')"
                />
            </div>

            <div class="flex flex-col mt-3">
                <label class="text-sm font-semibold mb-1 text-slate-700 dark:text-gray-300">
                    Business Hours
                </label>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm mb-1 text-slate-700 dark:text-gray-300">Opening Hours</p>
                        <Combobox
                            v-model="setting.opening"
                            :items="timeItems"
                            required
                            placeholder="Opening time"
                            class="w-full"
                            :error="errors.opening"
                            @update:modelValue="clearError('opening')"
                        />
                    </div>

                    <div>
                        <p class="text-sm mb-1 text-slate-700 dark:text-gray-300">Closing Hours</p>
                        <Combobox
                            v-model="setting.closing"
                            :items="timeItems"
                            required
                            placeholder="Closing time"
                            class="w-full"
                            :error="errors.closing"
                            @update:modelValue="clearError('closing')"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-6">
            <button
                type="button"
                @click="save"
                :disabled="saving"
                class="px-5 py-2 rounded-lg bg-primary text-white text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ saving ? "Saving..." : "Save Settings" }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { useSchemaValidation } from "~/composables/useSchemaValidation";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { Currency } from "~/utils/currency";
import { generate24HourTimes, getTimeZone } from "~/utils/time";
import { useBranchStore } from "~/stores/branch";
import { settingSchema, type OperationSetting } from "~/schema/branch-schema";
import { branchSettingService } from "~/api/branch-setting/BranchSettingService";
import { useRoute } from "vue-router";
import { useToast } from "~/composables/useToast";

const { success, error } = useToast();
const route = useRoute();
const branchStore = useBranchStore();
const activeBranch = computed(() => branchStore.activeBranch);
const emit = defineEmits<{
    (e: "save", setting: OperationSetting): void;
}>();

const setting = reactive<OperationSetting>({
    reserved_walkin_slots:
        activeBranch.value?.settings?.reserved_walkin_slots ?? 3,
    enable_booking_pre_admission:
        activeBranch.value?.settings?.enable_booking_pre_admission ?? true,
    enable_booking_complete_admission:
        activeBranch.value?.settings?.enable_booking_complete_admission ?? true,
    minimum_adl_hours: activeBranch.value?.settings?.minimum_adl_hours ?? 8,
    termination_fee_percent:
        activeBranch.value?.settings?.termination_fee_percent ?? 0,
    // billing_due_date: activeBranch.value?.settings?.billing_due_date ?? 31,
    is_open: activeBranch.value?.settings?.is_open ?? false,
    time_zone: activeBranch.value?.settings?.time_zone ?? "",
    opening: activeBranch.value?.settings?.opening ?? "",
    closing: activeBranch.value?.settings?.closing ?? "",
    currency: activeBranch.value?.settings?.currency ?? "",
});

const saving = ref(false);

const { errors, validate, clearError } = useSchemaValidation(
    computed(() => settingSchema),
    setting,
);

const timeZones = ref(getTimeZone());
const currencies = ref(Currency());

const times = generate24HourTimes();
const timeItems = computed(() =>
    times.map((t) => ({
        label: t,
        value: t,
    })),
);

async function save() {
    if (!validate()) {
        return;
    }

    saving.value = true;
    try {
        const res = await branchSettingService.update(
            route.params.uuid as string,
            {
                branch_uuid: route.params.uuid,
                ...setting,
            },
        );

        await branchStore.refreshBranch();

        success(res.message ?? res);
    } catch (err: any) {
        error(err.message);
        console.log(err);
    } finally {
        saving.value = false;
    }
}
</script>
