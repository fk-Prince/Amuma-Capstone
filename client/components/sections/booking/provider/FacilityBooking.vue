<template>
    <section
        class="rounded-2xl bg-white p-6 md:p-8 dark:bg-secondary"
        :class="{ 'animate-pulse': loading }"
    >
        <div
            class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between gap-5"
        >
            <div class="flex items-baseline gap-3 mb-8">
                <template v-if="loading">
                    <div class="h-6 w-6 rounded bg-slate-200 shrink-0 dark:bg-white/15" />
                    <div class="flex-1 space-y-2">
                        <div class="h-5 w-40 rounded bg-slate-200 dark:bg-white/15" />
                        <div class="h-3 w-64 rounded bg-slate-200 dark:bg-white/15" />
                    </div>
                </template>
                <template v-else>
                    <span class="text-2xl text-primary">01</span>
                    <div>
                        <h2 class="text-xl text-primary">Booking Request</h2>
                        <p class="text-[13px] text-muted dark:text-gray-400">
                            Select your admission type and schedule.
                        </p>
                    </div>
                </template>
            </div>

            <div v-if="!loading" class="shrink-0">
                <span
                    v-if="maxAvailableSlots > 0"
                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                    ></span>
                    {{ maxAvailableSlots }} slot{{
                        maxAvailableSlots === 1 ? "" : "s"
                    }} available
                </span>
                <span
                    v-else
                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500 dark:bg-white/10 dark:text-gray-400"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    No slots available
                </span>
            </div>
        </div>

        <div class="space-y-6">
            <div>
                <div v-if="loading" class="grid md:grid-cols-2 gap-3">
                    <div
                        v-for="i in 2"
                        :key="i"
                        class="rounded-xl border p-4 animate-pulse"
                    >
                        <div class="flex items-center justify-between">
                            <div class="space-y-2">
                                <div
                                    class="h-4 w-28 bg-slate-200 rounded dark:bg-white/15"
                                ></div>
                                <div
                                    class="h-3 w-40 bg-slate-100 rounded dark:bg-secondary"
                                ></div>
                            </div>
                            <div class="h-9 w-9 rounded-lg bg-slate-200 dark:bg-white/15"></div>
                        </div>
                    </div>
                </div>

                <template v-else>
                    <h3 class="font-semibold text-sm text-slate-900 mb-3 dark:text-white">
                        Admission Type <span class="text-danger">*</span>
                    </h3>

                    <div class="grid gap-4 md:grid-cols-2">
                        <button
                            type="button"
                            @click="update('type', 'Pre-Admission')"
                            class="group relative overflow-hidden rounded-2xl border p-5 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            :class="
                                model.type === 'Pre-Admission'
                                    ? 'border-primary-300 bg-primary-50/60 shadow-sm ring-2 ring-primary-100 dark:bg-primary-500/10 dark:ring-primary-500/20'
                                    : 'border-slate-200 bg-white hover:border-primary-200 hover:bg-primary-50/30 hover:shadow-sm dark:bg-secondary dark:border-white/10 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/5'
                            "
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-colors"
                                    :class="
                                        model.type === 'Pre-Admission'
                                            ? 'bg-primary-500 text-white shadow-sm'
                                            : 'bg-primary-50 text-primary-600 ring-1 ring-primary-100 group-hover:bg-primary-100 dark:bg-primary-500/10 dark:ring-primary-500/20 dark:group-hover:bg-primary-500/20 dark:text-primary-300'
                                    "
                                >
                                    <ClipboardCheck class="h-5 w-5" />
                                </div>

                                <div
                                    v-if="model.type === 'Pre-Admission'"
                                    class="rounded-full bg-primary-500 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white"
                                >
                                    Selected
                                </div>
                            </div>

                            <div class="mt-4">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                    Pre-Admission
                                </h3>

                                <p
                                    class="mt-1 text-xs leading-5 text-slate-500 dark:text-gray-400"
                                >
                                    Submit your requirements and patient
                                    information before admission is finalized.
                                </p>
                            </div>

                            <div
                                class="mt-5 flex items-center gap-2 border-t pt-4"
                                :class="
                                    model.type === 'Pre-Admission'
                                        ? 'border-primary-100 dark:border-primary-500/20'
                                        : 'border-slate-100 dark:border-white/10'
                                "
                            >
                                <span
                                    class="flex h-5 w-5 items-center justify-center rounded-full"
                                    :class="
                                        model.type === 'Pre-Admission'
                                            ? 'bg-primary-100 text-primary-600 dark:bg-primary-500/15 dark:text-primary-400'
                                            : 'bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-gray-500'
                                    "
                                >
                                    <Check class="h-3 w-3" />
                                </span>

                                <span
                                    class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                                >
                                    Complete the patient information now and
                                    continue the admission process when you
                                    arrive at the facility.
                                </span>
                            </div>
                        </button>

                        <button
                            type="button"
                            @click="update('type', 'Complete')"
                            class="group relative overflow-hidden rounded-2xl border p-5 text-left transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            :class="
                                model.type === 'Complete'
                                    ? 'border-primary-300 bg-primary-50/60 shadow-sm ring-2 ring-primary-100 dark:bg-primary-500/10 dark:ring-primary-500/20'
                                    : 'border-slate-200 bg-white hover:border-primary-200 hover:bg-primary-50/30 hover:shadow-sm dark:bg-secondary dark:border-white/10 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/5'
                            "
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-colors"
                                    :class="
                                        model.type === 'Complete'
                                            ? 'bg-primary-500 text-white shadow-sm'
                                            : 'bg-primary-50 text-primary-600 ring-1 ring-primary-100 group-hover:bg-primary-100 dark:bg-primary-500/10 dark:ring-primary-500/20 dark:group-hover:bg-primary-500/20 dark:text-primary-300'
                                    "
                                >
                                    <Building2 class="h-5 w-5" />
                                </div>

                                <div
                                    v-if="model.type === 'Complete'"
                                    class="rounded-full bg-primary-500 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white"
                                >
                                    Selected
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="flex items-center gap-2">
                                    <h3
                                        class="text-sm font-bold text-slate-900 dark:text-white"
                                    >
                                        Complete Admission
                                    </h3>
                                </div>

                                <p
                                    class="mt-1 text-xs leading-5 text-slate-500 dark:text-gray-400"
                                >
                                    Complete the admission process in one step,
                                    including accommodation and payment.
                                </p>
                            </div>

                            <div
                                class="mt-5 flex items-center gap-2 border-t pt-4"
                                :class="
                                    model.type === 'Complete'
                                        ? 'border-primary-100 dark:border-primary-500/20'
                                        : 'border-slate-100 dark:border-white/10'
                                "
                            >
                                <span
                                    class="flex h-5 w-5 items-center justify-center rounded-full"
                                    :class="
                                        model.type === 'Complete'
                                            ? 'bg-primary-100 text-primary-600 dark:bg-primary-500/15 dark:text-primary-400'
                                            : 'bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-gray-500'
                                    "
                                >
                                    <Check class="h-3 w-3" />
                                </span>

                                <span
                                    class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                                >
                                    Choose your accommodation, billing cycle,
                                    and admission details to complete your
                                    booking in one step.
                                </span>
                            </div>
                        </button>
                    </div>
                    <p v-if="errors?.type" class="text-xs text-red-500 mt-2">
                        {{ errors.type }}
                    </p>
                </template>
            </div>

            <!-- Accommodation + Plan (only for Complete) -->
            <!-- <div v-if="loading || model.type === 'Complete'" class="space-y-6">
                <div>
                    <div v-if="loading" class="grid md:grid-cols-2 gap-3">
                        <div
                            v-for="i in 2"
                            :key="i"
                            class="rounded-xl border p-4 animate-pulse"
                        >
                            <div class="flex items-center justify-between">
                                <div class="space-y-2">
                                    <div
                                        class="h-4 w-28 bg-slate-200 rounded dark:bg-white/15"
                                    ></div>
                                    <div
                                        class="h-3 w-40 bg-slate-100 rounded dark:bg-secondary"
                                    ></div>
                                </div>
                                <div
                                    class="h-9 w-9 rounded-lg bg-slate-200 dark:bg-white/15"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <template v-else>
                        <h3 class="font-semibold text-sm text-slate-900 mb-3 dark:text-white">
                            Accommodation Type
                            <span class="text-danger">*</span>
                        </h3>

                        <div class="grid md:grid-cols-2 gap-3">
                            <button
                                v-for="room in roomTypes"
                                :key="room.value"
                                type="button"
                                :disabled="room.slots === 0"
                                @click="update('plan', room.value)"
                                class="text-left rounded-xl border p-4 transition hover:border-primary hover:bg-primary/5 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:border-inherit disabled:hover:bg-transparent disabled:hover:shadow-none"
                                :class="
                                    model.plan === room.value
                                        ? 'border-primary ring-1 ring-primary/30 bg-primary/5'
                                        : ''
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-sm">
                                            {{ room.title }}
                                        </h3>
                                        <p
                                            class="text-xs text-slate-500 mt-0.5 dark:text-gray-400"
                                        >
                                            {{ room.description }}
                                        </p>
                                    </div>

                                    <img
                                        v-if="room.image"
                                        :src="room.image"
                                        :alt="room.title"
                                        class="h-16 w-16 shrink-0 rounded-lg object-cover"
                                    />
                                    <div
                                        v-else
                                        class="h-9 w-9 shrink-0 rounded-lg bg-primary/10 flex items-center justify-center text-primary"
                                    >
                                        <component
                                            :is="room.icon"
                                            class="h-7 w-7"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="mt-3 flex items-end justify-between"
                                >
                                    <span
                                        class="text-xs"
                                        :class="
                                            room.slots === 0
                                                ? 'text-rose-500 font-medium dark:text-rose-300'
                                                : 'text-slate-500 dark:text-gray-400'
                                        "
                                    >
                                        {{
                                            room.slots > 0
                                                ? `${room.slots} slot${room.slots === 1 ? "" : "s"} available`
                                                : "Fully booked"
                                        }}
                                    </span>

                                    <p
                                        v-if="model.plan === room.value"
                                        class="text-[11px] font-medium text-primary"
                                    >
                                        Currently selected
                                    </p>
                                </div>
                            </button>
                        </div>
                        <p
                            v-if="errors?.plan"
                            class="text-xs text-red-500 mt-2"
                        >
                            {{ errors.plan }}
                        </p>
                    </template>
                </div>

                <div>
                    <div v-if="loading" class="grid md:grid-cols-2 gap-3">
                        <div
                            v-for="i in 2"
                            :key="i"
                            class="rounded-xl border p-4 animate-pulse"
                        >
                            <div class="flex items-center justify-between">
                                <div class="space-y-2">
                                    <div
                                        class="h-4 w-28 bg-slate-200 rounded dark:bg-white/15"
                                    ></div>
                                    <div
                                        class="h-3 w-40 bg-slate-100 rounded dark:bg-secondary"
                                    ></div>
                                </div>
                                <div
                                    class="h-9 w-9 rounded-lg bg-slate-200 dark:bg-white/15"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <template v-else>
                        <h3 class="font-semibold text-sm text-slate-900 mb-3 dark:text-white">
                            Admission Plan <span class="text-danger">*</span>
                        </h3>

                        <div class="grid md:grid-cols-2 gap-3">
                            <button
                                v-for="plan in availablePlans"
                                :key="plan.value"
                                type="button"
                                @click="update('billing_cycle', plan.value)"
                                class="text-left rounded-xl border p-4 transition hover:border-primary hover:bg-primary/5 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                :class="
                                    model.billing_cycle === plan.value
                                        ? 'border-primary ring-1 ring-primary/30 bg-primary/5'
                                        : ''
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold text-sm">
                                            {{ plan.title }}
                                        </h3>
                                        <p
                                            class="text-xs text-slate-500 mt-0.5 dark:text-gray-400"
                                        >
                                            {{ plan.description }}
                                        </p>
                                    </div>

                                    <div
                                        class="h-9 w-9 shrink-0 rounded-lg bg-primary/10 flex items-center justify-center text-primary"
                                    >
                                        <component
                                            :is="plan.icon"
                                            class="h-5 w-5"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="mt-3 flex items-end justify-between"
                                >
                                    <span
                                        class="text-sm font-semibold text-primary"
                                    >
                                        ₱{{ getPrice(plan.value) }}
                                    </span>

                                    <p
                                        v-if="
                                            model.billing_cycle === plan.value
                                        "
                                        class="text-[11px] font-medium text-primary"
                                    >
                                        Currently selected
                                    </p>
                                </div>
                            </button>
                        </div>

                        <p
                            v-if="!availablePlans.length"
                            class="text-xs text-amber-600 mt-2 dark:text-amber-300"
                        >
                            No billing plans are configured for this room type
                            yet.
                        </p>

                        <p
                            v-if="errors?.billing_cycle"
                            class="text-xs text-red-500 mt-2"
                        >
                            {{ errors.billing_cycle }}
                        </p>
                    </template>
                </div>
            </div> -->
            <div v-if="loading || model.type === 'Complete'" class="space-y-6">
                <div>
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            Billing Cycle
                        </h3>

                        <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                            Choose how often you would like to be billed.
                        </p>
                    </div>

                    <div
                        class="relative inline-flex items-center rounded-full border border-primary-200 bg-muted-light/40 p-1 dark:border-white/10 dark:bg-white/5"
                    >
                        <span
                            class="absolute bottom-1 left-1 top-1 w-[calc(50%-4px)] rounded-full bg-primary shadow-sm transition-all duration-300 ease-in-out"
                            :class="
                                model.billing_cycle === 'Yearly'
                                    ? 'translate-x-[calc(100%+2px)]'
                                    : 'translate-x-0'
                            "
                        />

                        <button
                            type="button"
                            class="relative z-10 min-w-[110px] rounded-full px-5 py-2 text-sm font-semibold transition-colors duration-300"
                            :class="
                                model.billing_cycle === 'Monthly'
                                    ? 'text-white'
                                    : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                            "
                            @click="update('billing_cycle', 'Monthly')"
                        >
                            Monthly
                        </button>

                        <button
                            type="button"
                            class="relative z-10 min-w-[110px] rounded-full px-5 py-2 text-sm font-semibold transition-colors duration-300"
                            :class="
                                model.billing_cycle === 'Yearly'
                                    ? 'text-white'
                                    : 'text-muted hover:text-secondary dark:text-gray-400 dark:hover:text-white'
                            "
                            @click="update('billing_cycle', 'Yearly')"
                        >
                            Yearly
                        </button>
                    </div>

                    <p class="mt-2 text-xs text-muted dark:text-gray-400">
                        {{
                            model.billing_cycle === "Yearly"
                                ? "Billed annually — save more compared to monthly billing."
                                : "Billed monthly. Switch to yearly to save more."
                        }}
                    </p>
                </div>
                <!-- Accommodation -->
                <div v-if="model.billing_cycle">
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            Accommodation Type
                            <span class="text-danger">*</span>
                        </h3>

                        <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                            Select the accommodation that best suits your needs.
                        </p>
                    </div>

                    <div v-if="loading" class="grid gap-3 md:grid-cols-2">
                        <div
                            v-for="i in 2"
                            :key="i"
                            class="h-40 animate-pulse rounded-2xl border border-slate-200 bg-slate-50 dark:bg-secondary dark:border-white/10"
                        />
                    </div>

                    <div v-else class="grid gap-4 md:grid-cols-2">
                        <button
                            v-for="room in roomTypes"
                            :key="room.value"
                            type="button"
                            :disabled="room.slots === 0"
                            @click="update('plan', room.value)"
                            class="group relative overflow-hidden rounded-2xl border p-5 text-left transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="
                                model.plan === room.value
                                    ? 'border-primary-300 bg-primary-50/60 shadow-sm ring-2 ring-primary-100 dark:bg-primary-500/10 dark:ring-primary-500/20'
                                    : 'border-slate-200 bg-white hover:border-primary-200 hover:bg-primary-50/30 hover:shadow-sm dark:bg-secondary dark:border-white/10 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/5'
                            "
                        >
                            <div class="flex items-start justify-between gap-4">
                                <img
                                    v-if="room.image"
                                    :src="room.image"
                                    :alt="room.title"
                                    class="h-24 w-1/4 shrink-0 rounded-lg object-cover"
                                />

                                <div
                                    v-else
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                >
                                    <component
                                        :is="room.icon"
                                        class="h-7 w-7"
                                    />
                                </div>

                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="
                                            model.billing_cycle !== 'Monthly' &&
                                            getAnnualDiscount(
                                                facilityList,
                                                room.value,
                                            ) > 0
                                        "
                                        class="rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400"
                                    >
                                        Save
                                        {{
                                            getAnnualDiscount(
                                                facilityList,
                                                room.value,
                                            )
                                        }}%
                                    </span>

                                    <div
                                        v-if="model.plan === room.value"
                                        class="rounded-full bg-primary-500 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white"
                                    >
                                        Selected
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ room.title }}
                                </h4>

                                <p
                                    class="mt-1 text-xs leading-5 text-slate-500 dark:text-gray-400"
                                >
                                    {{ getRoomDescription(room.value) }}
                                </p>
                            </div>

                            <div
                                class="mt-5 flex items-end justify-between border-t border-slate-100 pt-4 dark:border-white/10"
                            >
                                <div>
                                    <p
                                        class="text-[10px] font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                    >
                                        {{
                                            model.billing_cycle.toLowerCase() ===
                                            "yearly"
                                                ? "Yearly"
                                                : "Monthly"
                                        }}
                                    </p>

                                    <p
                                        class="mt-0.5 text-lg font-bold text-primary-600 dark:text-primary-300"
                                    >
                                        ₱{{
                                            formatAmount(
                                                getFacilityPrice(
                                                    facilityList,
                                                    model.billing_cycle as
                                                        | "Monthly"
                                                        | "Yearly",
                                                    room.value,
                                                ),
                                            )
                                        }}
                                    </p>
                                </div>

                                <span
                                    class="text-[11px] font-medium"
                                    :class="
                                        room.slots === 0
                                            ? 'text-rose-500 dark:text-rose-300'
                                            : 'text-slate-400 dark:text-gray-500'
                                    "
                                >
                                    {{
                                        room.slots > 0
                                            ? `${room.slots} slot${room.slots === 1 ? "" : "s"} available`
                                            : "Fully booked"
                                    }}
                                </span>
                            </div>
                        </button>
                    </div>

                    <p v-if="errors?.plan" class="mt-2 text-xs text-red-500">
                        {{ errors.plan }}
                    </p>
                </div>

                <div v-else>
                    <div
                        class="flex items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/70 px-4 py-4 dark:border-white/10 dark:bg-white/5"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-500 dark:bg-primary-500/10 dark:text-primary-300"
                        >
                            <CalendarRange class="h-4 w-4" />
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-700 dark:text-gray-300">
                                Select a billing interval first
                            </p>

                            <p
                                class="mt-0.5 text-[11px] leading-4 text-slate-500 dark:text-gray-400"
                            >
                                Choose Monthly or Yearly to view the available
                                accommodation options and pricing.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="model.type === 'Complete' && !loading" class="max-w-xs">
                <DatePickerField
                    label="Admission Date"
                    :model-value="model.admission_date"
                    @update:model-value="update('admission_date', $event)"
                    :min="todayStr"
                    :max="maxDateStr"
                    placeholder="Select admission date"
                    required
                />
                <p
                    v-if="errors?.admission_date"
                    class="mt-1.5 text-xs text-red-500"
                >
                    {{ errors.admission_date }}
                </p>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, watch } from "vue";
