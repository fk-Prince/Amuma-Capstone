<template>
    <div class="w-full mx-auto flex flex-col gap-5">
        <div class="grid grid-cols-2 gap-4">
            <Combobox
                v-model="setting.currency"
                :items="currencies"
                placeholder="Select currency"
                class="w-full"
                label="Currency"
                :error="errors?.currency"
                @update:modelValue="clearError('currency')"
            />

            <Combobox
                v-model="setting.time_zone"
                :items="timeZones"
                placeholder="Select time-zone"
                class="w-full"
                label="Time-Zone"
                :error="errors?.time_zone"
                @update:modelValue="clearError('time_zone')"
            />
        </div>

        <div class="flex flex-col">
            <label class="text-sm font-semibold mb-1 text-slate-700">
                Business Hours
            </label>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm mb-1 text-slate-700">Opening Hours</p>
                    <Combobox
                        v-model="setting.opening"
                        :items="timeItems"
                        required
                        placeholder="Opening time"
                        class="w-full"
                        :error="errors?.opening"
                        @update:modelValue="clearError('opening')"
                    />
                </div>

                <div>
                    <p class="text-sm mb-1 text-slate-700">Closing Hours</p>
                    <Combobox
                        v-model="setting.closing"
                        :items="timeItems"
                        required
                        placeholder="Closing time"
                        class="w-full"
                        :error="errors?.closing"
                        @update:modelValue="clearError('closing')"
                    />
                </div>
            </div>
        </div>
        <div
            class="flex items-center gap-2 rounded-lg bg-primary/5 border border-primary/10 px-4 py-2.5 text-[13px] text-primary mb-8"
        >
            <Settings class="h-3.5 w-3.5 shrink-0" />
            <span>
                Some fields below are managed through your branch settings. You
                can update them anytime in Branch Settings.
            </span>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Settings } from "lucide-vue-next";
import { ref, computed } from "vue";
import Combobox from "../ui/Combobox.vue";
import { Currency } from "~/utils/currency";
import { generate24HourTimes, getTimeZone } from "~/utils/time";

const props = defineProps<{
    setting: any;
    errors?: Record<string, string> | null;
}>();

const emit = defineEmits<{
    (e: "update:setting", value: any): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

const setting = computed({
    get: () => props.setting,
    set: (value) => emit("update:setting", value),
});

const errors = computed(() => props.errors);

const times = generate24HourTimes();
const currencies = ref(Currency());
const timeZones = ref(getTimeZone());

const timeItems = computed(() =>
    times.map((t) => ({
        label: t,
        value: t,
    })),
);

function clearError(field: string) {
    if (!props.errors) return;

    const updated = { ...props.errors };
    delete updated[field];

    emit("update:errors", updated);
}
</script>
