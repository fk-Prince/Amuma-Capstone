<template>
    <div class="max-w-5xl mx-auto flex flex-col gap-5">
        <div class="grid grid-cols-2 gap-4">
            <Combobox
                v-model="selectedCurrency"
                :items="currencies"
                placeholder="Select currency"
                class="w-full"
                label="Currency"
            />
            <Combobox
                v-model="selectedTimeZone"
                :items="timeZones"
                placeholder="Select time-zone"
                class="w-full"
                label="Time-Zone"
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
                        v-model="selectedOpening"
                        :items="timeItems"
                        required
                        placeholder="Opening time"
                        class="w-full"
                    />
                </div>

                <div>
                    <p class="text-sm mb-1 text-slate-700">Closing Hours</p>
                    <Combobox
                        v-model="selectedClosing"
                        :items="timeItems"
                        required
                        placeholder="Closing time"
                        class="w-full"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import Combobox from "../ui/Combobox.vue";
import { Currency } from "~/utils/currency";
import { generate24HourTimes, getTimeZone } from "~/utils/time";
import { useSubscriptionCheckout } from "~/stores/subscription";

const checkout = useSubscriptionCheckout();

const emit = defineEmits(["update:setting"]);

const times = generate24HourTimes();
const currencyList = Currency();
const timeZoneList = getTimeZone();
const setting = checkout.settings;

const selectedCurrency = ref(setting?.currency);
const selectedOpening = ref(setting?.opening);
const selectedClosing = ref(setting?.closing);
const selectedTimeZone = ref(setting?.time_zone);

const currencies = ref(currencyList);
const timeZones = ref(timeZoneList);

const timeItems = computed(() =>
    times.map((t) => ({
        label: t,
        value: t,
    })),
);

watch([selectedCurrency, selectedOpening, selectedClosing], () => {
    emit("update:setting", {
        ...setting,
        currency: selectedCurrency.value,
        selectedOpening: selectedOpening.value,
        selectedClosing: selectedClosing.value,
        timeZone: selectedTimeZone.value,
    });
});
</script>