import type { Component } from "vue";
import { getLocalDateStr } from "~/utils/time";
import { getFacilityPrice, getAnnualDiscount } from "~/utils/calculation";
import { formatAmount } from "~/utils/currency";
import type { FacilityBooking } from "~/types/booking";
import type { BranchImage, BranchRetrieve } from "~/types/branch";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import {
    Star,
    CalendarDays,
    CalendarRange,
    Users,
    ClipboardCheck,
    Building2,
    Check,
} from "lucide-vue-next";

interface RoomTypeStat {
    slots: number;
    description: string | null;
}

function getRoomImage(roomValue: "VIP" | "Common"): string | null {
    const typeMap: Record<"VIP" | "Common", BranchImage["type"]> = {
        VIP: "vip_room",
        Common: "common_room",
    };

    const match = (props.branch?.images ?? []).find(
        (img) => img.type === typeMap[roomValue],
    );

    return match?.image_url ?? null;
}

const props = defineProps<{
    model: FacilityBooking;
    errors?: Record<string, string> | null;
    branch?: BranchRetrieve | null;
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: "update:model", value: FacilityBooking): void;
    (e: "update:errors", value: Record<string, string>): void;
}>();

function update<K extends keyof FacilityBooking>(
    key: K,
    value: FacilityBooking[K],
) {
    emit("update:model", {
        ...props.model,
        [key]: value,
    });

    clearError(key as string);
}

