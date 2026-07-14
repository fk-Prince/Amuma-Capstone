<template>
    <div class="w-full">
        <div class="grid grid-cols-3 gap-3">
            <div
                class="rounded-2xl border border-blue-200 bg-white p-4 shadow-sm transition-all duration-300 ease-in-out hover:shadow-lg hover:-translate-y-1 hover:border-blue-400"
            >
                <div
                    class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center"
                >
                    <svg
                        class="w-4 h-4 text-blue-600"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                    </svg>
                </div>

                <p
                    class="mt-2 text-[10px] uppercase tracking-wide text-gray-400 font-medium"
                >
                    Total Employees
                </p>

                <div class="flex items-end justify-between">
                    <p class="text-xl font-bold text-gray-800">
                        {{ props.totalEmployee ?? 0 }}
                    </p>

                    <span class="text-[11px] font-medium text-orange-500">
                        160 max
                    </span>
                </div>

                <div class="mt-2">
                    <div class="h-1.5 rounded-full bg-gray-100 overflow-hidden">
                        <div
                            class="h-full rounded-full bg-blue-500"
                            :style="{
                                width: `${(props.totalEmployee / 160) * 100}%`,
                            }"
                        />
                    </div>

                    <p class="mt-1 text-[11px] text-orange-500">
                        {{ 160 - (props.totalEmployee ?? 0) }} positions
                        available
                    </p>
                </div>
            </div>

            <div
                class="rounded-2xl border border-emerald-200 bg-white p-4 shadow-sm transition-all duration-300 ease-in-out hover:shadow-lg hover:-translate-y-1 hover:border-emerald-400"
            >
                <div
                    class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center"
                >
                    <svg
                        class="w-4 h-4 text-emerald-600"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                </div>

                <p
                    class="mt-2 text-[10px] uppercase tracking-wide text-gray-400 font-medium"
                >
                    On Duty
                </p>

                <p class="text-xl font-bold text-gray-800">
                    {{ props.onDuty ?? 0 }}
                </p>

                <p class="mt-1 text-[11px] text-emerald-600">
                    Active staff today
                </p>
            </div>

            <div
                class="rounded-2xl border border-rose-200 bg-white p-4 shadow-sm transition-all duration-300 ease-in-out hover:shadow-lg hover:-translate-y-1 hover:border-rose-400"
            >
                <div
                    class="w-9 h-9 rounded-lg bg-rose-50 flex items-center justify-center"
                >
                    <svg
                        class="w-4 h-4 text-rose-600"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 8v4l3 3" />
                    </svg>
                </div>

                <p
                    class="mt-2 text-[10px] uppercase tracking-wide text-gray-400 font-medium"
                >
                    On Leave
                </p>

                <p class="text-xl font-bold text-gray-800">
                    {{ props.onLeave ?? 0 }}
                </p>

                <p class="mt-1 text-[11px] text-rose-500">
                    Current leave requests
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
useHead({ title: "Staff" });

const props = defineProps<{
    totalEmployee: number;
    onDuty: number;
    onLeave: number;
}>();

const emit = defineEmits<{
    addEmployee: [];
}>();
</script>
