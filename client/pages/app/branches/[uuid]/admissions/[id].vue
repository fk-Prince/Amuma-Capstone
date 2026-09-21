<template>
    <div class="min-h-screen-header ">
        <div class="w-full mx-auto ">
            <button
                type="button"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline mb-6"
                @click="router.back()"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="w-4 h-4"
                >
                    <polyline points="15 18 9 12 15 6" />
                </svg>
                Back
            </button>

        
            <div
                v-if="loading"
                class="animate-pulse overflow-hidden rounded-2xl bg-white border border-primary-100 shadow-[0_0_40px_rgba(10,40,87,0.06)] dark:bg-secondary dark:border-primary-500/20"
            >
                <div
                    class="p-4 sm:p-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between xl:gap-6"
                >
                    <div class="flex items-center gap-3 sm:gap-4 min-w-0 flex-1">
                        <div
                            class="h-12 w-12 sm:h-14 sm:w-14 shrink-0 rounded-2xl bg-slate-200 dark:bg-white/15"
                        ></div>

                        <div class="min-w-0 flex-1 space-y-2">
                            <div
                                class="h-5 w-48 max-w-full rounded bg-slate-200 dark:bg-white/15"
                            ></div>
                            <div
                                class="h-3 w-64 max-w-full rounded bg-slate-100 dark:bg-white/10"
                            ></div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                        <div
                            v-for="width in [96, 64, 112, 92, 168, 84, 72]"
                            :key="width"
                            class="h-9 rounded-lg bg-slate-100 dark:bg-white/10"
                            :style="{ width: `${width}px` }"
                        ></div>
                    </div>
                </div>

                <div
                    class="grid lg:grid-cols-3 border-t border-primary-100 dark:border-primary-500/20"
                >
                    <div
                        class="lg:col-span-2 divide-y divide-primary-100 lg:border-r lg:border-primary-100 dark:divide-primary-500/20 dark:lg:border-primary-500/20"
                    >
                        <div class="p-6">
                            <div
                                class="h-2.5 w-32 rounded bg-slate-100 dark:bg-white/10"
                            ></div>

                            <div
                                class="mt-3 rounded-xl border border-primary-100 p-5 dark:border-primary-500/20"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="space-y-2">
                                        <div
                                            class="h-4 w-40 rounded bg-slate-200 dark:bg-white/15"
                                        ></div>
                                        <div
                                            class="h-3 w-56 rounded bg-slate-100 dark:bg-white/10"
                                        ></div>
                                    </div>
                                    <div
                                        class="h-4 w-20 rounded bg-slate-100 dark:bg-white/10"
                                    ></div>
                                </div>

                                <div
                                    class="mt-5 h-1.5 rounded-full bg-slate-100 dark:bg-white/10"
                                ></div>
                            </div>
                        </div>

                        <div class="p-6">
                            <div
                                class="h-3.5 w-36 rounded bg-slate-200 dark:bg-white/15"
                            ></div>
                            <div
                                class="mt-2 h-2.5 w-44 rounded bg-slate-100 dark:bg-white/10"
                            ></div>

                            <div
                                class="mt-4 ml-6 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200/70 dark:divide-white/10 dark:border-white/10"
                            >
                                <div v-for="row in 3" :key="row" class="px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-3 w-14 rounded bg-slate-200 dark:bg-white/15"
                                        ></div>
                                        <div
                                            class="h-3 w-24 rounded bg-slate-100 dark:bg-white/10"
                                        ></div>
                                        <div
                                            class="ml-auto h-3 w-16 rounded bg-slate-100 dark:bg-white/10"
                                        ></div>
                                    </div>
                                    <div
                                        class="mt-2 h-2.5 w-3/4 rounded bg-slate-100 dark:bg-white/10"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-primary-100 dark:divide-primary-500/20">
                        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-for="tile in 2"
                                :key="tile"
                                class="rounded-xl border border-primary-100 p-4 space-y-2 dark:border-primary-500/20"
                            >
                                <div
                                    class="h-2.5 w-20 rounded bg-slate-100 dark:bg-white/10"
                                ></div>
                                <div
                                    class="h-5 w-12 rounded bg-slate-200 dark:bg-white/15"
                                ></div>
                            </div>
                        </div>

                        <div class="p-5">
                            <div
                                class="h-2.5 w-28 rounded bg-slate-100 dark:bg-white/10"
                            ></div>
                            <div
                                class="mt-3 h-24 rounded-xl border border-dashed border-slate-200 dark:border-white/10"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else-if="!patient"
                class="rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-400 dark:border-white/10 dark:text-gray-500"
            >
                We couldn't find this patient's admission record.
            </div>

            <template v-else>
                <PlanLockNotice
                    v-if="facilityLocked"
                    class="mb-4"
                    title="This admission is read-only"
                    message="This branch has no In-house Facility plan. You can view the record, but admitting, extending, moving or discharging is locked."
                />

                <div
                    class="overflow-hidden rounded-lg bg-white border border-primary-100 shadow-[0_0_40px_rgba(10,40,87,0.06)] dark:bg-secondary dark:border-primary-500/20"
                >
                
                <div class="p-4 flex flex-col gap-5">
                    <div class="flex items-start gap-3 sm:gap-4 min-w-0">
                        <PatientAvatar
                            :src="patient.avatar"
                            :name="patient.full_name"
                            size-class="h-12 w-12 sm:h-14 sm:w-14 text-base sm:text-lg"
                            rounded-class="rounded-2xl"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <h1
                                    class="text-lg font-semibold text-primary-900 truncate dark:text-primary-300"
                                >
                                    {{ patient.full_name }}
                                </h1>

                                <span
                                    v-if="patient.patient_code"
                                    class="shrink-0 rounded-lg bg-primary-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300"
                                >
                                    {{ patient.patient_code }}
                                </span>

                                <span
                                    v-if="latestAdmission"
                                    class="shrink-0 text-xs font-medium capitalize rounded-full px-2.5 py-1"
                                    :class="
                                        statusBadgeClass(latestAdmission.status)
                                    "
                                >
                                    {{ latestAdmission.status }}
                                </span>

                                <NuxtLink
                                    v-if="patient.uuid"
                                    :to="`/app/branches/${uuid}/patients/${patient.uuid}`"
                                    class="shrink-0 text-xs font-medium text-primary hover:underline dark:text-primary-300"
                                >
                                    View full profile →
                                </NuxtLink>
                            </div>

                            <!-- Packed rather than gridded: the fields have very
                                 different widths, so fixed columns left the
                                 address stranded on a row of its own. -->
                            <dl
                                class="mt-2.5 flex flex-wrap gap-x-7 gap-y-2.5"
                            >
                                <div
                                    v-for="fact in patientFacts"
                                    :key="fact.label"
                                    class="min-w-0 max-w-full"
                                >
                                    <dt
                                        class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                    >
                                        {{ fact.label }}
                                    </dt>

                                    <dd
                                        class="truncate text-[13px] font-medium text-slate-700 dark:text-gray-200"
                                        :title="fact.value ?? undefined"
                                    >
                                        {{ fact.value }}
                                    </dd>
                                </div>
                            </dl>
                        </div>

           
                        <div
                            v-if="patientOutstanding"
                            class="hidden shrink-0 text-right sm:block"
                        >
                            <p
                                class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                            >
                                Overall balance
                            </p>

                            <p
                                class="mt-0.5 text-lg font-bold"
                                :class="
                                    patientOutstanding.total_balance > 0
                                        ? 'text-rose-600 dark:text-rose-300'
                                        : 'text-emerald-600 dark:text-emerald-300'
                                "
                            >
                                {{
                                    formatCurrency(
                                        patientOutstanding.total_balance,
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex flex-wrap items-center gap-2 min-w-0 border-t border-slate-100 pt-4 dark:border-white/10"
                    >
                        <ActionButton
                            variant="primary"
                            :disabled="isWaiting || isAdmitted || facilityLocked"
                            :tooltip="
                                blockedTip(
                                    isAdmitted
                                        ? 'This patient is already admitted. Discharge them before starting a new admission.'
                                        : 'This patient already has an admission waiting to be admitted.',
                                )
                            "
                            @click="openNewAdmissionModal"
                        >
                            New Admission
                        </ActionButton>

                        <ActionButton
                            variant="primary"
                            :disabled="!isWaiting || facilityLocked"
                            :tooltip="
                                blockedTip(
                                    isAdmitted
                                        ? 'This patient is already admitted.'
                                        : 'There is no waiting admission to admit. Start a new admission first.',
                                )
                            "
                            @click="handleAdmitClick"
                        >
                            Admit
                        </ActionButton>

                        <ActionButton
                            variant="outline"
                            :disabled="!isAdmitted"
                            :tooltip="unavailableWhileNotAdmitted"
                            @click="transferHistoryModalOpen = true"
                        >
                            Transfer History
                        </ActionButton>
                        <ActionButton
                            variant="outline"
                            :disabled="!isAdmitted || facilityLocked"
                            :tooltip="blockedTip(unavailableWhileNotAdmitted)"
                            @click="handleExtendClick"
                        >
                            Extend Stay
                        </ActionButton>

                        <ActionButton
                            variant="outline"
                            :disabled="!isAdmitted || facilityLocked"
                            :tooltip="blockedTip(unavailableWhileNotAdmitted)"
                            @click="openChangeRoomModal"
                        >
                            Change Room / Accommodation
                        </ActionButton>

                        <ActionButton
                            variant="outline"
                            :disabled="!isAdmitted || facilityLocked"
                            :tooltip="blockedTip(unavailableWhileNotAdmitted)"
                            @click="addServiceModalOpen = true"
                        >
                            Add Service
                        </ActionButton>

                        <ActionButton
                            variant="outline"
                            :disabled="!isAdmitted"
                            :tooltip="unavailableWhileNotAdmitted"
                            @click="caregiverModalOpen = true"
                        >
                            {{
                                caregiverCount > 0
                                    ? "View Caregiver"
                                    : "Assign Caregiver"
                            }}
                        </ActionButton>

                        <ActionButton
                            variant="danger"
                            :disabled="!isAdmitted || facilityLocked"
                            :tooltip="blockedTip(unavailableWhileNotAdmitted)"
                            @click="dischargeDialogOpen = true"
                        >
                            Discharge
                        </ActionButton>
                        <ActionButton
                            variant="danger"
                            :disabled="!isWaiting || facilityLocked"
                            :tooltip="
                                blockedTip(
                                    isAdmitted
                                        ? 'This patient is already admitted. Use Discharge instead of Cancel.'
                                        : 'Only an admission still waiting to be admitted can be cancelled.',
                                )
                            "
                            @click="cancelAdmissionDialogOpen = true"
                        >
                            Cancel
                        </ActionButton>
                    </div>
                </div>

                <div
                    class="grid lg:grid-cols-3 border-t border-primary-100 dark:border-primary-500/20"
                >
                    <div
                        class="lg:col-span-2 divide-y divide-primary-100 lg:border-r lg:border-primary-100 dark:divide-primary-500/20 dark:lg:border-primary-500/20"
                    >
                        <section class="p-6">
                            <h2
                                class="text-[11px] uppercase tracking-wide text-muted font-semibold mb-2.5 dark:text-gray-400"
                            >
                                Current admission
                            </h2>

                            <button
                                v-if="
                                    latestAdmission &&
                                    ['admitted', 'waiting'].includes(
                                        latestAdmission?.status,
                                    )
                                "
                                type="button"
                                class="w-full text-left rounded-xl border border-primary-100 p-5 hover:bg-primary-50/40 transition dark:border-primary-500/20 dark:hover:bg-primary-500/10"
                                @click="timelineAdmission = latestAdmission ?? null"
                            >
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                                >
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-primary-900 dark:text-primary-300"
                                        >
                                            {{
                                                latestAdmission.room?.room_no
                                                    ? `Room ${latestAdmission.room.room_no}`
                                                    : "No room assigned"
                                            }}
                                            <span
                                                v-if="
                                                    latestAdmission.bed?.bed_no
                                                "
                                                class="text-muted font-normal dark:text-gray-400"
                                            >
                                                · Bed
                                                {{ latestAdmission.bed.bed_no }}
                                            </span>
                                        </p>
                                        <p class="text-xs text-muted mt-1 dark:text-gray-400">
                                            <span
                                                v-if="
                                                    latestAdmission.status ===
                                                    'waiting'
                                                "
                                            >
                                                Waiting to Admit
                                                {{
                                                    formatDate(
                                                        latestAdmission.admitted_at,
                                                    )
                                                }}
                                            </span>

                                            <span v-else>
                                                Admitted
                                                {{
                                                    formatDate(
                                                        latestAdmission.admitted_at,
                                                    )
                                                }}

                                                <span
                                                    v-if="
                                                        latestAdmission.end_date
                                                    "
                                                >
                                                    — Ends
                                                    {{
                                                        formatDate(
                                                            latestAdmission.end_date,
                                                        )
                                                    }}
                                                </span>
                                            </span>
                                        </p>

                                    </div>

                                    <div
                                        v-if="latestAdmission.current_contract"
                                        class="text-right shrink-0"
                                    >
                                        <p
                                            class="text-lg text-primary capitalize"
                                        >
                                            {{ latestAdmission.status }}
                                        </p>
                                        <p class="text-xs text-muted mt-0.5 dark:text-gray-400">
                                            {{
                                                latestAdmission.current_contract
                                                    .accommodation_type
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="totalStayDays !== null" class="mt-5">
                                    <div
                                        class="h-1.5 rounded-full bg-slate-100 overflow-hidden dark:bg-white/10"
                                    >
                                        <div
                                            class="h-full rounded-full bg-primary transition-all duration-300"
                                            :style="{
                                                width: `${stayProgress ?? 0}%`,
                                            }"
                                        ></div>
                                    </div>
                                    <div
                                        class="mt-1.5 flex flex-wrap items-center justify-between gap-x-3 text-[11px] text-muted dark:text-gray-400"
                                    >
                                        <span>
                                            Day {{ dayOfStay ?? 0 }} of
                                            {{ totalStayDays }}
                                        </span>

                                        <span v-if="periodEndsOn">
                                            Renews
                                            {{ formatDate(periodEndsOn) }}
                                        </span>
                                    </div>
                                </div>
                            </button>

                            <div
                                v-else
                                class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400 dark:border-white/10 dark:text-gray-500"
                            >
                                No active admission on record.
                            </div>
                        </section>

                        <section v-if="timelineAdmissions.length" class="p-6">
                            <div
                                v-if="showingEndedAdmission"
                                class="mb-4 flex items-start gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 dark:border-white/10 dark:bg-white/5"
                            >
                                <svg
                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-gray-500"
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

                                <p
                                    class="text-xs leading-5 text-slate-500 dark:text-gray-400"
                                >
                                    This admission has ended. Showing the
                                    patient's most recent stay — admit them
                                    again to start a new one.
                                </p>
                            </div>

                            <AdmissionTimeline
                                flat
                                :admissions="timelineAdmissions"
                            />
                        </section>
                    </div>

                  <aside
                        class="divide-y divide-primary-100 dark:divide-primary-500/20"
                    >
                        <div class="p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div
                                    class="rounded-xl bg-primary-50/60 border border-primary-100 p-4 dark:bg-primary-500/10 dark:border-primary-500/20"
                                >
                                    <p
                                        class="text-[10px] uppercase tracking-wide text-muted font-semibold dark:text-gray-400"
                                    >
                                        Current Stay
                                    </p>

                                    <p
                                        class="text-xl font-semibold text-primary-900 mt-1 dark:text-primary-300"
                                    >
                                        {{ daysAdmitted }}
                                        <span class="text-xs font-medium text-muted dark:text-gray-400">
                                            day{{ daysAdmitted === 1 ? "" : "s" }}
                                        </span>
                                    </p>

                                    <p class="text-[10px] text-muted mt-1 dark:text-gray-400">
                                        {{
                                            latestAdmission?.status === "admitted"
                                                ? "Currently admitted"
                                                : "No active stay"
                                        }}
                                    </p>
                            </div>

                            <div
                                class="rounded-xl bg-slate-50 border border-slate-100 p-4 dark:bg-white/5 dark:border-white/10"
                            >
                                <p
                                    class="text-[10px] uppercase tracking-wide text-muted font-semibold dark:text-gray-400"
                                >
                                    Total Admissions
                                </p>

                                <p
                                    class="text-xl font-semibold text-primary-900 mt-1 dark:text-primary-300"
                                >
                                    {{ patient.admissions?.length ?? 0 }}
                                </p>

                                <p class="text-[10px] text-muted mt-1 dark:text-gray-400">
                                    Lifetime admissions
                                </p>
                            </div>
                        </div>
                    </div>
                   
                      <section class="p-5">
                            <h2
                                class="text-[11px] uppercase tracking-wide text-muted font-semibold mb-2.5 dark:text-gray-400"
                            >
                                Admission history
                                <span v-if="pastAdmissions.length">
                                    ({{ pastAdmissions.length }})
                                </span>
                            </h2>

                            <div
                                v-if="!pastAdmissions.length"
                                class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400 dark:border-white/10 dark:text-gray-500"
                            >
                                No previous admissions.
                            </div>

                            <div v-else class="space-y-3">
                                <button
                                    v-for="admission in pastAdmissions"
                                    :key="admission.patient_admission_id"
                                    type="button"
                                    class="w-full text-left rounded-xl border border-primary-100 p-5 hover:bg-primary-50/40 transition dark:border-primary-500/20 dark:hover:bg-primary-500/10"
                                    @click="timelineAdmission = admission"
                                >
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-semibold text-primary-900 dark:text-primary-300"
                                            >
                                                {{
                                                    admission.room?.room_no
                                                        ? `Room ${admission.room.room_no}`
                                                        : "No room assigned"
                                                }}

                                                <span
                                                    v-if="admission.bed?.bed_no"
                                                    class="text-muted font-normal dark:text-gray-400"
                                                >
                                                    · Bed
                                                    {{ admission.bed.bed_no }}
                                                </span>
                                            </p>

                                            <p class="text-xs text-muted mt-1 dark:text-gray-400">
                                                Admitted
                                                {{
                                                    formatDate(
                                                        admission.admitted_at,
                                                    )
                                                }}

                                                <span v-if="admission.end_date">
                                                    — Ended
                                                    {{
                                                        formatDate(
                                                            admission.end_date,
                                                        )
                                                    }}
                                                </span>
                                            </p>
                                        </div>

                                        <div
                                            class="flex items-center gap-3 shrink-0"
                                        >
                                            <div
                                                v-if="
                                                    admission.current_contract
                                                "
                                                class="text-right"
                                            >
                                                <p
                                                    class="text-xs text-muted mt-0.5 dark:text-gray-400"
                                                >
                                                    {{
                                                        admission
                                                            .current_contract
                                                            .accommodation_type
                                                    }}
                                                </p>
                                            </div>

                                            <span
                                                class="text-xs font-medium capitalize rounded-full px-2.5 py-1"
                                                :class="
                                                    statusBadgeClass(
                                                        admission.status,
                                                    )
                                                "
                                            >
                                                {{ admission.status }}
                                            </span>
                                        </div>
                                    </div>

                                    <p
                                        v-if="admission.note"
                                        class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs text-muted dark:bg-white/5 dark:text-gray-400"
                                    >
                                        {{ admission.note }}
                                    </p>

                                    <p
                                        class="mt-3 text-[11px] font-medium text-primary"
                                    >
                                        View timeline →
                                    </p>
                                </button>
                            </div>
                        </section>
                 </aside>
                </div>
                </div>
            </template>
        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-150"
                enter-from-class="opacity-0"
                leave-active-class="transition duration-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="timelineAdmission"
                    class="fixed inset-0 z-[70] flex items-center justify-center bg-primary-900/50 p-4 backdrop-blur-sm"
                    @click.self="timelineAdmission = null"
                >
                    <div
                        class="w-full max-w-2xl max-h-[85dvh] overflow-y-auto rounded-2xl bg-white shadow-xl dark:bg-secondary"
                    >
                        <div
                            class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-primary-100 bg-white px-5 py-4 dark:border-primary-500/20 dark:bg-secondary"
                        >
                            <div class="min-w-0">
                                <h3
                                    class="text-sm font-semibold text-primary-900 dark:text-primary-300"
                                >
                                    {{
                                        timelineAdmission.room?.room_no
                                            ? `Room ${timelineAdmission.room.room_no}`
                                            : "Admission"
                                    }}
                                    <span
                                        v-if="timelineAdmission.bed?.bed_no"
                                        class="font-normal text-muted dark:text-gray-400"
                                    >
                                        · Bed
                                        {{ timelineAdmission.bed.bed_no }}
                                    </span>
                                </h3>

                                <p class="mt-1 text-xs text-muted dark:text-gray-400">
                                    Admitted
                                    {{
                                        formatDate(
                                            timelineAdmission.admitted_at,
                                        )
                                    }}
                                    <span v-if="timelineAdmission.end_date">
                                        — Ended
                                        {{
                                            formatDate(
                                                timelineAdmission.end_date,
                                            )
                                        }}
                                    </span>
                                </p>
                            </div>

                            <button
                                type="button"
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10"
                                aria-label="Close"
                                @click="timelineAdmission = null"
                            >
                                ✕
                            </button>
                        </div>

                        <div class="p-5">
                            <AdmissionTimeline
                                flat
                                :admissions="[timelineAdmission]"
                            />
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <Teleport to="body">
            <div
                v-if="admitModalOpen"
                class="fixed inset-0 bg-primary-900/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                @click.self="admitModalOpen = false"
            >
                <div
                    class="bg-white rounded-2xl shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60 w-full max-w-sm p-6 dark:bg-secondary dark:ring-primary-500/20"
                >
                    <h3 class="text-base font-semibold text-primary-900 dark:text-primary-300">
                        Admit Patient
                    </h3>
                    <p class="text-xs text-muted mt-1 mb-4 dark:text-gray-400">
                        Confirm the admission date 
                    </p>

                    <div class="space-y-4">
                        <BaseInput
                            label="Admission Date"
                            mode="date"
                            v-model="admitDate"
                            :min="todayStr"
                            :max="todayStr"
                        />
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700 transition dark:text-gray-400 dark:hover:text-gray-400"
                            @click="admitModalOpen = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="!admitDate || actionLoading"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:opacity-90 disabled:opacity-40 disabled:cursor-not-allowed transition"
                            @click="confirmAdmit"
                        >
                            {{ actionLoading ? "Admitting..." : "Admit" }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <AdmissionDetail
                v-if="newAdmissionModalOpen"
                variant="new"
                :loading="loadingContract"
                :roomContract="roomContract"
                :model="reserved"
                :errors="reservedErrors"
                :requireAdmissionDate="true"
                @update:model="reserved = $event"
                @close="closeNewAdmissionModal"
                @confirm="handleNewAdmissionConfirm"
            />
        </Teleport>

        <AdmissionDischarge
            :open="dischargeDialogOpen"
            :admission="currentAdmission"
            :future-invoices="notStartedInvoices"
            :billing="patient?.billing ?? null"
            :loading="actionLoading"
            @confirm="confirmDischarge"
            @cancel="dischargeDialogOpen = false"
        />

        <AdmissionCancel
            :open="cancelAdmissionDialogOpen"
            :loading="actionLoading"
            @confirm="confirmCancelAdmission"
            @cancel="cancelAdmissionDialogOpen = false"
        />

        <ConfirmDialog
            :open="newAdmissionDialogOpen"
            title="Confirm New Admission"
            message="Are you sure you want to admit this patient?"
            description="This will create a new admission record for this patient."
            confirm-label="Admit"
            variant="default"
            :loading="actionLoading"
            @confirm="confirmNewAdmission"
            @cancel="cancelNewAdmissionConfirm"
        />

        <ConfirmDialog
            :open="paymentRequiredDialogOpen"
            title="Payment Required"
            :message="`${formatCurrency(unpaidAmount)} remains unpaid.`"
            description="Full payment is required before this patient can be admitted."
            confirm-label="Understood"
            hide-cancel
            variant="danger"
            @confirm="paymentRequiredDialogOpen = false"
            @cancel="paymentRequiredDialogOpen = false"
        />

        <ConfirmDialog
            :open="unpaidAdmitDialogOpen"
            title="Payment Incomplete"
            :message="`${formatCurrency(unpaidAmount)} remains unpaid.`"
            description="The patient has not fully paid the admission invoice. Are you sure you want to continue?"
            confirm-label="Yes, Continue"
            cancel-label="Cancel"
            variant="danger"
            :loading="actionLoading"
            @confirm="proceedToAdmitModal"
            @cancel="unpaidAdmitDialogOpen = false"
        />


        <ConfirmDialog
            :open="unpaidExtendDialogOpen"
            title="Unpaid Invoice"
            message="This patient hasn't paid yet."
            description="Are you sure you want to extend the stay without full payment?"
            confirm-label="Yes, Continue"
            variant="danger"
            :loading="actionLoading"
            @confirm="proceedToExtendModal"
            @cancel="unpaidExtendDialogOpen = false"
        />

        <BillingCycleModal
            :open="extendModalOpen"
            :admission="latestAdmission ?? null"
            @select="handleExtendSelect"
            @close="extendModalOpen = false"
        />

        <ChangeRoomModal
            :open="changeRoomModalOpen"
            :admission="latestAdmission ?? null"
            @select="handleChangeRoomSelect"
            @close="changeRoomModalOpen = false"
        />

        <TransferHistoryModal
            :open="transferHistoryModalOpen"
            :admission="latestAdmission ?? null"
            @close="transferHistoryModalOpen = false"
        />

        <CaregiverShiftModal
            :open="caregiverModalOpen"
            :admission-id="
                (currentAdmission ?? latestAdmission)?.patient_admission_id ??
                null
            "
            :patient-name="patient?.full_name"
            :branch-uuid="String(route.params.uuid)"
            @close="caregiverModalOpen = false"
            @count="caregiverCount = $event"
        />

        <AddServiceModal
            :open="addServiceModalOpen"
            :patient-uuid="id"
            :patient-name="patient?.full_name"
            :branch-uuid="uuid"
            @close="addServiceModalOpen = false"
        />
    </div>
</template>
<script setup lang="ts"">
import { computed, ref, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { formatPhone } from "~/utils/phone";
import type { PatientRetrieve, Admission } from "~/types/patient";
import type { RoomContract, Reserved } from "~/types/contract";
import { patientService } from "~/api/patient/PatientService";
import { formatCurrency as formatCurrencyUtil } from "~/utils/currency";
import { admissionService } from "~/api/admission/AdmissionService";
import { useToast } from "~/composables/useToast";
import { toLocalDateString } from "~/utils/time";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import BillingCycleModal from "~/components/sections/app/Patient/BillingCycleModal.vue";
import type { Room } from "~/types/room";
import type { Bed } from "~/types/bed";
import ChangeRoomModal from "~/components/sections/app/Admission/ChangeRoomModal.vue";
import TransferHistoryModal from "~/components/sections/app/Admission/TransferHistoryModal.vue";
import CaregiverShiftModal from "~/components/sections/app/Admission/CaregiverShiftModal.vue";
import AdmissionTimeline from "~/components/sections/app/Admission/AdmissionTimeline.vue";
import ActionButton from "~/components/ui/ActionButton.vue";
import AdmissionDetail from "~/components/sections/app/Admission/AdmissionDetail.vue";
import AdmissionDischarge from "~/components/sections/app/Admission/AdmissionDischarge.vue";
import AdmissionCancel from "~/components/sections/app/Admission/AdmissionCancel.vue";
import AddServiceModal from "~/components/sections/app/Admission/AddServiceModal.vue";
import PlanLockNotice from "~/components/ui/PlanLockNotice.vue";
import PatientAvatar from "~/components/ui/PatientAvatar.vue";
import { useBranchStore } from "~/stores/branch";
import { useBranchPlan } from "~/composables/useBranchPlan";

definePageMeta({
    layout: "dashboard",
    middleware: ["auth-client"],
});

useHead({ title: "Patient Admission" });

const unpaidAmount = computed(() => {
    if (patient.value?.latest_admission?.status !=='waiting') return 0;
    const invoice = patient.value?.latest_admission?.invoices[0];
    const price = Number(invoice?.price ?? 0)
    const paid = Number(invoice?.paid_amount ?? 0)
    return Math.max(price - paid, 0)
})


const route = useRoute();
const router = useRouter();
const { success, error } = useToast();

const id = computed(() => route.params.id as string);
const uuid = computed(() => route.params.uuid as string);

const loading = ref(true);
const patient = ref<PatientRetrieve | null>(null);

const latestAdmission = computed<Admission | undefined>(
    () => patient.value?.latest_admission,
);

// Carried on the admission's discharge calculation, which is the one place the
// whole account is totalled.
const patientOutstanding = computed(
    () =>
        patient.value?.current_admission?.discharge_calculation?.outstanding ??
        patient.value?.latest_admission?.discharge_calculation?.outstanding ??
        null,
);

const currentAdmission = computed<Admission | undefined>(
    () => patient.value?.current_admission,
);

const caregiverModalOpen = ref(false);
const caregiverCount = ref(0);

watch(
    () => (currentAdmission.value ?? latestAdmission.value)?.caregiver_count,
    (count) => {
        caregiverCount.value = count ?? 0;
    },
    { immediate: true },
);

// One stay at a time: the one in progress, or the most recent if the patient has
// been discharged. Earlier admissions are reached through the Admission History
// panel, which opens each one's own timeline in a modal.
const timelineAdmissions = computed<Admission[]>(() => {
    const admission = currentAdmission.value ?? latestAdmission.value;

    return admission ? [admission] : [];
});

// The timeline still shows after a discharge, so it has to say so — otherwise a
// finished stay reads as one still running.
const showingEndedAdmission = computed(
    () => !currentAdmission.value && !!latestAdmission.value,
);

const pastAdmissions = computed<Admission[]>(() => {
    const all = patient.value?.admissions ?? [];
    const latestId = latestAdmission.value?.patient_admission_id;
    const latestStatus = latestAdmission.value?.status;

    return all
        .filter((a) => {
            if (a.status !== "discharged" && a.status !== "cancelled") {
                return false;
            }

            if (a.patient_admission_id === latestId) {
                return (
                    latestStatus === "discharged" ||
                    latestStatus === "cancelled"
                );
            }

            return true;
        })
        .sort(
            (a, b) =>
                new Date(b.admitted_at).getTime() -
                new Date(a.admitted_at).getTime(),
        );
});

const latestInvoice = computed(() => {
    const invoices = latestAdmission.value?.invoices ?? [];
    return invoices.length ? invoices[invoices.length - 1] : null;
});

const initials = computed(() => {
    const parts = (patient.value?.full_name ?? "").trim().split(/\s+/);
    return (
        (parts[0]?.[0] ?? "") + (parts[parts.length - 1]?.[0] ?? "")
    ).toUpperCase();
});

const patientFacts = computed(() => {
    const age = patient.value?.age;

    return [
        { label: "Gender", value: patient.value?.gender },
        { label: "Age", value: age ? `${age} years old` : null },
        { label: "Blood type", value: patient.value?.blood_type },
        { label: "Contact", value: formatPhone(patient.value?.phone_number) },
        {
            label: "Address",
            value: patient.value?.location?.full_address,
            wide: true,
        },
    ].filter((fact) => !!fact.value);
});

const status = computed(() => latestAdmission.value?.status?.toLowerCase());
const isWaiting = computed(() => status.value === "waiting");
const isAdmitted = computed(() => status.value === "admitted");

// Most actions need an in-progress stay, and the reason they are unavailable
// depends on where the patient actually is.
const unavailableWhileNotAdmitted = computed(() => {
    if (isWaiting.value) {
        return "This admission is still waiting. Admit the patient first.";
    }

    if (status.value === "discharged") {
        return "This patient has been discharged. Start a new admission first.";
    }

    if (status.value === "cancelled") {
        return "This admission was cancelled. Start a new admission first.";
    }

    return "This patient has no active admission. Start a new admission first.";
});

const { hasFacilityPlan } = useBranchPlan();
const facilityLocked = computed(() => !hasFacilityPlan.value);
const addServiceModalOpen = ref(false);

function blockedTip(reason: string) {
    return facilityLocked.value
        ? "Locked — this branch has no In-house Facility plan."
        : reason;
}

const isInvoiceUnpaid = computed(() => {
    return (latestInvoice.value?.status ?? "").toLowerCase() !== "paid";
});

const branchStore = useBranchStore();

const requiresFullPaymentOnAdmit = computed(
    () => branchStore.activeBranch?.settings?.requires_full_payment_on_admit ?? true,
);

const admitBlockedByUnpaidInvoice = computed(
    () => isWaiting.value && isInvoiceUnpaid.value && requiresFullPaymentOnAdmit.value,
);

const admitModalOpen = ref(false);
const admitDate = ref("");
const admitDeposit = ref("");
const dischargeDialogOpen = ref(false);
const extendModalOpen = ref(false);
const actionLoading = ref(false);
const unpaidAdmitDialogOpen = ref(false);
const paymentRequiredDialogOpen = ref(false);
const unpaidExtendDialogOpen = ref(false);
const cancelAdmissionDialogOpen = ref(false);
const todayStr = toLocalDateString(new Date());
const changeRoomModalOpen = ref(false);
const transferHistoryModalOpen = ref(false);
// The inline timeline covers the current stay only; any other admission is
// opened in a modal showing just that one.
const timelineAdmission = ref<Admission | null>(null);

const newAdmissionModalOpen = ref(false);
const newAdmissionDialogOpen = ref(false);
const loadingContract = ref(false);
const roomContract = ref<RoomContract[]>([]);
const reserved = ref<Reserved | null>(null);
const reservedErrors = ref<Record<string, string>>({});
const pendingAdmission = ref<Reserved | null>(null);

function openChangeRoomModal() {
    changeRoomModalOpen.value = true;
}
const notStartedInvoices = computed(
    () => patient.value?.latest_admission?.future_periods?.invoices ?? [],
);


function confirmDischarge(payload: {
    refund: boolean;
    currentRefundAmount: number | null;
    note: string;
    force: boolean;
}) {
    runAction("discharge", {
        refund: payload.refund,
        current_refund_amount: payload.currentRefundAmount,
        note: payload.note,
        force: payload.force,
    });
}

function openAdmitModal() {
    admitDate.value = todayStr;
    admitDeposit.value = "";
    admitModalOpen.value = true;
}

function handleAdmitClick() {
    if (admitBlockedByUnpaidInvoice.value) {
        paymentRequiredDialogOpen.value = true;
        return;
    }

    if (isInvoiceUnpaid.value) {
        unpaidAdmitDialogOpen.value = true;
        return;
    }
    openAdmitModal();
}

function proceedToAdmitModal() {
    unpaidAdmitDialogOpen.value = false;
    openAdmitModal();
}

function openExtendModal() {
    extendModalOpen.value = true;
}

function handleExtendClick() {
    if (isInvoiceUnpaid.value) {
        unpaidExtendDialogOpen.value = true;
        return;
    }
    openExtendModal();
}

function proceedToExtendModal() {
    unpaidExtendDialogOpen.value = false;
    openExtendModal();
}

async function openNewAdmissionModal() {
    reserved.value = null;
    reservedErrors.value = {};
    newAdmissionModalOpen.value = true;
    await fetchRoomContracts();
}

function closeNewAdmissionModal() {
    newAdmissionModalOpen.value = false;
    reserved.value = null;
    reservedErrors.value = {};
}

async function fetchRoomContracts() {
    loadingContract.value = true;
    try {
        const res = await admissionService.action({
            branch_uuid: route.params.uuid,
            action: "branch_contract",
        });
        roomContract.value = res.data?.data ?? res.data ?? res ?? [];
    } catch (err) {
        console.error("Failed loading contracts", err);
        roomContract.value = [];
    } finally {
        loadingContract.value = false;
    }
}

async function runAction(
    action: "admit" | "discharge" | "extend" | "change_room" | "cancel",
    extra: Record<string, unknown> = {},
) {
    if (!latestAdmission.value) return;

    actionLoading.value = true;

    try {
        const res = await admissionService.action({
            branch_uuid: uuid.value,
            p_uuid: id.value,
            admission_id: latestAdmission.value.patient_admission_id,
            action,
            ...extra,
        });
        patient.value = res.data;
        success(res?.message ?? "Updated successfully.");
    } catch (err: any) {
        error(err?.data?.message ?? "Something went wrong. Please try again.");
    } finally {
        actionLoading.value = false;
        admitModalOpen.value = false;
        dischargeDialogOpen.value = false;
        extendModalOpen.value = false;
        cancelAdmissionDialogOpen.value = false;
    }
}

function confirmCancelAdmission(reason: string) {
    runAction("cancel", { note: reason });
}

function confirmAdmit() {
    if (!admitDate.value) return;
    runAction("admit", {
        admitted_at: admitDate.value,
        deposit: admitDeposit.value,
    });
}


async function handleExtendSelect(payload: {
    contract: RoomContract;
    end_date: string;
    room?: Room;
    bed?: Bed;
}) {
    await runAction("extend", {
        end_date: payload.end_date,
        contract_id: payload.contract.contract_id,
        ...(payload.room ? { room_id: payload.room.room_id } : {}),
        ...(payload.bed ? { bed_id: payload.bed.bed_id } : {}),
    });
    extendModalOpen.value = false;
}

async function handleChangeRoomSelect(payload: {
    room: Room;
    bed: Bed;
    reason: string;
    contractId: number;
    isAccommodationChange: boolean;
}) {
    await runAction("change_room", {
        room_id: payload.room.room_id,
        bed_id: payload.bed.bed_id,
        contract_id: payload.contractId,
        reason: payload.reason,
    });
    changeRoomModalOpen.value = false;
}

function handleNewAdmissionConfirm(payload: Reserved) {
    pendingAdmission.value = payload;
    newAdmissionModalOpen.value = false;
    newAdmissionDialogOpen.value = true;
}

function cancelNewAdmissionConfirm() {
    newAdmissionDialogOpen.value = false;
    pendingAdmission.value = null;
}

async function confirmNewAdmission() {
    if (!pendingAdmission.value) return;

    actionLoading.value = true;
    reservedErrors.value = {};

    try {
        const payload = {
            contract_id: pendingAdmission.value.contract_id,
            bed_id: pendingAdmission.value.bed?.bed_id,
            room_id: pendingAdmission.value.room?.room_id,
            admitted_at: pendingAdmission.value.admitted_at,
        };
        const res = await admissionService.action({
            branch_uuid: uuid.value,
            p_uuid: id.value,
            action: "new_admission",
            ...payload,
        });
        patient.value = res.data;
        success(res?.message ?? "Patient admitted successfully.");
        newAdmissionDialogOpen.value = false;
        pendingAdmission.value = null;
        reserved.value = null;
    } catch (err: any) {
        reservedErrors.value = err?.data?.errors ?? {};
        error(err?.data?.message ?? "Something went wrong. Please try again.");
        newAdmissionDialogOpen.value = false;
        newAdmissionModalOpen.value = true;
    } finally {
        newAdmissionDialogOpen.value = false;
        actionLoading.value = false;
    }
}

// Progress tracks the billing period actually in effect, not the whole stay.
// A prepaid extension pushes admission.end_date years out, and folding that
// into one bar made a patient on day 211 of a year read as "day 211 of 731".
const currentPeriod = computed(
    () => latestAdmission.value?.current_period ?? null,
);

const periodCycleDays = computed(() =>
    (currentPeriod.value?.contract?.billing_cycle ?? "").toUpperCase() ===
    "YEARLY"
        ? 365
        : 30,
);

function wholeDaysBetween(from?: string | null, to?: string | null) {
    if (!from || !to) return null;

    const start = new Date(from);
    const end = new Date(to);

    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) {
        return null;
    }

    start.setHours(0, 0, 0, 0);
    end.setHours(0, 0, 0, 0);

    return Math.round((end.getTime() - start.getTime()) / 86400000);
}

// The bar covers everything the stay is paid up to, prepaid periods included,
// so a patient a day into a two-year booking reads as "Day 1 of 730".
const totalStayDays = computed(() => {
    const total = wholeDaysBetween(
        latestAdmission.value?.admitted_at,
        latestAdmission.value?.end_date,
    );

    return total && total > 0 ? total : null;
});

const daysAdmitted = computed(() => {
    if (status.value !== "admitted" || !latestAdmission.value?.admitted_at) {
        return 0;
    }

    const elapsed = wholeDaysBetween(
        latestAdmission.value.admitted_at,
        new Date().toISOString(),
    );

    return Math.max(0, elapsed ?? 0) + 1;
});

const dayOfStay = computed(() => {
    if (!latestAdmission.value) return null;
    if (status.value !== "admitted") return 0;

    return totalStayDays.value
        ? Math.min(totalStayDays.value, daysAdmitted.value)
        : daysAdmitted.value;
});

const periodEndsOn = computed(() => {
    const startedAt = currentPeriod.value?.started_at;
    if (!startedAt) return null;

    const end = new Date(startedAt);
    if (Number.isNaN(end.getTime())) return null;

    end.setDate(end.getDate() + periodCycleDays.value);

    return end.toISOString();
});

const stayProgress = computed(() => {
    if (dayOfStay.value === null || !totalStayDays.value) return null;
    return Math.min(
        100,
        Math.round((dayOfStay.value / totalStayDays.value) * 100),
    );
});

async function fetchPatient() {
    loading.value = true;
    patient.value = null;
    try {
        const response = await patientService.show(
            {
                branch_uuid: uuid.value,
                p_uuid: id.value,
            },
            id.value,
        );
        const list = response.data ?? response;
        patient.value = Array.isArray(list) ? (list[0] ?? null) : list;
    } catch (err: any) {
        error(err?.data?.message ?? "Couldn't load admission details.");
    } finally {
        loading.value = false;
    }
}

function formatDate(value?: string | null) {
    if (!value) return "—";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function formatCurrency(value?: string | number) {
    if (value === undefined || value === null || value === "") return "—";
    const num = Number(value);
    if (Number.isNaN(num)) return String(value);
    return formatCurrencyUtil(num);
}

function statusBadgeClass(status?: string) {
    switch (status?.toLowerCase()) {
        case "admitted":
            return "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300";
        case "waiting":
            return "bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300";
        case "discharged":
        case "completed":
            return "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-400";
        case "cancelled":
        case "rejected":
            return "bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300";
        default:
            return "bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300";
    }
}

onMounted(fetchPatient);
</script>