function clearError(field: string) {
    if (!props.errors) return;

    const updated = { ...props.errors };
    delete updated[field];

    emit("update:errors", updated);
}

const facilityList = computed(() => props.branch?.facility ?? []);

const maxAvailableSlots = computed(() => {
    const slots = facilityList.value
        .map((room) => Number(room.available_slot))
        .filter((n) => !isNaN(n));

    return slots.length ? Math.max(...slots) : 0;
});

const slotsByRoomType = computed(() => {
    const map: Record<string, RoomTypeStat> = {};

    for (const room of facilityList.value) {
        const type = (room.accommodation_type || "").toUpperCase();
        const slot = Number(room.available_slot);
        if (isNaN(slot)) continue;

        const existing = map[type];
        const isHigher = !existing || slot > existing.slots;

        map[type] = {
            slots: isHigher ? slot : existing.slots,
            description: room.description ?? existing?.description ?? null,
        };
    }

    return map;
});

const roomTypeDefs: {
    value: "VIP" | "Common";
    title: string;
    description: string;
    icon: Component;
}[] = [
    {
        value: "Common",
        title: "Common Room",
        description: "Shared ward",
        icon: Users,
    },
    {
        value: "VIP",
        title: "VIP Room",
        description: "Private premium room",
        icon: Star,
    },
];

