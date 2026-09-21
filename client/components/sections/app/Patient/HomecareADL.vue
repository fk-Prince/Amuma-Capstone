<template>
    <div class="rounded-2xl bg-white font-sans dark:bg-secondary">
        <div
            v-if="variant !== 3"
            class="flex flex-col gap-3 p-4 sm:p-5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h3
                    class="text-base font-semibold text-secondary dark:text-white"
                >
                    Homecare Activity of Daily Living (ADL) Schedule Audit Log
                </h3>

                <p class="mt-0.5 text-sm text-muted dark:text-gray-400">
                    QR check-in / check-out history for scheduled visits.
                </p>
            </div>

            <div class="flex w-full gap-2 sm:w-auto">
                <ActionButton
                    v-if="variant === 1"
                    variant="primary"
                    @click="showScanner = true"
                >
                    Scan QR
                </ActionButton>

                <div v-if="variant === 1" class="relative w-full sm:w-64">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search employee or schedule code..."
                        class="w-full rounded-lg border border-muted-light dark:border-white/10 py-2 pl-3 pr-3 text-sm text-secondary dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary"
                    />
                </div>
            </div>
        </div>

        <div
            v-if="loading"
            class="space-y-3"
            :class="variant === 3 ? '' : 'p-4 sm:p-5'"
        >
            <div
                v-for="i in 4"
                :key="i"
                class="h-32 animate-pulse rounded-2xl bg-muted-light dark:bg-white/10"
            />
        </div>

        <div
            v-else-if="!filteredLogs.length"
            class="rounded-2xl border border-dashed border-muted-light dark:border-white/10 p-12 text-center"
        >
            <span
                class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-muted-light dark:bg-white/10 text-muted dark:text-gray-400"
            >
                <CalendarClock class="h-6 w-6" />
            </span>

            <p class="text-sm font-semibold text-secondary dark:text-white">
                {{
                    variant === 3
                        ? "No schedule records found"
                        : "No audit records found"
                }}
            </p>

            <p
                v-if="variant === 3"
                class="mt-1 text-xs text-muted dark:text-gray-400"
            >
                Try a different status or month to see more visits.
            </p>
        </div>

        <div
            v-else
            class="space-y-6"
            :class="variant === 3 ? '' : 'p-4 sm:p-5'"
        >
            <div v-for="group in logGroups" :key="group.key" class="space-y-3">
                <div
                    v-if="group.title"
                    class="flex items-center justify-between gap-2"
                >
                    <div class="flex items-center gap-2">
                        <CalendarClock class="h-4 w-4 text-primary" />
                        <p
                            class="text-sm font-semibold text-secondary dark:text-white"
                        >
                            {{ group.title }}
                        </p>
                    </div>
                    <span class="text-xs text-muted dark:text-gray-400">
                        {{ group.logs.length }} upcoming
                    </span>
                </div>

                <div
                    v-for="log in group.logs"
                    :key="rowKey(log)"
                    class="overflow-hidden rounded-2xl border border-muted-light dark:border-white/10 bg-white transition hover:border-primary/30 dark:bg-secondary"
                >
                    <button
                        type="button"
                        class="flex w-full flex-col gap-3 p-4 sm:p-5 text-left lg:flex-row lg:items-center lg:justify-between"
                        :class="
                            isExpanded(log)
                                ? 'border-b border-muted-light dark:border-white/10'
                                : 'hover:bg-muted-light/40 dark:hover:bg-white/5 transition-colors'
                        "
                        @click="onRowClick(log)"
                    >
                        <div class="flex items-start gap-3 min-w-0">
                            <span
                                class="mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-muted dark:text-gray-400 transition-transform"
                                :class="isExpanded(log) ? 'rotate-180' : ''"
                            >
                                <ChevronDown class="h-4 w-4" />
                            </span>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <h4
                                        class="font-semibold text-secondary dark:text-white"
                                        :class="
                                            variant === 3 ? 'text-base' : 'text-xl'
                                        "
                                    >
                                        {{ log.schedule_code }}
                                    </h4>

                                    <span
                                        v-if="variant !== 3"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ring-1"
                                        :class="statusPill(log.status)"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-current opacity-70"
                                        />
                                        {{ log.status }}
                                    </span>
                                </div>

                                <div
                                    v-if="variant === 3"
                                    class="mt-0.5 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-muted dark:text-gray-400"
                                >
                                    <span class="whitespace-nowrap">
                                        {{ formatDateTime(log.scheduled_at) }}
                                    </span>

                                    <span
                                        v-if="log.address"
                                        class="flex min-w-0 items-center gap-1"
                                    >
                                        <span class="text-muted-light">·</span>
                                        <MapPinned class="h-3 w-3 shrink-0" />
                                        <span class="min-w-0 truncate">
                                            {{ log.address }}
                                        </span>
                                    </span>
                                </div>

                                <template v-else>
                                    <p
                                        class="text-[14px] text-muted dark:text-gray-400"
                                    >
                                        {{ formatDateTime(log.scheduled_at) }}
                                    </p>
                                    <p
                                        class="flex min-w-0 items-center gap-1.5 text-[13px] text-muted dark:text-gray-400"
                                    >
                                        <MapPinned
                                            class="w-3.5 h-3.5 text-muted dark:text-gray-400 shrink-0"
                                        />
                                        <span class="min-w-0 truncate">{{
                                            log.address
                                        }}</span>
                                    </p>
                                    <p
                                        v-if="log.note"
                                        class="mt-0.5 truncate text-[12px] text-muted dark:text-gray-400"
                                        :title="log.note"
                                    >
                                        Note: {{ log.note }}
                                    </p>
                                </template>

                                <span
                                    v-if="
                                        variant === 3 &&
                                        isCurrentlyCheckedIn(log)
                                    "
                                    class="mt-1.5 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"
                                    />
                                    On duty ·
                                    {{
                                        formatCheckInTime(
                                            latestCheckIn(log)?.in_timestamp,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>

                        <div
                            :class="
                                variant === 3
                                    ? 'flex w-full flex-col items-stretch gap-2.5 sm:w-60 sm:shrink-0'
                                    : 'flex w-full flex-col gap-2 lg:w-auto lg:shrink-0'
                            "
                            @click="$event.stopPropagation()"
                        >
                            <div
                                v-if="variant === 1 || variant === 2"
                                class="flex w-full flex-wrap items-center gap-2"
                            >
                                <ActionButton
                                    variant="outline"
                                    @click="viewDetails(log)"
                                >
                                    View details
                                </ActionButton>

                                <ActionButton
                                    v-if="
                                        !['cancelled', 'completed'].includes(
                                            log.status,
                                        )
                                    "
                                    @click="openAssignModal(log)"
                                    variant="primary"
                                >
                                    Assign
                                </ActionButton>
                            </div>

                            <ActionButton
                                v-if="
                                    variant === 3 &&
                                    canGenerateQr(log) &&
                                    isCurrentlyCheckedIn(log)
                                "
                                class="order-2"
                                extra-class="w-full !py-1.5 !text-xs"
                                :loading="generatingQr"
                                @click="generateQr('out', log)"
                                variant="primary"
                            >
                                Generate QR Out
                            </ActionButton>

                            <ActionButton
                                v-if="
                                    variant === 3 &&
                                    canGenerateQr(log) &&
                                    !isCurrentlyCheckedIn(log)
                                "
                                class="order-2"
                                extra-class="w-full !py-1.5 !text-xs"
                                :loading="generatingQr"
                                @click="generateQr('in', log)"
                                variant="primary"
                            >
                                Generate QR In
                            </ActionButton>

                            <template v-if="variant === 3">
                                <div
                                    class="order-1 flex w-full flex-col gap-2.5"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2 whitespace-nowrap"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold capitalize ring-1"
                                            :class="statusPill(log.status)"
                                        >
                                            <span
                                                class="h-2 w-2 rounded-full bg-current opacity-70"
                                            />
                                            {{ log.status }}
                                        </span>

                                        <span
                                            class="text-xs font-semibold text-muted dark:text-gray-400"
                                        >
                                            {{
                                                formatDurationShort(
                                                    log.total_hours,
                                                )
                                            }}
                                            booked
                                        </span>
                                    </div>

                                    <button
                                        type="button"
                                        :disabled="
                                            !canRequestReview(log) ||
                                            sendingReview === log.schedule_id
                                        "
                                        class="flex w-full items-center justify-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:border-muted-light disabled:bg-transparent disabled:text-muted dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-500/20 dark:disabled:border-white/10 dark:disabled:bg-transparent dark:disabled:text-gray-400"
                                        @click="requestScheduleReview(log)"
                                    >
                                        <Bell class="h-3.5 w-3.5 shrink-0" />
                                        <span>{{
                                            reviewButtonLabel(log)
                                        }}</span>
                                    </button>

                                    <div class="w-full">
                                        <div
                                            class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-white/10"
                                        >
                                            <div
                                                class="h-full rounded-full transition-[width] duration-500 ease-out"
                                                :class="progressFill(log)"
                                                :style="{
                                                    width: progressWidth(log),
                                                }"
                                            />
                                        </div>

                                        <div
                                            class="mt-2 flex items-center justify-between gap-2 whitespace-nowrap text-[11px] font-semibold"
                                        >
                                            <span
                                                :class="progressTextTone(log)"
                                            >
                                                {{ progressLabel(log) }}
                                            </span>

                                            <span
                                                class="text-muted dark:text-gray-400"
                                            >
                                                {{ remainingLabel(log) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div
                                v-else
                                class="flex w-full flex-wrap items-center justify-end gap-2"
                            >
                                <div
                                    class="rounded-xl border border-primary/20 bg-primary/5 px-4 py-2"
                                >
                                    <p
                                        class="text-[10px] uppercase text-primary/60"
                                    >
                                        Scheduled Duration
                                    </p>

                                    <p class="text-sm font-bold text-primary">
                                        {{
                                            formatDuration(log.total_hours) ||
                                            "0 hrs"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </button>

                    <Transition
                        name="row-collapse"
                        @enter="onEnter"
                        @after-enter="onAfterEnter"
                        @leave="onLeave"
                    >
                        <div v-show="isExpanded(log)" class="overflow-hidden">
                            <div class="px-4 sm:px-5">
                                <div
                                    class="mt-4 space-y-2 border-b border-muted-light dark:border-white/10 pb-4"
                                >
                                    <p
                                        v-if="log.assignees.length"
                                        class="text-xs font-semibold uppercase text-muted dark:text-gray-400"
                                    >
                                        Assigned Caregiver Staff
                                    </p>

                                    <template v-if="log.assignees.length">
                                        <div
                                            v-for="assignee in visibleAssignees(
                                                log,
                                            )"
                                            :key="assignee.employee_id"
                                            class="flex items-center justify-between gap-3"
                                        >
                                            <div
                                                class="flex items-center gap-3 min-w-0"
                                            >
                                                <img
                                                    v-if="assignee.avatar"
                                                    :src="assignee.avatar"
                                                    class="h-10 w-10 rounded-full object-cover"
                                                />
                                                <div
                                                    v-else
                                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-muted-light dark:bg-white/10 text-sm font-semibold text-muted dark:text-gray-400"
                                                >
                                                    {{
                                                        initials(
                                                            assignee.full_name,
                                                        )
                                                    }}
                                                </div>

                                                <div class="min-w-0">
                                                    <p
                                                        class="truncate text-sm font-semibold text-secondary dark:text-white"
                                                    >
                                                        {{ assignee.full_name }}
                                                    </p>

                                                    <p
                                                        v-if="
                                                            assignee.employee_role
                                                        "
                                                        class="text-xs text-muted dark:text-gray-400 capitalize"
                                                    >
                                                        {{
                                                            assignee.employee_role ??
                                                            "—"
                                                        }}
                                                    </p>

                                                    <p
                                                        v-if="
                                                            assignee.phone_number
                                                        "
                                                        class="flex items-center gap-1 text-[11px] text-muted dark:text-gray-400"
                                                    >
                                                        <Phone
                                                            class="h-3 w-3 shrink-0"
                                                        />
                                                        {{
                                                            assignee.phone_number
                                                        }}
                                                    </p>

                                                    <p
                                                        v-if="assignee.email"
                                                        class="flex min-w-0 items-center gap-1 text-[11px] text-muted dark:text-gray-400"
                                                    >
                                                        <Mail
                                                            class="h-3 w-3 shrink-0"
                                                        />
                                                        <span
                                                            class="truncate"
                                                            >{{
                                                                assignee.email
                                                            }}</span
                                                        >
                                                    </p>
                                                </div>
                                            </div>

                                            <div
                                                class="flex shrink-0 flex-col items-end gap-1"
                                            >
                                                <span
                                                    v-if="
                                                        isOnDuty(
                                                            log,
                                                            assignee.employee_id,
                                                        )
                                                    "
                                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                                    />
                                                    On Duty
                                                </span>

                                                <span
                                                    v-if="assignee.note"
                                                    class="rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-medium text-primary"
                                                    :title="shiftHours(assignee.note) ?? undefined"
                                                >
                                                    {{ assignee.note }}
                                                    <template v-if="shiftHours(assignee.note)">
                                                        · {{ shiftHours(assignee.note) }}
                                                    </template>
                                                </span>

                                                <span
                                                    v-if="
                                                        shiftEnded(log, assignee.note) &&
                                                        isOnDuty(log, assignee.employee_id)
                                                    "
                                                    class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
                                                >
                                                    <TriangleAlert class="h-3 w-3" />
                                                    Shift ended · still on duty
                                                </span>
                                            </div>
                                        </div>

                                        <button
                                            v-if="hasMoreAssignees(log)"
                                            type="button"
                                            class="w-full rounded-lg border border-muted-light py-1.5 text-xs font-semibold text-muted transition hover:bg-muted-light/40 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                                            @click="loadMoreAssignees(log)"
                                        >
                                            Load more
                                        </button>
                                    </template>

                                    <div v-else class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-sm font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
                                        >
                                            !
                                        </div>

                                        <div>
                                            <p
                                                class="text-sm font-semibold text-amber-700 dark:text-amber-300"
                                            >
                                                Service is unassigned
                                            </p>

                                            <p
                                                class="text-xs text-muted dark:text-gray-400"
                                            >
                                                No employee has been assigned
                                                yet
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="
                                        ![
                                            'completed',
                                            'cancelled',
                                            'missed',
                                        ].includes(log.status)
                                    "
                                    class="mt-4 flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-500/20 dark:bg-amber-500/10"
                                >
                                    <div>
                                        <p
                                            class="text-[11px] text-amber-600/70 dark:text-amber-300"
                                        >
                                            Remaining
                                        </p>

                                        <p
                                            class="text-sm font-bold text-amber-700 dark:text-amber-300"
                                        >
                                            {{ formatRemaining(log) }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="totalGapMinutes(log) > 0"
                                        class="flex items-center gap-2"
                                    >
                                        <div class="text-right">
                                            <p
                                                class="text-[11px] text-amber-600/70 dark:text-amber-300"
                                            >
                                                Total Late/Gap
                                            </p>

                                            <p
                                                class="text-sm font-bold text-amber-700 dark:text-amber-300"
                                            >
                                                {{ formatTotalGap(log) }}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-200 dark:bg-amber-500/20 dark:text-amber-300 dark:hover:bg-amber-500/30"
                                            @click="openDeductionModal(log)"
                                        >
                                            <Minus class="h-3.5 w-3.5" />
                                            Request deduction
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-5 space-y-3 pb-5">
                                    <p
                                        class="text-xs font-semibold uppercase text-muted dark:text-gray-400"
                                    >
                                        Attendance History
                                    </p>
                                    <div
                                        v-if="!log.online_logs.length"
                                        class="rounded-lg border border-muted-light dark:border-white/10 bg-muted-light/40 dark:bg-white/5 p-4 text-sm text-muted dark:text-gray-400"
                                    >
                                        No attendance history available
                                    </div>

                                    <div
                                        v-else
                                        v-for="entry in visibleAttendance(log)"
                                        :key="entry.index"
                                        class="rounded-lg border border-muted-light dark:border-white/10 bg-muted-light/40 dark:bg-white/5 p-4"
                                    >
                                        <div
                                            v-if="entry.scan.employee_name"
                                            class="mb-3 flex items-center gap-2"
                                        >
                                            <img
                                                v-if="
                                                    entry.scan.employee_avatar
                                                "
                                                :src="
                                                    entry.scan.employee_avatar
                                                "
                                                class="h-6 w-6 rounded-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-muted-light dark:bg-white/10 text-[10px] font-semibold text-muted dark:text-gray-400"
                                            >
                                                {{
                                                    initials(
                                                        entry.scan
                                                            .employee_name,
                                                    )
                                                }}
                                            </div>

                                            <p
                                                class="text-xs font-medium text-secondary dark:text-white"
                                            >
                                                {{ entry.scan.employee_name }}
                                            </p>
                                        </div>

                                        <div
                                            class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                                        >
                                            <div>
                                                <p
                                                    class="text-[11px] uppercase text-muted dark:text-gray-400"
                                                >
                                                    Check-in
                                                </p>

                                                <p
                                                    class="text-sm text-secondary dark:text-white"
                                                >
                                                    {{
                                                        entry.scan.in_timestamp
                                                            ? formatDateTime(
                                                                  entry.scan
                                                                      .in_timestamp,
                                                              )
                                                            : "Not checked in"
                                                    }}
                                                </p>

                                                <p
                                                    v-if="
                                                        entry.scan.in_timestamp
                                                    "
                                                    class="text-[11px] text-emerald-600 dark:text-emerald-300"
                                                >
                                                    QR scanned
                                                </p>
                                            </div>

                                            <div>
                                                <p
                                                    class="text-[11px] uppercase text-muted dark:text-gray-400"
                                                >
                                                    Check-out
                                                </p>

                                                <p
                                                    class="text-sm text-secondary dark:text-white"
                                                >
                                                    {{
                                                        entry.scan.out_timestamp
                                                            ? formatDateTime(
                                                                  entry.scan
                                                                      .out_timestamp,
                                                              )
                                                            : "Not checked out"
                                                    }}
                                                </p>

                                                <p
                                                    v-if="
                                                        entry.scan.out_timestamp
                                                    "
                                                    class="text-[11px] text-emerald-600 dark:text-emerald-300"
                                                >
                                                    QR scanned
                                                </p>
                                            </div>

                                            <!-- <div>
                                                <p
                                                    class="text-[11px] uppercase text-muted dark:text-gray-400"
                                                >
                                                    Status
                                                </p>

                                                <span
                                                    class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                                                    :class="
                                                        scan.out_timestamp
                                                            ? 'bg-primary/10 text-primary'
                                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'
                                                    "
                                                >
                                                    {{
                                                        scan.out_timestamp
                                                            ? "Completed"
                                                            : "Ongoing"
                                                    }}
                                                </span>
                                            </div> -->
                                        </div>

                                        <p
                                            v-if="
                                                attendanceGapLabel(
                                                    log,
                                                    entry.index,
                                                )
                                            "
                                            class="mt-3 text-[11px] font-medium text-amber-600 dark:text-amber-300"
                                        >
                                            {{
                                                attendanceGapLabel(
                                                    log,
                                                    entry.index,
                                                )
                                            }}
                                        </p>

                                        <div
                                            v-if="entry.scan.notes"
                                            class="mt-3 border-t border-muted-light dark:border-white/10 pt-3 text-xs text-muted dark:text-gray-400"
                                        >
                                            {{ entry.scan.notes }}
                                        </div>
                                    </div>

                                    <button
                                        v-if="hasMoreAttendance(log)"
                                        type="button"
                                        class="w-full rounded-lg border border-muted-light py-1.5 text-xs font-semibold text-muted transition hover:bg-muted-light/40 dark:border-white/10 dark:text-gray-400 dark:hover:bg-white/5"
                                        @click="loadMoreAttendance(log)"
                                    >
                                        Load more
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <QrCodeModal
            v-if="variant === 1 || variant === 3"
            :show="showQrModal"
            :token="qrToken"
            :mode="qrMode"
            :caregivers="qrCaregivers"
            :checked-in="qrCheckedIn"
            @close="closeQrModal"
            @scanned="handleQrScanned"
        />

        <InvoiceDeductionModal
            :open="showDeductionModal"
            :log="deductionSchedule"
            :gap-minutes="deductionSchedule ? totalGapMinutes(deductionSchedule) : 0"
            :is-saving="isSendingDeduction"
            @close="closeDeductionModal"
            @confirm="submitDeductionRequest"
        />

        <template v-if="variant === 1 || variant === 2">
            <AssignADLModal
                :open="showAssignModal"
                :schedule="selectedSchedule"
                :branch-uuid="String(route.params.uuid)"
                :is-saving="isAssigning"
                :conflicts="assignConflicts"
                @close="closeAssignModal"
                @confirm="handleAssignConfirm"
            />

            <QrScanner
                v-if="variant === 1 && showScanner"
                @close="showScanner = false"
            />
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import {
    TriangleAlert,
    ChevronDown,
    CalendarClock,
    MapPinned,
    Phone,
    Mail,
    Bell,
    Minus,
} from "lucide-vue-next";
import type { ScheduleItem, AuditRow } from "~/types/schedule";
import ActionButton from "~/components/ui/ActionButton.vue";
import { onlineScheduleService } from "~/api/online-schedule/OnlineScheduleService";
import { useRoute } from "vue-router";
import { useToast } from "~/composables/useToast";
import { formatDuration, formatDurationShort } from "~/utils/time";
import AssignADLModal from "./AssignADLModal.vue";
import InvoiceDeductionModal from "./InvoiceDeductionModal.vue";
import QrCodeModal from "~/components/ui/QrCodeModal.vue";
import QrScanner from "~/components/ui/QrScanner.vue";
import { scheduleService } from "~/api/schedule/ScheduleService.js";
import { patientAccessService } from "~/api/patient-access/PatientAccessService";

const { success, error } = useToast();

const route = useRoute();

const props = withDefaults(
    defineProps<{
        logs?: ScheduleItem[];
        loading?: boolean;
        date?: string;
        rangeEnd?: string;
        variant?: 1 | 2 | 3;
    }>(),
    {
        logs: () => [],
        loading: false,
        variant: 1,
    },
);
const emit = defineEmits<{
    (e: "update", schedule: ScheduleItem): void;
    (e: "refresh"): void;
    (e: "view-details", schedule: ScheduleItem): void;
}>();
const selectedSchedule = ref<AuditRow>();
const showAssignModal = ref(false);
const isAssigning = ref(false);
const assignConflicts = ref<string[]>([]);
const showScanner = ref(false);

const expandedKey = ref<string | null>(null);

function rowKey(log: AuditRow) {
    return `${log.schedule_id}-${log.schedule_services_id}`;
}

function isExpanded(log: AuditRow) {
    return expandedKey.value === rowKey(log);
}

function toggleRow(log: AuditRow) {
    const key = rowKey(log);

    expandedKey.value = expandedKey.value === key ? null : key;
}

const PAGE_SIZE = 2;

const visibleAssigneeCounts = ref<Record<string, number>>({});
const visibleAttendanceCounts = ref<Record<string, number>>({});

function visibleAssignees(log: AuditRow) {
    const count = visibleAssigneeCounts.value[rowKey(log)] ?? PAGE_SIZE;

    return log.assignees.slice(0, count);
}

function hasMoreAssignees(log: AuditRow): boolean {
    const count = visibleAssigneeCounts.value[rowKey(log)] ?? PAGE_SIZE;

    return log.assignees.length > count;
}

function loadMoreAssignees(log: AuditRow) {
    const key = rowKey(log);
    const count = visibleAssigneeCounts.value[key] ?? PAGE_SIZE;

    visibleAssigneeCounts.value[key] = count + PAGE_SIZE;
}

function newestFirstAttendance(log: AuditRow) {
    return log.online_logs.map((scan, index) => ({ scan, index })).reverse();
}

function visibleAttendance(log: AuditRow) {
    const count = visibleAttendanceCounts.value[rowKey(log)] ?? PAGE_SIZE;

    return newestFirstAttendance(log).slice(0, count);
}

function hasMoreAttendance(log: AuditRow): boolean {
    const count = visibleAttendanceCounts.value[rowKey(log)] ?? PAGE_SIZE;

    return log.online_logs.length > count;
}

function loadMoreAttendance(log: AuditRow) {
    const key = rowKey(log);
    const count = visibleAttendanceCounts.value[key] ?? PAGE_SIZE;

    visibleAttendanceCounts.value[key] = count + PAGE_SIZE;
}

function onRowClick(log: AuditRow) {
    toggleRow(log);
}

function openAssignModal(log: AuditRow) {
    selectedSchedule.value = log;
    showAssignModal.value = true;
}

function viewDetails(log: AuditRow) {
    const schedule = props.logs.find((s) => s.schedule_id === log.schedule_id);
    if (schedule) emit("view-details", schedule);
}

function closeAssignModal() {
    showAssignModal.value = false;
    assignConflicts.value = [];
    selectedSchedule.value = undefined;
}

async function handleAssignConfirm(payload: {
    schedule_service_id: number | null;
    assignments: unknown[];
}) {
    isAssigning.value = true;
    assignConflicts.value = [];
    try {
        const res = await scheduleService.action({
            type: "assign",
            branch_uuid: route.params.uuid,
            schedule_id: selectedSchedule.value?.schedule_id,
            ...payload,
        });
        success(res.message ?? "Succesfully assigned employee");
        emit("update", res.data);
        closeAssignModal();
    } catch (err: any) {
        if (err?.status === 409) {
            assignConflicts.value = [err.message];
            error("Schedule conflict");
        } else {
            error(
                err?.response?.data?.message ??
                    err?.message ??
                    "Failed to assign staff.",
            );
        }
        console.error(err);
    } finally {
        isAssigning.value = false;
    }
}

const generatingQr = ref(false);
const qrToken = ref<string | null>(null);
const showQrModal = ref(false);
const qrMode = ref<"clock-in" | "clock-out">("clock-in");
const qrCaregivers = ref<{ employee_id: number; name: string }[]>([]);
const qrCheckedIn = ref<{
    employee_id: number;
    name: string;
    in_timestamp: string | null;
} | null>(null);

async function generateQr(type: "in" | "out", schedule: AuditRow) {
    generatingQr.value = true;
    qrToken.value = null;
    qrMode.value = type === "in" ? "clock-in" : "clock-out";
    showQrModal.value = true;

    try {
        const res = await onlineScheduleService.generateQr({
            type,
            branch_uuid: route.params.uuid,
            schedule_services_id: schedule.schedule_services_id,
            as_family: props.variant === 3,
        });
        qrToken.value = res.data?.token ?? res.token ?? res;
        qrCaregivers.value = res.caregivers ?? res.data?.caregivers ?? [];
        qrCheckedIn.value = res.checked_in ?? res.data?.checked_in ?? null;
    } catch (err: any) {
        error(
            err?.response?.data?.message ??
                err?.data?.message ??
                err?.message ??
                "Internal Server Error",
        );
        showQrModal.value = false;
        console.error(err);

        if (err?.response?.status === 409 || err?.status === 409) {
            emit("refresh");
        }
    } finally {
        generatingQr.value = false;
    }
}

function closeQrModal() {
    showQrModal.value = false;
    qrToken.value = null;
}

function handleQrScanned() {
    success(
        qrMode.value === "clock-in"
            ? "Clocked in successfully."
            : "Clocked out successfully.",
    );
    emit("refresh");
    closeQrModal();
}

const REVIEW_NOTIFY_COOLDOWN_MS = 10 * 60 * 1000;

const reviewNotifyTick = ref(0);
const sendingReview = ref<number | null>(null);
const secondsTick = ref(0);
let secondsTickInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    secondsTickInterval = setInterval(() => {
        secondsTick.value++;
    }, 1000);
});

onUnmounted(() => {
    if (secondsTickInterval) clearInterval(secondsTickInterval);
});

type ShiftKey = "am" | "pm" | "full";

const SHIFT_HOURS: Record<ShiftKey, { start: number; end: number; label: string }> = {
    am: { start: 0, end: 12, label: "12:00 AM – 12:00 PM" },
    pm: { start: 12, end: 24, label: "12:00 PM – 12:00 AM" },
    full: { start: 0, end: 24, label: "Whole booking" },
};

function shiftOf(note?: string | null): ShiftKey | null {
    const text = (note ?? "").toLowerCase();

    if (/\bfull\b/.test(text)) return "full";
    if (/\bam\b/.test(text)) return "am";
    if (/\bpm\b/.test(text)) return "pm";

    return null;
}

function shiftHours(note?: string | null): string | null {
    const shift = shiftOf(note);

    return shift ? SHIFT_HOURS[shift].label : null;
}

function shiftEnded(log: AuditRow, note?: string | null): boolean {
    secondsTick.value;

    const shift = shiftOf(note);

    if (!shift || shift === "full" || log.status?.toLowerCase() !== "ongoing") return false;

    const now = new Date();
    const hour = now.getHours() + now.getMinutes() / 60;
    const { start, end } = SHIFT_HOURS[shift];

    return hour < start || hour >= end;
}

function reviewNotifyKey(log: AuditRow) {
    return `adl-review-notify:${log.schedule_id}`;
}

function lastReviewNotifyAt(log: AuditRow): number {
    if (typeof window === "undefined") return 0;

    try {
        const raw = window.localStorage.getItem(reviewNotifyKey(log));
        return raw ? Number(raw) || 0 : 0;
    } catch {
        return 0;
    }
}

function canRequestReview(log: AuditRow): boolean {
    reviewNotifyTick.value;
    secondsTick.value;

    return Date.now() - lastReviewNotifyAt(log) >= REVIEW_NOTIFY_COOLDOWN_MS;
}

function formatCountdown(ms: number): string {
    const totalSeconds = Math.max(0, Math.ceil(ms / 1000));
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${minutes}:${String(seconds).padStart(2, "0")}`;
}

function reviewButtonLabel(log: AuditRow): string {
    secondsTick.value;

    if (canRequestReview(log)) {
        return "Notify Admission Staff";
    }

    const waitMs =
        REVIEW_NOTIFY_COOLDOWN_MS - (Date.now() - lastReviewNotifyAt(log));

    return `Available in ${formatCountdown(waitMs)}`;
}

async function requestScheduleReview(log: AuditRow) {
    if (!canRequestReview(log) || sendingReview.value === log.schedule_id) {
        return;
    }

    sendingReview.value = log.schedule_id;

    try {
        await patientAccessService.executeAction({
            action: "request_schedule_review",
            patient_id: log.patient_id,
            schedule_id: log.schedule_id,
        });

        try {
            window.localStorage.setItem(
                reviewNotifyKey(log),
                String(Date.now()),
            );
        } catch {}

        reviewNotifyTick.value++;
        success("Admission staff have been notified to review this schedule.");
    } catch (err: any) {
        error(
            err?.data?.message ||
                err?.message ||
                "Failed to send the notification.",
        );
    } finally {
        sendingReview.value = null;
    }
}

const showDeductionModal = ref(false);
const deductionSchedule = ref<AuditRow | null>(null);
const isSendingDeduction = ref(false);

function openDeductionModal(log: AuditRow) {
    deductionSchedule.value = log;
    showDeductionModal.value = true;
}

function closeDeductionModal() {
    showDeductionModal.value = false;
    deductionSchedule.value = null;
}

async function submitDeductionRequest(payload: {
    amount: number;
    reason: string;
}) {
    const log = deductionSchedule.value;

    if (!log) return;

    isSendingDeduction.value = true;

    try {
        if (props.variant === 3) {
            await patientAccessService.executeAction({
                action: "request_invoice_deduction",
                patient_id: log.patient_id,
                schedule_id: log.schedule_id,
                amount: payload.amount,
                reason: payload.reason,
            });
        } else {
            await scheduleService.action({
                type: "request_deduction",
                branch_uuid: route.params.uuid,
                schedule_id: log.schedule_id,
                amount: payload.amount,
                reason: payload.reason,
            });
        }

        success("Accounting has been notified to review this deduction request.");
        closeDeductionModal();
    } catch (err: any) {
        error(
            err?.data?.message ||
                err?.message ||
                "Failed to send the deduction request.",
        );
    } finally {
        isSendingDeduction.value = false;
    }
}

const search = ref("");

const filteredLogs = computed<AuditRow[]>(() => {
    const rows: AuditRow[] = props.logs.flatMap((schedule) =>
        (schedule.services ?? []).map((service): AuditRow => {
            const activeAssignees = (service.assignees ?? []).filter(
                (assignee) => assignee.is_active,
            );

            const assignees = activeAssignees.map((assignee) => ({
                employee_id: assignee.employee_id,
                full_name: assignee.full_name ?? null,
                avatar: assignee.avatar ?? null,
                note: assignee.note ?? null,
                employee_role: assignee.employee_role ?? null,
                phone_number: assignee.phone_number ?? null,
                email: assignee.email ?? null,
            }));

            const online_logs = (service.assignees ?? []).flatMap((assignee) =>
                (assignee.online ?? []).map((scan) => ({
                    qr_in: scan.qr_in ?? null,
                    qr_out: scan.qr_out ?? null,
                    in_timestamp: scan.in_timestamp ?? null,
                    out_timestamp: scan.out_timestamp ?? null,
                    notes: scan.notes ?? null,
                    employee_id: assignee.employee_id,
                    employee_name: assignee.full_name ?? null,
                    employee_avatar: assignee.avatar ?? null,
                })),
            );

            online_logs.sort((a, b) => {
                const aTime = a.in_timestamp
                    ? new Date(a.in_timestamp).getTime()
                    : 0;
                const bTime = b.in_timestamp
                    ? new Date(b.in_timestamp).getTime()
                    : 0;
                return aTime - bTime;
            });

            const firstAssignee = assignees[0];

            return {
                status: schedule.status,
                category: schedule.category ?? null,
                schedule_id: schedule.schedule_id,
                schedule_code: schedule.schedule_code,
                scheduled_at: schedule.scheduled_at ?? null,
                schedule_services_id: service.schedule_services_id,
                total_hours: service.hours_booked ?? schedule.total_hours ?? 0,
                price: service.price ?? 0,

                is_active: !!firstAssignee,
                employee_id: firstAssignee?.employee_id ?? null,
                full_name: firstAssignee?.full_name ?? null,
                avatar: firstAssignee?.avatar ?? null,
                note: schedule.note ?? null,

                assignees,

                address: schedule.address ?? schedule.patient?.address ?? null,
                patient_id: schedule.patient?.patient_id ?? null,
                patient_uuid: schedule.patient?.patient_uuid ?? "",
                patient_full_name: schedule.patient?.full_name ?? "",

                online_logs,

                total_worked_minutes: online_logs.reduce(
                    (total: any, scan: any) => total + workedMinutes(scan),
                    0,
                ),
            };
        }),
    );

    const query = search.value.trim().toLowerCase();

    return rows.filter((row) => {
        const searchMatch =
            !query ||
            row.schedule_code.toLowerCase().includes(query) ||
            row.assignees.some((a) =>
                a.full_name?.toLowerCase().includes(query),
            );

        return searchMatch;
    });
});

const NOT_YET_DONE_STATUSES = ["pending"];

const upcomingLogs = computed(() =>
    filteredLogs.value.filter((log) =>
        NOT_YET_DONE_STATUSES.includes(log.status),
    ),
);

const historyLogs = computed(() =>
    filteredLogs.value.filter(
        (log) => !NOT_YET_DONE_STATUSES.includes(log.status),
    ),
);

const logGroups = computed(() => {
    if (props.variant === 3) {
        const groups: {
            key: string;
            title: string | null;
            logs: AuditRow[];
        }[] = [];

        if (upcomingLogs.value.length) {
            groups.push({
                key: "upcoming",
                title: "Scheduled Days",
                logs: upcomingLogs.value,
            });
        }

        if (historyLogs.value.length) {
            groups.push({
                key: "history",
                title: null,
                logs: historyLogs.value,
            });
        }

        return groups;
    }

    return [{ key: "all", title: null, logs: filteredLogs.value }];
});

const QR_GENERATION_BLOCKED_STATUSES = ["cancelled", "completed", "missed"];

function canGenerateQr(log: AuditRow): boolean {
    return (
        !!log.assignees.length &&
        !QR_GENERATION_BLOCKED_STATUSES.includes(log.status)
    );
}

function initials(name?: string | null) {
    if (!name) return "?";

    return name
        .split(" ")
        .map((word) => word.charAt(0))
        .slice(0, 2)
        .join("")
        .toUpperCase();
}

function formatDateTime(value?: string | null) {
    if (!value) return "—";

    return new Date(value).toLocaleString("en-US", {
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
}

function latestCheckIn(log: AuditRow) {
    return log.online_logs.reduce<AuditRow["online_logs"][number] | null>(
        (latest, scan) => {
            if (!scan.in_timestamp) return latest;
            if (!latest?.in_timestamp) return scan;

            return new Date(scan.in_timestamp) > new Date(latest.in_timestamp)
                ? scan
                : latest;
        },
        null,
    );
}

function isCurrentlyCheckedIn(log: AuditRow): boolean {
    const latest = latestCheckIn(log);
    return Boolean(latest && !latest.out_timestamp);
}

function isOnDuty(
    log: AuditRow,
    employeeId: string | number | null | undefined,
): boolean {
    if (!employeeId) return false;

    return log.online_logs.some(
        (scan) =>
            Number(scan.employee_id) === Number(employeeId) &&
            scan.in_timestamp &&
            !scan.out_timestamp,
    );
}

function workedMinutes(scan: {
    in_timestamp: string | null;
    out_timestamp: string | null;
}) {
    if (!scan.in_timestamp) {
        return 0;
    }

    const start = new Date(scan.in_timestamp).getTime();

    const end = scan.out_timestamp
        ? new Date(scan.out_timestamp).getTime()
        : Date.now();

    return Math.max(0, Math.round((end - start) / 60000));
}

function attendanceGapMinutes(log: AuditRow, index: number): number | null {
    const scan = log.online_logs[index];

    if (!scan?.in_timestamp) return null;

    const inTime = new Date(scan.in_timestamp).getTime();

    if (index === 0) {
        if (!log.scheduled_at) return null;

        return Math.floor(
            (inTime - new Date(log.scheduled_at).getTime()) / 60000,
        );
    }

    const previous = log.online_logs[index - 1];

    if (!previous?.out_timestamp) return null;

    return Math.floor(
        (inTime - new Date(previous.out_timestamp).getTime()) / 60000,
    );
}

function attendanceGapLabel(log: AuditRow, index: number): string | null {
    const minutes = attendanceGapMinutes(log, index);

    if (minutes === null || minutes <= 0) return null;

    const label = formatDurationShort(minutes / 60);

    return index === 0 ? `Late by ${label}` : `Gap of ${label}`;
}

function statusPill(status: string) {
    const map: Record<string, string> = {
        pending:
            "bg-violet-50 text-violet-700 ring-violet-100 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/20",
        ongoing:
            "bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20",
        completed:
            "bg-sky-50 text-sky-700 ring-sky-100 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/20",
        missed: "bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20",
        cancelled:
            "bg-gray-100 text-gray-500 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10",
    };

    return (
        map[status?.toLowerCase()] ??
        "bg-gray-100 text-gray-500 ring-gray-200 dark:bg-white/10 dark:text-gray-400 dark:ring-white/10"
    );
}

function elapsedMinutes(log: AuditRow): number {
    if (!log.scheduled_at) return log.total_worked_minutes;

    return Math.max(
        0,
        Math.round((Date.now() - new Date(log.scheduled_at).getTime()) / 60000),
    );
}

function progressPercent(log: AuditRow) {
    const booked = (log.total_hours ?? 0) * 60;
    const elapsed = elapsedMinutes(log);

    if (booked <= 0) return elapsed > 0 ? 100 : 0;

    return Math.min(100, Math.round((elapsed / booked) * 100));
}

function progressWidth(log: AuditRow) {
    const percent = progressPercent(log);

    return `${percent > 0 ? Math.max(percent, 6) : 0}%`;
}

function scheduledEndTime(log: AuditRow) {
    if (!log.scheduled_at) return null;

    return (
        new Date(log.scheduled_at).getTime() +
        (log.total_hours ?? 0) * 60 * 60000
    );
}

function overtimeMinutes(log: AuditRow) {
    const end = scheduledEndTime(log);

    if (end === null) {
        return Math.max(log.total_worked_minutes - log.total_hours * 60, 0);
    }

    return Math.max(0, Math.round((Date.now() - end) / 60000));
}

function progressFill(log: AuditRow) {
    if (overtimeMinutes(log) > 0) return "bg-amber-400";

    switch (log.status?.toLowerCase()) {
        case "ongoing":
            return "bg-emerald-500";
        case "completed":
            return "bg-sky-500";
        case "missed":
            return "bg-rose-300";
        case "cancelled":
            return "bg-slate-300 dark:bg-white/20";
        default:
            return "bg-primary";
    }
}

function progressTextTone(log: AuditRow) {
    if (overtimeMinutes(log) > 0) return "text-amber-600 dark:text-amber-300";
    if (elapsedMinutes(log) <= 0) return "text-muted dark:text-gray-400";

    return log.status?.toLowerCase() === "ongoing"
        ? "text-emerald-600 dark:text-emerald-300"
        : "text-secondary dark:text-white";
}

function progressLabel(log: AuditRow) {
    const elapsed = elapsedMinutes(log);

    if (elapsed <= 0) return "Not started";

    return `${progressPercent(log)}% · ${formatDurationShort(
        elapsed / 60,
    )} done`;
}

function remainingLabel(log: AuditRow) {
    const overtime = overtimeMinutes(log);

    if (overtime > 0) {
        return `+${formatDurationShort(overtime / 60)} over`;
    }

    return `${remainingShort(log)} left`;
}

function remainingMinutes(log: AuditRow) {
    const end = scheduledEndTime(log);

    if (end === null) {
        return Math.max(log.total_hours * 60 - log.total_worked_minutes, 0);
    }

    return Math.max(0, Math.round((end - Date.now()) / 60000));
}

function formatMinutesLong(totalMinutes: number): string {
    if (totalMinutes <= 0) return "0 mins";

    const days = Math.floor(totalMinutes / 1440);
    const hours = Math.floor((totalMinutes % 1440) / 60);
    const minutes = totalMinutes % 60;

    const parts: string[] = [];

    if (days) parts.push(`${days} day${days > 1 ? "s" : ""}`);
    if (hours) parts.push(`${hours} hr${hours > 1 ? "s" : ""}`);
    if (minutes) parts.push(`${minutes} min${minutes > 1 ? "s" : ""}`);

    if (parts.length <= 1) return parts[0] ?? "0 mins";

    return `${parts.slice(0, -1).join(", ")} and ${parts[parts.length - 1]}`;
}

function formatRemaining(log: AuditRow) {
    return formatMinutesLong(remainingMinutes(log));
}

function totalGapMinutes(log: AuditRow): number {
    return log.online_logs.reduce((total, _scan, index) => {
        const gap = attendanceGapMinutes(log, index);

        return total + (gap && gap > 0 ? gap : 0);
    }, 0);
}

function formatTotalGap(log: AuditRow): string {
    return formatMinutesLong(totalGapMinutes(log));
}

function remainingShort(log: AuditRow) {
    return formatDurationShort(remainingMinutes(log) / 60);
}

function formatCheckInTime(value?: string | null) {
    if (!value) return "—";

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return "—";

    const isToday = parsed.toDateString() === new Date().toDateString();

    return parsed.toLocaleString("en-US", {
        month: isToday ? undefined : "short",
        day: isToday ? undefined : "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    });
}

const onEnter = (el: Element) => {
    const panel = el as HTMLElement;

    panel.style.maxHeight = "0px";
    panel.style.opacity = "0";

    void panel.offsetHeight;

    panel.style.maxHeight = `${panel.scrollHeight}px`;
    panel.style.opacity = "1";
};

const onAfterEnter = (el: Element) => {
    (el as HTMLElement).style.maxHeight = "none";
};

const onLeave = (el: Element) => {
    const panel = el as HTMLElement;

    panel.style.maxHeight = `${panel.scrollHeight}px`;

    void panel.offsetHeight;

    panel.style.maxHeight = "0px";
    panel.style.opacity = "0";
};
</script>

<style scoped>
.row-collapse-enter-active,
.row-collapse-leave-active {
    transition:
        max-height 0.25s ease,
        opacity 0.2s ease;
}
</style>
