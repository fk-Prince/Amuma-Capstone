<template>
    <div
        class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 transition-all duration-200 ease-out hover:-translate-y-1 hover:border-primary-200 hover:bg-white hover:shadow-md transform-gpu"
    >
        <div class="flex items-start gap-3">
            <img
                :src="branch.image"
                :alt="branch.name"
                class="h-14 w-14 rounded-xl object-cover shrink-0"
            />

            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p
                            class="text-sm font-semibold text-slate-900 truncate"
                        >
                            {{ branch.name }}
                        </p>
                    </div>

                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <span
                            class="inline-flex items-center gap-1 text-[11px] font-medium rounded-full px-2.5 py-1"
                            :class="
                                branch.is_verified
                                    ? 'bg-primary-50 text-primary'
                                    : 'bg-amber-50 text-amber-600'
                            "
                        >
                            <svg
                                v-if="branch.is_verified"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                class="w-3 h-3 shrink-0"
                            >
                                <path
                                    d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                class="w-3 h-3 shrink-0"
                            >
                                <circle cx="12" cy="12" r="9" />
                                <path
                                    d="M12 7v5l3 2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            {{
                                branch.is_verified
                                    ? "Verified"
                                    : "Pending review"
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 space-y-1.5 text-xs text-slate-500">
            <p class="flex items-center gap-1.5">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-3.5 h-3.5 shrink-0"
                >
                    <path
                        d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z"
                    />
                    <circle cx="12" cy="10" r="2.5" />
                </svg>
                {{ branch.address }}
            </p>
            <p class="flex items-center gap-1.5">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-3.5 h-3.5 shrink-0"
                >
                    <path
                        d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.9.6 2.7a2 2 0 0 1-.5 2.1L8 9.7a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.7 2.1Z"
                    />
                </svg>
                {{ branch.phone }}
            </p>
            <p class="flex items-center gap-1.5">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-3.5 h-3.5 shrink-0"
                >
                    <rect x="2" y="4" width="20" height="16" rx="2" />
                    <path d="m22 6-10 7L2 6" />
                </svg>
                {{ branch.email }}
            </p>
        </div>

        <div
            class="mt-4 grid grid-cols-3 divide-x divide-slate-200 border-t border-slate-200 pt-3"
        >
            <div class="text-center">
                <p class="text-sm font-semibold text-slate-900">
                    {{ branch.rooms }}
                </p>
                <p class="text-[11px] text-slate-400">Rooms</p>
            </div>
            <div class="text-center">
                <p class="text-sm font-semibold text-slate-900">
                    {{ branch.staffs }}
                </p>
                <p class="text-[11px] text-slate-400">Staffs</p>
            </div>
            <div class="text-center">
                <p class="text-sm font-semibold text-slate-900">
                    {{ branch.patients }}
                </p>
                <p class="text-[11px] text-slate-400">Patients</p>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <button
                type="button"
                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white py-2 text-xs font-medium text-slate-600 hover:border-primary hover:text-primary transition"
                @click="emit('edit', branch)"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-3.5 h-3.5"
                >
                    <path d="M12 20h9" />
                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                </svg>
                Edit
            </button>

            <button
                type="button"
                class="h-8 w-8 shrink-0 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-slate-400 hover:text-slate-600"
                @click="emit('menu', branch)"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-4 h-4"
                >
                    <circle cx="12" cy="5" r="1.2" />
                    <circle cx="12" cy="12" r="1.2" />
                    <circle cx="12" cy="19" r="1.2" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
interface BranchCardData {
    branch_id: number;
    uuid: string;
    name: string;
    address: string;
    phone: string;
    email: string;
    is_verified: boolean;
    rooms: number;
    staffs: number;
    patients: number;
    image: string;
}

defineProps<{
    branch: BranchCardData;
}>();

const emit = defineEmits<{
    edit: [branch: BranchCardData];
    menu: [branch: BranchCardData];
}>();
</script>