const roomTypes = computed(() =>
    roomTypeDefs
        .filter((room) => slotsByRoomType.value[room.value.toUpperCase()])
        .map((room) => {
            const data = slotsByRoomType.value[room.value.toUpperCase()];

            return {
                ...room,
                description: data?.description || room.description,
                slots: data?.slots ?? 0,
                image: getRoomImage(room.value),
            };
        }),
);

const admissionPlans: {
    value: "Monthly" | "Yearly";
    title: string;
    description: string;
    icon: Component;
}[] = [
    {
        value: "Monthly",
        title: "Monthly",
        description: "Monthly billing cycle",
        icon: CalendarDays,
    },
    {
        value: "Yearly",
        title: "Yearly",
        description: "Annual billing cycle",
        icon: CalendarRange,
    },
];

const availablePlans = computed(() => {
    const room = props.model.plan || "Common";

    return admissionPlans.filter((plan) =>
        facilityList.value.some(
            (item) =>
                (item.accommodation_type || "").toUpperCase() ===
                    room.toUpperCase() &&
                (item.billing_cycle || "").toUpperCase() ===
                    plan.value.toUpperCase(),
        ),
    );
});

function getPrice(plan: "Monthly" | "Yearly") {
    const room = props.model.plan || "Common";

    const facility = facilityList.value.find(
        (item) =>
            (item.accommodation_type || "").toUpperCase() ===
                room.toUpperCase() &&
            (item.billing_cycle || "").toUpperCase() === plan.toUpperCase(),
    );

    const price = Number(facility?.price ?? 0);
    return isNaN(price) ? "0" : formatAmount(price);
}

watch(
    () => props.model.type,
    (type) => {
        if (type === "Pre-Admission") {
            emit("update:model", {
                ...props.model,
                plan: "",
                billing_cycle: "",
            });
        }

        if (props.errors) {
            emit("update:errors", {});
        }
    },
);

const todayStr = getLocalDateStr(new Date());
const maxDateStr = getLocalDateStr(new Date(Date.now() + 7 * 86400000));
function getRoomDescription(room: "Common" | "VIP") {
    const facility = facilityList.value.find(
        (item) =>
            (item.accommodation_type || "").toUpperCase() ===
                room.toUpperCase() &&
            (item.billing_cycle || "").toUpperCase() ===
                (props.model.billing_cycle || "").toUpperCase(),
    );

    return facility?.description || "";
}
</script>
