<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-150"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 p-5"
            >
                <div
                    class="flex w-full max-w-3xl max-h-[88dvh] flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                >
                    <!-- HEADER -->
                    <div
                        class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold tracking-tight">
                                Change Room / Accommodation
                            </h3>

                            <p
                                class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                            >
                                Moving to a different accommodation re-bills the
                                remaining days at the new rate. The
                                {{
                                    currentCycle
                                        ? currentCycle.toLowerCase()
                                        : ""
                                }}
                                billing cycle stays the same — use Extend Stay
                                to change it.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                            aria-label="Close"
                            @click="$emit('close')"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- BODY -->
                    <div class="flex-1 overflow-y-auto px-6 py-5">
                        <div
                            class="rounded-xl border border-slate-200 p-3 flex flex-wrap items-center justify-between gap-3 dark:border-white/10"
                        >
                            <div>
                                <p
                                    class="text-xs text-slate-500 dark:text-gray-400"
                                >
                                    Current Room & Bed
                                </p>
                                <p class="font-semibold">
                                    Room {{ admission?.room?.room_no ?? "—" }}
                                    <span
                                        v-if="admission?.bed?.bed_no"
                                        class="text-slate-400 font-normal dark:text-gray-500"
                                    >
                                        · Bed {{ admission.bed.bed_no }}
                                    </span>
                                    <span
                                        v-if="currentType"
                                        class="text-slate-400 font-normal dark:text-gray-500"
                                    >
                                        · {{ currentType }}
                                        {{ currentCycle }}
                                    </span>
                                </p>
                            </div>

                            <div
                                v-if="remainingDays !== null"
                                class="text-right"
                            >
                                <p
                                    class="text-xs text-slate-500 dark:text-gray-400"
                                >
                                    Days left in period
                                </p>
                                <p class="font-semibold">{{ remainingDays }}</p>
                            </div>
                        </div>

                        <div
                            v-if="loading"
                            class="mt-6 grid sm:grid-cols-2 gap-4"
                        >
                            <div
                                v-for="i in 4"
                                :key="i"
                                class="animate-pulse rounded-xl border p-4 space-y-3 dark:border-white/10"
                            >
                                <div
                                    class="h-4 w-28 rounded bg-slate-200 dark:bg-white/15"
                                />
                                <div
                                    class="h-3 w-40 rounded bg-slate-100 dark:bg-white/10"
                                />
                            </div>
                        </div>

                        <div
                            v-else-if="!contractGroups.length"
                            class="mt-6 rounded-xl border border-dashed py-10 text-center text-sm text-slate-500 dark:text-gray-400 dark:border-white/10"
                        >
                            No rooms are configured for this branch.
                        </div>

                        <template v-else>
                            <section class="mt-6">
                                <h4
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    1 · Accommodation
                                </h4>

                                <div class="mt-3 grid sm:grid-cols-2 gap-3">
                                    <button
                                        v-for="group in contractGroups"
                                        :key="group.contract_id"
                                        type="button"
                                        class="rounded-xl border p-4 text-left transition dark:border-white/10"
                                        :class="
                                            selectedGroup?.contract_id ===
                                            group.contract_id
                                                ? 'border-primary ring-1 ring-primary/30 bg-primary/5'
                                                : 'hover:border-primary/50'
                                        "
                                        @click="selectGroup(group)"
                                    >
                                        <div
                                            class="flex items-start justify-between gap-3"
                                        >
                                            <div class="min-w-0">
                                                <p class="font-semibold">
                                                    {{
                                                        group.accommodation_type
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500 dark:text-gray-400"
                                                >
                                                    {{ group.billing_cycle }} ·
                                                    {{
                                                        group.rooms.length
                                                    }}
                                                    rooms ·
                                                    {{
                                                        group.available_beds_count
                                                    }}
                                                    {{
                                                        group.available_beds_count ===
                                                        1
                                                            ? "bed"
                                                            : "beds"
                                                    }}
                                                    available
                                                </p>
                                            </div>

                                            <p
                                                class="shrink-0 text-sm font-semibold"
                                            >
                                                {{
                                                    formatCurrency(group.price)
                                                }}
                                            </p>
                                        </div>

                                        <span
                                            v-if="
                                                group.contract_id ===
                                                currentContractId
                                            "
                                            class="mt-3 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-white/10 dark:text-gray-400"
                                        >
                                            Current plan
                                        </span>

                                        <span
                                            v-else
                                            class="mt-3 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                            :class="
                                                Number(group.price) >
                                                currentPrice
                                                    ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'
                                                    : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                                            "
                                        >
                                            {{
                                                Number(group.price) >
                                                currentPrice
                                                    ? "Upgrade"
                                                    : "Downgrade"
                                            }}
                                        </span>
                                    </button>
                                </div>
                            </section>

                            <section v-if="selectedGroup" class="mt-6">
                                <h4
                                    class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    2 · Room & Bed
                                    <span
                                        class="font-normal normal-case tracking-normal"
                                    >
                                        in
                                        {{ selectedGroup.accommodation_type }}
                                    </span>
                                </h4>

                                <div
                                    v-if="!selectedGroup.rooms.length"
                                    class="mt-3 rounded-xl border border-dashed py-10 text-center text-sm text-slate-500 dark:text-gray-400 dark:border-white/10"
                                >
                                    No rooms available for this accommodation.
                                </div>

                                <div
                                    v-else
                                    class="mt-3 grid sm:grid-cols-2 gap-3"
                                >
                                    <button
                                        v-for="room in selectedGroup.rooms"
                                        :key="room.room_id"
                                        type="button"
                                        :disabled="
                                            availableBeds(room).length === 0 &&
                                            !isCurrentRoom(room)
                                        "
                                        class="rounded-xl border p-4 text-left transition hover:border-primary/60 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10"
                                        :class="
                                            selectedRoom?.room_id ===
                                            room.room_id
                                                ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                                                : ''
                                        "
                                        @click="openBeds(room)"
                                    >
                                        <div
                                            class="flex items-start justify-between gap-2"
                                        >
                                            <div class="min-w-0">
                                                <p class="font-semibold">
                                                    Room {{ room.room_no }}
                                                    <span
                                                        v-if="
                                                            isCurrentRoom(room)
                                                        "
                                                        class="text-xs font-normal text-slate-400 dark:text-gray-500"
                                                    >
                                                        (current)
                                                    </span>
                                                </p>
                                                <p
                                                    class="text-xs text-slate-500 dark:text-gray-400"
                                                >
                                                    {{ room.floor }} Floor ·
                                                    {{
                                                        availableBeds(room)
                                                            .length
                                                    }}
                                                    {{
                                                        availableBeds(room)
                                                            .length === 1
                                                            ? "bed"
                                                            : "beds"
                                                    }}
                                                    available
                                                </p>
                                            </div>

                                            <span
                                                v-if="
                                                    selectedRoom?.room_id ===
                                                        room.room_id &&
                                                    selectedBed
                                                "
                                                class="h-fit shrink-0 rounded-full bg-primary px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-white"
                                            >
                                                Bed {{ selectedBed.bed_no }}
                                            </span>

                                            <span
                                                v-else-if="
                                                    availableBeds(room)
                                                        .length === 0
                                                "
                                                class="h-fit shrink-0 rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-rose-700 dark:bg-rose-500/10 dark:text-rose-300"
                                            >
                                                Full
                                            </span>

                                            <span
                                                v-else
                                                class="h-fit shrink-0 text-xs font-medium text-primary"
                                            >
                                                Select bed →
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            </section>
                        </template>

                        <div
                            v-if="selectedRoom && selectedBed"
                            class="mt-5 rounded-xl bg-primary/5 border border-primary/20 p-4 flex items-center justify-between"
                        >
                            <div>
                                <p
                                    class="text-xs text-slate-500 dark:text-gray-400"
                                >
                                    New Room & Bed
                                </p>
                                <p class="font-semibold">
                                    Room {{ selectedRoom.room_no }}
                                    <span
                                        class="text-slate-400 font-normal dark:text-gray-500"
                                    >
                                        · Bed {{ selectedBed.bed_no }}
                                    </span>
                                    <span
                                        v-if="selectedContract"
                                        class="text-slate-400 font-normal dark:text-gray-500"
                                    >
                                        ·
                                        {{
                                            selectedContract.accommodation_type
                                        }}
                                        {{ selectedContract.billing_cycle }}
                                    </span>
                                </p>
                            </div>
                            <span
                                class="text-[11px] font-medium px-2 py-0.5 rounded-full"
                                :class="
                                    isAccommodationChange
                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'
                                        : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                "
                            >
                                {{
                                    isAccommodationChange
                                        ? "Accommodation change"
                                        : "Room change only"
                                }}
                            </span>
                        </div>

                        <div
                            v-if="isAccommodationChange && periodExhausted"
                            class="mt-4 rounded-xl border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-500/20 dark:bg-rose-500/10"
                        >
                            <p
                                class="text-sm font-semibold text-rose-900 dark:text-rose-300"
                            >
                                This billing period has already ended
                            </p>
                            <p
                                class="mt-1 text-xs leading-5 text-rose-800/80 dark:text-rose-300/70"
                            >
                                There are no days left to move to
                                {{ selectedContract?.accommodation_type }}, so
                                there is nothing to re-bill. Extend the stay
                                first, then change the accommodation. A room
                                change within {{ currentType }} is still
                                allowed.
                            </p>
                        </div>

                        <div
                            v-if="proration"
                            class="mt-4 rounded-xl border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-500/20 dark:bg-amber-500/10"
                        >
                            <div class="flex items-center gap-1.5">
                                <p
                                    class="text-sm font-semibold text-amber-900 dark:text-amber-300"
                                >
                                    {{ proration.remainingDays }} days remaining
                                </p>

                                <span
                                    class="group relative inline-flex"
                                    tabindex="0"
                                    role="button"
                                    aria-label="How this was worked out"
                                >
                                    <svg
                                        class="h-3.5 w-3.5 cursor-help text-amber-700/60 transition hover:text-amber-900 dark:text-amber-300/60 dark:hover:text-amber-200"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 16v-4" />
                                        <path d="M12 8h.01" />
                                    </svg>

                                    <span
                                        class="pointer-events-none invisible absolute left-0 top-full z-20 mt-2 w-80 rounded-lg border border-slate-200 bg-white p-3 text-left opacity-0 shadow-lg transition duration-150 group-hover:visible group-hover:opacity-100 group-focus:visible group-focus:opacity-100 dark:border-white/10 dark:bg-secondary"
                                    >
                                        <span
                                            class="mb-2 block text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                        >
                                            How this was worked out
                                        </span>

                                        <span
                                            class="block space-y-1 text-[11px] leading-5"
                                        >
                                            <span
                                                class="flex justify-between gap-3"
                                            >
                                                <span
                                                    class="text-slate-500 dark:text-gray-400"
                                                >
                                                    Period
                                                </span>

                                                <span
                                                    class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                >
                                                    {{ proration.totalDays }}
                                                    days
                                                </span>
                                            </span>

                                            <span
                                                class="flex justify-between gap-3"
                                            >
                                                <span
                                                    class="text-slate-500 dark:text-gray-400"
                                                >
                                                    Stayed so far
                                                </span>

                                                <span
                                                    class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                >
                                                    {{ proration.consumedDays }}
                                                    days
                                                </span>
                                            </span>

                                            <span
                                                class="flex justify-between gap-3 border-t border-slate-100 pt-1 dark:border-white/10"
                                            >
                                                <span
                                                    class="text-slate-500 dark:text-gray-400"
                                                >
                                                    Unused
                                                </span>

                                                <span
                                                    class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                >
                                                    {{
                                                        proration.remainingDays
                                                    }}
                                                    days
                                                </span>
                                            </span>

                                            <span
                                                class="mt-1 flex justify-between gap-3 border-t border-slate-100 pt-1 dark:border-white/10"
                                            >
                                                <span
                                                    class="text-slate-500 dark:text-gray-400"
                                                >
                                                    {{ currentType }}
                                                    {{
                                                        formatCurrency(
                                                            proration.oldPeriodPrice,
                                                        )
                                                    }}
                                                    ÷
                                                    {{ proration.totalDays }}
                                                    =
                                                    {{
                                                        formatCurrency(
                                                            proration.oldDailyRate,
                                                        )
                                                    }}/day
                                                </span>

                                                <span
                                                    class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            proration.oldRemaining,
                                                        )
                                                    }}
                                                </span>
                                            </span>

                                            <span
                                                class="flex justify-between gap-3"
                                            >
                                                <span
                                                    class="text-slate-500 dark:text-gray-400"
                                                >
                                                    {{
                                                        selectedContract?.accommodation_type
                                                    }}
                                                    {{
                                                        formatCurrency(
                                                            proration.newContractPrice,
                                                        )
                                                    }}
                                                    ÷ cycle =
                                                    {{
                                                        formatCurrency(
                                                            proration.newDailyRate,
                                                        )
                                                    }}/day
                                                </span>

                                                <span
                                                    class="shrink-0 font-medium text-slate-700 dark:text-gray-300"
                                                >
                                                    {{
                                                        formatCurrency(
                                                            proration.newRemaining,
                                                        )
                                                    }}
                                                </span>
                                            </span>

                                            <span
                                                class="mt-1 flex justify-between gap-3 border-t border-slate-100 pt-1 dark:border-white/10"
                                            >
                                                <span
                                                    class="text-slate-500 dark:text-gray-400"
                                                >
                                                    Each × {{
                                                        proration.remainingDays
                                                    }}
                                                    unused days
                                                </span>

                                                <span
                                                    class="shrink-0 font-semibold text-slate-700 dark:text-gray-300"
                                                >
                                                    {{
                                                        proration.difference >= 0
                                                            ? "+"
                                                            : "−"
                                                    }}
                                                    {{
                                                        formatCurrency(
                                                            Math.abs(
                                                                proration.difference,
                                                            ),
                                                        )
                                                    }}
                                                </span>
                                            </span>
                                        </span>
                                    </span>
                                </span>
                            </div>

                            <div class="mt-3 space-y-1.5 text-sm">
                                <div class="flex justify-between gap-4">
                                    <span
                                        class="text-slate-600 dark:text-gray-400"
                                    >
                                        {{ currentType }}
                                        <span
                                            class="text-slate-400 dark:text-gray-500"
                                        >
                                            now
                                        </span>
                                    </span>
                                    <span class="font-medium">
                                        {{
                                            formatCurrency(
                                                proration.oldRemaining,
                                            )
                                        }}
                                    </span>
                                </div>

                                <div class="flex justify-between gap-4">
                                    <span
                                        class="text-slate-600 dark:text-gray-400"
                                    >
                                        {{
                                            selectedContract?.accommodation_type
                                        }}
                                        <span
                                            class="text-slate-400 dark:text-gray-500"
                                        >
                                            new
                                        </span>
                                    </span>
                                    <span class="font-medium">
                                        {{
                                            formatCurrency(
                                                proration.newRemaining,
                                            )
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex justify-between gap-4 border-t border-amber-200 pt-1.5 dark:border-amber-500/20"
                                >
                                    <span
                                        class="font-semibold text-slate-700 dark:text-gray-300"
                                    >
                                        {{
                                            proration.difference >= 0
                                                ? "To pay"
                                                : "To credit"
                                        }}
                                    </span>
                                    <span
                                        class="font-bold"
                                        :class="
                                            proration.difference >= 0
                                                ? 'text-amber-900 dark:text-amber-300'
                                                : 'text-emerald-700 dark:text-emerald-300'
                                        "
                                    >
                                        {{
                                            proration.difference >= 0
                                                ? "+"
                                                : "−"
                                        }}
                                        {{
                                            formatCurrency(
                                                Math.abs(proration.difference),
                                            )
                                        }}
                                    </span>
                                </div>

                                <div
                                    v-if="proration.futureCount"
                                    class="flex justify-between gap-4 border-t border-amber-200 pt-1.5 dark:border-amber-500/20"
                                >
                                    <span
                                        class="text-slate-600 dark:text-gray-400"
                                    >
                                        {{ proration.futureCount }} prepaid
                                        {{
                                            proration.futureCount === 1
                                                ? "period"
                                                : "periods"
                                        }}
                                        →
                                        {{
                                            selectedContract?.accommodation_type
                                        }}
                                    </span>
                                    <span
                                        class="font-medium"
                                        :class="
                                            proration.futureDelta < 0
                                                ? 'text-emerald-700 dark:text-emerald-300'
                                                : ''
                                        "
                                    >
                                        {{
                                            proration.futureDelta >= 0
                                                ? "+"
                                                : "−"
                                        }}
                                        {{
                                            formatCurrency(
                                                Math.abs(proration.futureDelta),
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>

                            <p
                                class="mt-3 text-xs text-amber-800/80 dark:text-amber-300/70"
                            >
                                One adjustment on
                                {{
                                    currentInvoiceCode ?? "the current invoice"
                                }}. Payments already made stay put.
                            </p>
                        </div>

                        <div v-if="selectedRoom && selectedBed" class="mt-4">
                            <label
                                class="block text-xs font-medium text-slate-500 mb-1.5 dark:text-gray-400"
                            >
                                Reason for change
                                <span
                                    class="text-slate-400 font-normal dark:text-gray-500"
                                    >(optional)</span
                                >
                            </label>
                            <BaseInput
                                v-model="reason"
                                :rows="2"
                                maxlength="255"
                                mode="textarea"
                                placeholder="e.g. Patient requested a quieter room, upgraded at family request..."
                                class="w-full p-3 text-sm resize-none"
                            />
                        </div>
                    </div>

                    <!-- FOOTER -->
                    <div
                        class="border-t border-slate-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="flex-1 rounded-xl border py-2.5 text-sm font-medium transition hover:bg-slate-50 dark:border-white/10 dark:hover:bg-white/5"
                                @click="$emit('close')"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                class="flex flex-[2] items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-medium text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!canConfirm"
                                @click="confirmSelection"
                            >
                                <svg
                                    v-if="submitting"
                                    class="h-4 w-4 animate-spin"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    />
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                    />
                                </svg>
                                {{ confirmLabel }}
                            </button>
                        </div>
                    </div>
                </div>

                <transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="showBeds"
                        class="fixed inset-0 z-[80] bg-black/40 flex items-center justify-center p-5"
                        @click.self="showBeds = false"
                    >
                        <div
                            class="flex w-full max-w-lg max-h-[80dvh] flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                        >
                            <div
                                class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                            >
                                <div class="min-w-0">
                                    <h3
                                        class="text-lg font-semibold tracking-tight"
                                    >
                                        Select Bed
                                    </h3>
                                    <p
                                        class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                                    >
                                        Room {{ modalRoom?.room_no }}
                                        <span v-if="selectedGroup">
                                            ·
                                            {{
                                                selectedGroup.accommodation_type
                                            }}
                                            {{ selectedGroup.billing_cycle }}
                                        </span>
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                                    aria-label="Close"
                                    @click="showBeds = false"
                                >
                                    ✕
                                </button>
                            </div>

                            <div class="flex-1 overflow-y-auto px-6 py-5">
                                <div
                                    v-if="bedsForModal.length"
                                    class="grid grid-cols-2 gap-3"
                                >
                                    <button
                                        v-for="bed in bedsForModal"
                                        :key="bed.bed_id"
                                        type="button"
                                        :disabled="
                                            bed.status.toLowerCase() !==
                                                'available' &&
                                            !isCurrentBed(bed)
                                        "
                                        class="rounded-xl border p-4 text-left transition dark:border-white/10"
                                        :class="
                                            bed.status.toLowerCase() ===
                                                'available' || isCurrentBed(bed)
                                                ? 'hover:border-primary hover:bg-primary/5'
                                                : 'cursor-not-allowed opacity-50'
                                        "
                                        @click="chooseBed(bed)"
                                    >
                                        <p class="font-semibold">
                                            Bed {{ bed.bed_no }}
                                        </p>

                                        <span
                                            class="mt-1.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                            :class="
                                                isCurrentBed(bed)
                                                    ? 'bg-slate-200 text-slate-600 dark:bg-white/15 dark:text-gray-400'
                                                    : bed.status.toLowerCase() ===
                                                        'available'
                                                      ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                                      : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
                                            "
                                        >
                                            {{
                                                isCurrentBed(bed)
                                                    ? "Current"
                                                    : bed.status
                                            }}
                                        </span>
                                    </button>
                                </div>

                                <div
                                    v-else
                                    class="rounded-xl border border-dashed py-10 text-center dark:border-white/10"
                                >
                                    <p
                                        class="font-medium text-slate-600 dark:text-gray-400"
                                    >
                                        No beds in this room
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                                    >
                                        This room currently has no beds set up.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { useRoute } from "vue-router";
import BaseInput from "~/components/ui/BaseInput.vue";

import { admissionService } from "~/api/admission/AdmissionService";
import { formatCurrency } from "~/utils/currency";
import type { RoomContract } from "~/types/contract";
import type { Admission } from "~/types/patient";
import type { Room } from "~/types/room";
import type { Bed } from "~/types/bed";

const route = useRoute();

const props = defineProps<{
    open: boolean;
    admission: Admission | null;
}>();

const emit = defineEmits<{
    (
        e: "select",
        payload: {
            room: Room;
            bed: Bed;
            reason: string;
            contractId: number;
            isAccommodationChange: boolean;
        },
    ): void;
    (e: "close"): void;
}>();

const contracts = ref<RoomContract[]>([]);
const loading = ref(false);
const submitting = ref(false);

const selectedGroup = ref<RoomContract | null>(null);
const selectedRoom = ref<Room | null>(null);
const selectedBed = ref<Bed | null>(null);
const modalRoom = ref<Room | null>(null);
const showBeds = ref(false);
const reason = ref("");

const selectedContract = computed(() => selectedGroup.value);

const currentPeriod = computed(() => props.admission?.current_period ?? null);

const currentContractId = computed(() =>
    props.admission?.current_contract?.branch_contract_id
        ? Number(props.admission.current_contract.branch_contract_id)
        : null,
);

const currentType = computed(
    () => props.admission?.current_contract?.accommodation_type ?? "",
);

const currentCycle = computed(
    () => props.admission?.current_contract?.billing_cycle ?? "",
);

const currentPrice = computed(() =>
    Number(props.admission?.current_contract?.price ?? 0),
);

const contractGroups = computed<RoomContract[]>(() =>
    contracts.value
        // A room change keeps the billing cycle — switching cycle is what
        // Extend Stay is for, and the server rejects it here.
        .filter(
            (c) =>
                !currentCycle.value ||
                normalize(c.billing_cycle) === normalize(currentCycle.value),
        )
        .filter((c) => (c.rooms ?? []).length > 0)
        .map((c) => ({
            ...c,
            rooms: (c.rooms ?? []).filter(
                (room) =>
                    normalize(room.room_type) ===
                    normalize(c.accommodation_type),
            ),
        }))
        .filter((c) => c.rooms.length > 0)
        .sort((a, b) => {
            if (a.contract_id === currentContractId.value) return -1;
            if (b.contract_id === currentContractId.value) return 1;
            return a.price - b.price;
        }),
);

function normalize(value?: string | null) {
    return (value ?? "").trim().toUpperCase();
}

function isCurrentRoom(room: Room) {
    return room.room_id === props.admission?.room?.room_id;
}

function isCurrentBed(bed: Bed) {
    return bed.bed_id === props.admission?.bed?.bed_id;
}

function availableBeds(room: Room) {
    if (!room.beds?.length) return [];
    return room.beds.filter(
        (b) => b.status?.toLowerCase() === "available" || isCurrentBed(b),
    );
}

// The contract feed only carries free beds, so the one this patient is lying
// in is missing from its own room. Put it back, marked as theirs, otherwise
// the room they are already in looks like it has a bed fewer than it does.
const bedsForModal = computed<Bed[]>(() => {
    const beds = [...(modalRoom.value?.beds ?? [])];
    const currentBed = props.admission?.bed;

    if (
        modalRoom.value &&
        currentBed?.bed_id &&
        isCurrentRoom(modalRoom.value) &&
        !beds.some((bed) => bed.bed_id === currentBed.bed_id)
    ) {
        beds.push(currentBed as Bed);
    }

    return beds.sort((a, b) =>
        String(a.bed_no).localeCompare(String(b.bed_no), undefined, {
            numeric: true,
        }),
    );
});

function cycleDays(billingCycle?: string | null) {
    return normalize(billingCycle) === "YEARLY" ? 365 : 30;
}

// Accommodation is billed by the day, and the admission day itself is already
// day one. Counts whole calendar days so the figure does not shift with the
// time of day. Mirrors periodConsumption() on the server.
const consumption = computed(() => {
    const startedAt = currentPeriod.value?.started_at;

    if (!startedAt) return null;

    const startDay = new Date(startedAt);

    if (Number.isNaN(startDay.getTime())) return null;

    startDay.setHours(0, 0, 0, 0);

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // The period's own window, not the contract cycle — a period opened by an
    // earlier change covers only the remainder of the cycle.
    const endedAt = currentPeriod.value?.ended_at;
    const endDay = endedAt ? new Date(endedAt) : null;

    const totalDays =
        endDay && !Number.isNaN(endDay.getTime())
            ? Math.max(
                  1,
                  Math.round(
                      (endDay.setHours(0, 0, 0, 0) - startDay.getTime()) /
                          86_400_000,
                  ),
              )
            : cycleDays(currentCycle.value);

    const elapsed = Math.max(
        0,
        Math.round((today.getTime() - startDay.getTime()) / 86_400_000),
    );

    // The day of the change is billed to the accommodation being left, so today
    // counts as consumed. A period that has not started yet consumes nothing.
    const consumedDays =
        today.getTime() >= startDay.getTime()
            ? Math.min(totalDays, elapsed + 1)
            : 0;

    const remainingDays = totalDays - consumedDays;

    return {
        totalDays,
        consumedDays,
        remainingDays,
        remainingRatio: remainingDays / totalDays,
    };
});

const periodExhausted = computed(
    () => !!consumption.value && consumption.value.remainingDays <= 0,
);

const remainingDays = computed(() => consumption.value?.remainingDays ?? null);

const isAccommodationChange = computed(
    () =>
        !!selectedContract.value &&
        selectedContract.value.contract_id !== currentContractId.value,
);

const currentInvoiceCode = computed(
    () => props.admission?.current_invoice?.invoice_code ?? null,
);

const proration = computed(() => {
    if (!isAccommodationChange.value || !consumption.value) {
        return null;
    }

    const { remainingDays, totalDays, consumedDays } = consumption.value;

    const round2 = (n: number) => Math.round(n * 100) / 100;

    const oldDailyRate =
        Number(currentPeriod.value?.charged_amount ?? 0) / totalDays;

    const newDailyRate =
        Number(selectedContract.value?.price ?? 0) /
        cycleDays(selectedContract.value?.billing_cycle);

    const oldRemaining = round2(oldDailyRate * remainingDays);
    const newRemaining = round2(newDailyRate * remainingDays);
    const difference = round2(newRemaining - oldRemaining);
    const futureCount = Number(props.admission?.future_periods?.count ?? 0);
    const futureCharged = Number(
        props.admission?.future_periods?.charged_amount ?? 0,
    );

    const futureDelta = round2(
        futureCount * Number(selectedContract.value?.price ?? 0) -
            futureCharged,
    );

    return {
        remainingDays,
        totalDays,
        consumedDays,
        oldDailyRate: round2(oldDailyRate),
        newDailyRate: round2(newDailyRate),
        oldPeriodPrice: round2(Number(currentPeriod.value?.charged_amount ?? 0)),
        newContractPrice: round2(Number(selectedContract.value?.price ?? 0)),
        oldRemaining,
        newRemaining,
        difference,
        futureCount,
        futureDelta,
    };
});

const confirmLabel = computed(() => {
    if (submitting.value) return "Moving...";
    return isAccommodationChange.value
        ? "Confirm Accommodation Change"
        : "Confirm Move";
});

function selectGroup(group: RoomContract) {
    if (selectedGroup.value?.contract_id === group.contract_id) return;

    selectedGroup.value = group;
    selectedRoom.value = null;
    selectedBed.value = null;
}

function openBeds(room: Room) {
    modalRoom.value = room;
    showBeds.value = true;
}

function chooseBed(bed: Bed) {
    if (bed.status.toLowerCase() !== "available" && !isCurrentBed(bed)) return;
    if (!modalRoom.value) return;
    selectedRoom.value = modalRoom.value;
    selectedBed.value = bed;
    showBeds.value = false;
}

const canConfirm = computed(
    () =>
        !submitting.value &&
        !!selectedRoom.value &&
        !!selectedBed.value &&
        !!selectedContract.value &&
        !(isAccommodationChange.value && periodExhausted.value),
);

watch(
    [() => props.open, () => props.admission],
    async ([open, admission]) => {
        if (!open || !admission) return;

        selectedGroup.value = null;
        selectedRoom.value = null;
        selectedBed.value = null;
        reason.value = "";

        loading.value = true;

        try {
            const res = await admissionService.action({
                branch_uuid: route.params.uuid,
                action: "branch_contract",
            });

            contracts.value = res.data?.data ?? res.data ?? res ?? [];

            selectedGroup.value =
                contractGroups.value.find(
                    (g) => g.contract_id === currentContractId.value,
                ) ??
                contractGroups.value[0] ??
                null;
        } catch (err) {
            console.error("Failed loading contracts", err);
            contracts.value = [];
        } finally {
            loading.value = false;
        }
    },
    { immediate: true },
);

function confirmSelection() {
    if (
        !canConfirm.value ||
        !selectedRoom.value ||
        !selectedBed.value ||
        !selectedContract.value
    ) {
        return;
    }

    submitting.value = true;

    emit("select", {
        room: selectedRoom.value,
        bed: selectedBed.value,
        reason: reason.value.trim(),
        contractId: selectedContract.value.contract_id,
        isAccommodationChange: isAccommodationChange.value,
    });
}

watch(
    () => props.open,
    (value) => {
        if (!value) submitting.value = false;
    },
);
</script>
