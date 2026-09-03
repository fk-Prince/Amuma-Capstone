<template>
    <Transition name="modal">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div
                class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"
                @click="close"
            />

            <div
                v-if="schedule"
                class="relative flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Schedule Details
                        </p>

                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">
                                {{ schedule.schedule_code }}
                            </h2>

                            <span
                                class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium capitalize text-slate-600 dark:bg-white/10 dark:text-gray-400"
                            >
                                {{ schedule.category }}
                            </span>
                        </div>
                    </div>

                    <span
                        v-if="!isEditing"
                        class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold capitalize"
                        :class="scheduleStatusTheme(schedule.status).badge"
                    >
                        {{ scheduleStatusLabel(schedule.status) }}
                    </span>
                </div>

                <!-- The body splits on wide screens: patient, location and the
                     schedule details/form stack on the left, and the services
                     list — the tallest part — gets its own column on the
                     right. Explicit grid placement keeps the markup in reading
                     order. Below xl it falls back to a single stack. -->
                <div
                    class="min-h-0 flex-1 space-y-4 overflow-y-auto p-6 xl:space-y-0"
                    :class="bodyGridClass"
                >
                    <div
                        class="rounded-xl bg-slate-50 p-3 dark:bg-white/5"
                        :class="leftColClass"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary"
                            >
                                <svg
                                    width="20"
                                    height="20"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <circle cx="12" cy="8" r="4" />
                                    <path d="M4 20c0-4 3.5-6 8-6s8 2 8 6" />
                                </svg>
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-slate-400 dark:text-gray-500">Patient</p>
                                <p
                                    class="truncate font-semibold text-slate-800 dark:text-white"
                                >
                                    {{ schedule.patient?.full_name ?? "—" }}
                                </p>
                            </div>

                            <button
                                v-if="patientUuid"
                                type="button"
                                class="flex shrink-0 items-center gap-1.5 rounded-lg border border-primary/20 bg-white px-3 py-1.5 text-xs font-medium text-primary transition hover:bg-primary/5 dark:bg-secondary"
                                @click="viewPatient"
                            >
                                View Patient

                                <svg
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M7.5 5 12.5 10 7.5 15" />
                                </svg>
                            </button>
                        </div>

                        <div
                            v-if="schedule.patient?.guardian"
                            class="mt-3 flex items-center gap-2.5 border-t border-slate-200 pt-3 dark:border-white/10"
                        >
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-slate-400 ring-1 ring-slate-200 dark:bg-secondary dark:text-gray-500 dark:ring-white/10"
                            >
                                <Users class="h-4 w-4" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="flex flex-wrap items-center gap-1.5 truncate text-sm font-medium text-slate-700 dark:text-gray-400"
                                >
                                    {{ schedule.patient.guardian.full_name }}

                                    <span
                                        v-if="
                                            schedule.patient.guardian
                                                .relationship
                                        "
                                        class="rounded-full bg-slate-200 px-1.5 py-0.5 text-[10px] font-medium capitalize text-slate-600 dark:bg-white/15 dark:text-gray-400"
                                    >
                                        {{
                                            schedule.patient.guardian
                                                .relationship
                                        }}
                                    </span>
                                </p>

                                <p class="text-[11px] text-slate-400 dark:text-gray-500">
                                    Guardian
                                </p>
                            </div>

                            <a
                                v-if="schedule.patient.guardian.phone_number"
                                :href="`tel:${schedule.patient.guardian.phone_number}`"
                                class="flex shrink-0 items-center gap-1 text-xs font-medium text-primary hover:underline"
                            >
                                <Phone class="h-3.5 w-3.5" />
                                {{ schedule.patient.guardian.phone_number }}
                            </a>
                        </div>
                    </div>

                    <div
                        v-if="isFacilitySchedule"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
                        :class="leftColClass"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 py-3 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300"
                                >
                                    <svg
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M3 21h18" />
                                        <path
                                            d="M5 21V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14"
                                        />
                                        <path d="M9 9h6" />
                                        <path d="M9 13h6" />
                                        <path d="M9 17h2" />
                                    </svg>
                                </div>

                                <div>
                                    <p
                                        class="text-sm font-semibold tracking-tight text-slate-800 dark:text-white"
                                    >
                                        Facility Admission
                                    </p>

                                    <p class="text-xs text-slate-400 dark:text-gray-500">
                                        Current room assignment
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="
                                    schedule.patient?.is_admitted
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300'
                                "
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="
                                        schedule.patient?.is_admitted
                                            ? 'bg-emerald-500'
                                            : 'bg-amber-500'
                                    "
                                />

                                {{
                                    schedule.patient?.is_admitted
                                        ? "Currently Admitted"
                                        : "Not Admitted"
                                }}
                            </span>
                        </div>

                        <div
                            v-if="
                                schedule.patient?.is_admitted &&
                                schedule.patient?.admission
                            "
                            class="p-4"
                        >
                            <div
                                class="mb-4 flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3 dark:bg-emerald-500/10"
                            >
                                <div>
                                    <p
                                        class="text-[11px] font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-300"
                                    >
                                        Assigned Accommodation
                                    </p>

                                    <p
                                        class="mt-0.5 text-sm font-semibold text-emerald-900 dark:text-emerald-300"
                                    >
                                        {{
                                            schedule.patient.admission.bed?.room
                                                ?.room_type ?? "—"
                                        }}

                                        <span class="mx-1 text-emerald-300"
                                            >•</span
                                        >

                                        Room
                                        {{
                                            schedule.patient.admission.bed?.room
                                                ?.room_no ?? "—"
                                        }}

                                        <span class="mx-1 text-emerald-300"
                                            >•</span
                                        >

                                        Bed
                                        {{
                                            schedule.patient.admission.bed
                                                ?.bed_no ?? "—"
                                        }}

                                        <span class="mx-1 text-emerald-300"
                                            >•</span
                                        >

                                        {{
                                            schedule.patient.admission.bed?.room
                                                ?.floor ?? "—"
                                        }}
                                        Floor
                                    </p>
                                </div>

                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm dark:bg-secondary dark:text-emerald-300"
                                >
                                    <svg
                                        width="17"
                                        height="17"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path d="M3 7v11" />
                                        <path
                                            d="M21 18v-7a2 2 0 0 0-2-2h-8a2 2 0 0 0-2 2v7"
                                        />
                                        <path d="M3 15h18" />
                                        <path d="M5 11h4" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex items-center gap-3 border-t border-slate-100 bg-amber-50/60 px-4 py-4 dark:border-white/10 dark:bg-amber-500/10"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300"
                            >
                                <svg
                                    width="17"
                                    height="17"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M12 9v4" />
                                    <path d="M12 17h.01" />
                                    <path
                                        d="M10.3 3.8 2.9 17a2 2 0 0 0 1.75 3h14.7a2 2 0 0 0 1.75-3l-7.4-13.2a2 2 0 0 0-3.4 0Z"
                                    />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-800 dark:text-white">
                                    Patient is not currently admitted
                                </p>

                                <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">
                                    No active facility admission or room
                                    assignment was found.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="space-y-4 rounded-xl border border-sky-100 bg-sky-50 p-4 dark:border-sky-500/20 dark:bg-sky-500/10"
                        :class="leftColClass"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300"
                            >
                                <svg
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M12 21s-7-5.686-7-11a7 7 0 1 1 14 0c0 5.314-7 11-7 11z"
                                    />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-xs font-medium uppercase tracking-wide text-sky-600 dark:text-sky-300"
                                >
                                    {{
                                        schedule.is_onsite
                                            ? "Location"
                                            : "Homecare Address"
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-800 dark:text-white"
                                >
                                    {{
                                        schedule.address ??
                                        schedule.patient?.address ??
                                        "No address on file"
                                    }}
                                </p>

                                <a
                                    v-if="hasCoordinates"
                                    :href="googleMapsUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-sky-600 hover:text-sky-700 hover:underline dark:text-sky-300 dark:hover:text-sky-300"
                                >
                                    Open in Google Maps

                                    <svg
                                        class="h-3 w-3"
                                        viewBox="0 0 20 20"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.75"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M8 4H4v12h12v-4" />
                                        <path d="M12 3h5v5M17 3l-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <!-- Coordinates only exist for homecare visits booked
                             through the map picker. -->
                        <ClientOnly v-if="hasCoordinates">
                            <LocationMap
                                :lat="schedule.latitude!"
                                :lng="schedule.longitude!"
                                :label="schedule.address ?? undefined"
                                height-class="h-[300px]"
                            />

                            <template #fallback>
                                <div
                                    class="h-[300px] w-full animate-pulse rounded-xl border border-sky-100 bg-sky-100/50 dark:border-sky-500/20 dark:bg-sky-500/15"
                                />
                            </template>
                        </ClientOnly>
                    </div>

                    <div
                        v-if="isEditing"
                        class="space-y-3"
                        :class="leftColClass"
                    >
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <BaseInput
                                :model-value="form.date"
                                label="Select Schedule Date"
                                mode="date"
                                :min="todayStr"
                                :error="errors.date"
                                required
                                @update:model-value="update('date', $event)"
                            />

                            <Combobox
                                :model-value="form.preferred_time"
                                :placeholder="displayTime"
                                label="Preferred Time"
                                :items="availableTimeSlots"
                                :error="errors.preferred_time"
                                required
                                @update:model-value="
                                    update('preferred_time', $event)
                                "
                            />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <Combobox
                                v-model="form.status"
                                label="Status"
                                placeholder="Select status"
                                :items="statusItems"
                            />
                        </div>
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 sm:grid-cols-3 gap-3"
                        :class="leftColClass"
                    >
                        <div class="rounded-xl border border-slate-100 p-3 dark:border-white/10">
                            <p class="text-xs text-slate-400 dark:text-gray-500">Date</p>
                            <p class="mt-1 text-sm font-medium text-slate-700 dark:text-gray-400">
                                {{ schedule.scheduled_date }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-100 p-3 dark:border-white/10">
                            <p class="text-xs text-slate-400 dark:text-gray-500">Time</p>
                            <p class="mt-1 text-sm font-medium text-slate-700 dark:text-gray-400">
                                {{ schedule.start_time }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-100 p-3 dark:border-white/10">
                            <p class="text-xs text-slate-400 dark:text-gray-500">
                                {{
                                    scheduleHasAdlService
                                        ? "Duration"
                                        : "Estimated Duration"
                                }}
                            </p>
                            <p class="mt-1 text-sm font-medium text-slate-700 dark:text-gray-400">
                                <template v-if="scheduleHasAdlService">
                                    <template v-if="schedule.total_hours">
                                        {{
                                            formatDuration(schedule.total_hours)
                                        }}
                                        <br />
                                        ({{ schedule.total_hours }} hrs)
                                    </template>
                                    <template v-else> — </template>
                                </template>

                                <template v-else>
                                    <template
                                        v-if="schedule.total_duration_minutes"
                                    >
                                        {{ schedule.total_duration_minutes }}
                                        min
                                    </template>
                                    <template v-else> — </template>
                                </template>
                            </p>

                            <!-- <p
                                            class="mt-0.5 text-xs text-slate-400 dark:text-gray-500"
                                        >
                                            {{ service.type }}
                                            •
                                            {{ durationLabelFor(service) }}
                                        </p> -->
                        </div>
                    </div>

                    <div :class="servicesColClass">
                        <p class="mb-2 text-sm font-semibold text-slate-800 dark:text-white">
                            Services
                        </p>

                        <div class="space-y-3">
                            <div
                                v-for="service in schedule.services"
                                :key="service.schedule_services_id"
                                class="rounded-xl border border-slate-100 p-3 dark:border-white/10"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-800 dark:text-white"
                                        >
                                            {{
                                                service.service_name ??
                                                "Activity of Daily Living (ADL)"
                                            }}
                                        </p>
                                    </div>

                                    <span
                                        v-if="!isEditing"
                                        class="rounded-full px-2 py-1 text-[11px] font-medium"
                                        :class="
                                            service.assignees?.length
                                                ? 'bg-primary/10 text-primary'
                                                : 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300'
                                        "
                                    >
                                        {{
                                            service.assignees?.length
                                                ? "Assigned"
                                                : "Needs Assignment"
                                        }}
                                    </span>
                                </div>

                                <div v-if="isEditing" class="mt-4 space-y-3">
                                    <div
                                        v-if="isFetchingEmployees"
                                        class="space-y-2"
                                    >
                                        <div
                                            v-for="i in 4"
                                            :key="i"
                                            class="h-[68px] animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
                                        />
                                    </div>

                                    <template v-else>
                                        <div
                                            class="flex flex-wrap items-center justify-between gap-2"
                                        >
                                            <p
                                                class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                            >
                                                Assign
                                                {{ requiredRoleLabel(service) }}
                                                <span class="text-slate-300 dark:text-gray-500">
                                                    ·
                                                </span>
                                                {{
                                                    entriesFor(
                                                        service.schedule_services_id,
                                                    ).length
                                                }}
                                                selected
                                            </p>

                                            <div
                                                class="relative w-full sm:w-64"
                                            >
                                                <Search
                                                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500"
                                                />

                                                <input
                                                    v-model="employeeSearch"
                                                    type="text"
                                                    placeholder="Search name"
                                                    class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-white/10 dark:bg-secondary dark:text-gray-400"
                                                />
                                            </div>
                                        </div>

                                        <div
                                            v-if="!eligibleFor(service).length"
                                            class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-xs text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
                                        >
                                            {{
                                                employeeSearch
                                                    ? "No staff match that search."
                                                    : `No ${requiredRoleLabel(service).toLowerCase()} is available for this branch.`
                                            }}
                                        </div>

                                        <div v-else class="space-y-2">
                                            <div
                                                v-for="employee in eligibleFor(
                                                    service,
                                                )"
                                                :key="employee.employee_id"
                                                class="rounded-xl border transition dark:border-white/10"
                                                :class="[
                                                    isAssigned(
                                                        service.schedule_services_id,
                                                        employee.employee_id,
                                                    )
                                                        ? 'border-primary/40 bg-primary/[0.04] ring-1 ring-primary/20'
                                                        : 'border-slate-200 bg-white hover:border-primary/30 dark:border-white/10 dark:bg-secondary',
                                                    isPickDisabled(
                                                        service,
                                                        employee,
                                                    )
                                                        ? 'opacity-60'
                                                        : '',
                                                ]"
                                            >
                                                <button
                                                    type="button"
                                                    :disabled="
                                                        isPickDisabled(
                                                            service,
                                                            employee,
                                                        )
                                                    "
                                                    class="flex w-full items-start gap-3 p-3 text-left disabled:cursor-not-allowed"
                                                    @click="
                                                        toggleAssignee(
                                                            service.schedule_services_id,
                                                            Number(
                                                                employee.employee_id,
                                                            ),
                                                        )
                                                    "
                                                >
                                                    <span
                                                        class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full text-[11px] font-semibold"
                                                        :class="
                                                            isAssigned(
                                                                service.schedule_services_id,
                                                                employee.employee_id,
                                                            )
                                                                ? 'bg-primary text-white'
                                                                : 'bg-primary/10 text-primary'
                                                        "
                                                    >
                                                        <img
                                                            v-if="
                                                                employee.avatar
                                                            "
                                                            :src="
                                                                employee.avatar
                                                            "
                                                            class="h-full w-full object-cover"
                                                        />

                                                        <template v-else>
                                                            {{
                                                                initials(
                                                                    employeeLabel(
                                                                        employee,
                                                                    ),
                                                                )
                                                            }}
                                                        </template>
                                                    </span>

                                                    <span
                                                        class="min-w-0 flex-1"
                                                    >
                                                        <span
                                                            class="flex items-center gap-1.5"
                                                        >
                                                            <span
                                                                class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                                            >
                                                                {{
                                                                    employeeLabel(
                                                                        employee,
                                                                    )
                                                                }}
                                                            </span>

                                                            <Check
                                                                v-if="
                                                                    isAssigned(
                                                                        service.schedule_services_id,
                                                                        employee.employee_id,
                                                                    )
                                                                "
                                                                class="h-3.5 w-3.5 shrink-0 text-primary"
                                                            />
                                                        </span>

                                                        <span
                                                            class="block truncate text-xs capitalize text-slate-500 dark:text-gray-400"
                                                        >
                                                            {{
                                                                employee.role_name ??
                                                                "Staff"
                                                            }}

                                                            <template
                                                                v-if="
                                                                    employee.formatted_assignment_type
                                                                "
                                                            >
                                                                ·
                                                                {{
                                                                    employee.formatted_assignment_type
                                                                }}
                                                            </template>
                                                        </span>

                                                        <span
                                                            v-if="
                                                                employee.phone_number
                                                            "
                                                            class="mt-0.5 flex items-center gap-1 text-[11px] text-slate-400 dark:text-gray-500"
                                                        >
                                                            <Phone
                                                                class="h-3 w-3 shrink-0"
                                                            />
                                                            {{
                                                                employee.phone_number
                                                            }}
                                                        </span>

                                                        <span
                                                            v-if="
                                                                employee.email
                                                            "
                                                            class="mt-0.5 flex min-w-0 items-center gap-1 text-[11px] text-slate-400 dark:text-gray-500"
                                                        >
                                                            <Mail
                                                                class="h-3 w-3 shrink-0"
                                                            />
                                                            <span
                                                                class="truncate"
                                                            >
                                                                {{
                                                                    employee.email
                                                                }}
                                                            </span>
                                                        </span>
                                                    </span>

                                                    <span
                                                        v-if="employee.is_busy"
                                                        class="shrink-0 rounded-full bg-amber-50 px-2 py-1 text-[11px] font-medium text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"
                                                    >
                                                        Conflict
                                                    </span>

                                                    <span
                                                        v-else-if="
                                                            !employee.is_assigned &&
                                                            service.type !==
                                                                'ADL'
                                                        "
                                                        class="flex shrink-0 items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-500 dark:bg-white/10 dark:text-gray-400"
                                                    >
                                                        <CircleHelp
                                                            class="h-3.5 w-3.5"
                                                        />
                                                        Not specialized
                                                    </span>

                                                    <span
                                                        v-else
                                                        class="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300"
                                                    >
                                                        Available
                                                    </span>
                                                </button>

                                                <div
                                                    v-if="
                                                        isAssigned(
                                                            service.schedule_services_id,
                                                            employee.employee_id,
                                                        )
                                                    "
                                                    class="border-t border-primary/15 px-3 py-3"
                                                >
                                                    <input
                                                        :value="
                                                            noteFor(
                                                                service.schedule_services_id,
                                                                Number(
                                                                    employee.employee_id,
                                                                ),
                                                            )
                                                        "
                                                        type="text"
                                                        maxlength="255"
                                                        placeholder="Note for this assignment"
                                                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 dark:border-white/10 dark:text-gray-400"
                                                        @input="
                                                            setNote(
                                                                service.schedule_services_id,
                                                                Number(
                                                                    employee.employee_id,
                                                                ),
                                                                (
                                                                    $event.target as HTMLInputElement
                                                                ).value,
                                                            )
                                                        "
                                                    />

                                                    <div
                                                        class="mt-2 flex flex-wrap items-center gap-1.5"
                                                    >
                                                        <span
                                                            class="text-[11px] text-slate-400 dark:text-gray-500"
                                                        >
                                                            Suggestions:
                                                        </span>

                                                        <button
                                                            v-for="preset in presetsFor(
                                                                service,
                                                            )"
                                                            :key="preset"
                                                            type="button"
                                                            class="rounded-full border px-2.5 py-0.5 text-[11px] font-medium transition dark:border-white/10"
                                                            :class="
                                                                noteFor(
                                                                    service.schedule_services_id,
                                                                    Number(
                                                                        employee.employee_id,
                                                                    ),
                                                                ) === preset
                                                                    ? 'border-primary bg-primary text-white'
                                                                    : 'border-slate-200 text-slate-500 hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-400'
                                                            "
                                                            @click="
                                                                togglePreset(
                                                                    service.schedule_services_id,
                                                                    Number(
                                                                        employee.employee_id,
                                                                    ),
                                                                    preset,
                                                                )
                                                            "
                                                        >
                                                            {{ preset }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div v-else class="mt-3">
                                    <p
                                        v-if="service.assignees"
                                        class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500"
                                    >
                                        Assigned Employee
                                        <span
                                            v-if="service.assignees?.length > 1"
                                        >
                                            s
                                        </span>
                                    </p>

                                    <div
                                        v-if="service.assignees?.length"
                                        class="mt-3 space-y-2"
                                    >
                                        <div
                                            v-for="employee in service.assignees"
                                            :key="employee.employee_id"
                                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-white/10 dark:bg-white/5"
                                        >
                                            <div
                                                class="flex min-w-0 items-center gap-3"
                                            >
                                                <span
                                                    class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary text-xs font-semibold text-white"
                                                >
                                                    <img
                                                        v-if="employee.avatar"
                                                        :src="employee.avatar"
                                                        class="h-full w-full object-cover"
                                                    />

                                                    <template v-else>
                                                        {{
                                                            initials(
                                                                employee.full_name,
                                                            )
                                                        }}
                                                    </template>
                                                </span>

                                                <div class="min-w-0">
                                                    <p
                                                        class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                                    >
                                                        {{ employee.full_name }}
                                                    </p>

                                                    <p
                                                        class="truncate text-xs capitalize text-slate-500 dark:text-gray-400"
                                                    >
                                                        {{
                                                            employee.employee_role ??
                                                            "Staff"
                                                        }}
                                                    </p>

                                                    <div
                                                        class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-[11px] text-slate-400 dark:text-gray-500"
                                                    >
                                                        <span
                                                            v-if="
                                                                employeeById(
                                                                    employee.employee_id,
                                                                )?.phone_number
                                                            "
                                                            class="flex items-center gap-1"
                                                        >
                                                            <Phone
                                                                class="h-3 w-3 shrink-0"
                                                            />
                                                            {{
                                                                employeeById(
                                                                    employee.employee_id,
                                                                )?.phone_number
                                                            }}
                                                        </span>

                                                        <span
                                                            v-if="
                                                                employeeById(
                                                                    employee.employee_id,
                                                                )?.email
                                                            "
                                                            class="flex min-w-0 items-center gap-1"
                                                        >
                                                            <Mail
                                                                class="h-3 w-3 shrink-0"
                                                            />
                                                            <span
                                                                class="truncate"
                                                            >
                                                                {{
                                                                    employeeById(
                                                                        employee.employee_id,
                                                                    )?.email
                                                                }}
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <span
                                                v-if="employee.note"
                                                class="ml-3 shrink-0 rounded-full bg-primary/10 px-2.5 py-1 text-[11px] font-medium text-primary"
                                            >
                                                {{ employee.note }}
                                            </span>
                                        </div>
                                    </div>

                                    <div
                                        v-else
                                        class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
                                    >
                                        No employee has been assigned yet.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex shrink-0 justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3 dark:border-white/10 dark:bg-white/5"
                >
                    <template v-if="isEditing">
                        <button
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-white/10 dark:bg-secondary dark:text-gray-400 dark:hover:bg-white/5"
                            @click="cancelEdit"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="submitLoading"
                            @click="handleSchedule"
                        >
                            <LoaderCircle
                                v-if="submitLoading"
                                class="h-4 w-4 animate-spin"
                            />

                            <CalendarCheck2 v-else class="h-4 w-4" />

                            {{
                                submitLoading
                                    ? "Scheduling..."
                                    : "Schedule Service"
                            }}
                        </button>
                    </template>

                    <template v-else>
                        <button
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-white/10 dark:bg-secondary dark:text-gray-400 dark:hover:bg-white/5"
                            @click="close"
                        >
                            Close
                        </button>

                        <button
                            v-if="schedule?.status !== 'cancelled'"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90"
                            @click="startEdit"
                        >
                            Update
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </Transition>

    <ConfirmDialog
        :open="cancelConfirmOpen"
        title="Cancel this schedule?"
        message="This action cannot be undone."
        description="The payment will be refunded if there is any, and the invoice will be voided."
        confirm-label="Cancel Schedule"
        cancel-label="Keep Schedule"
        variant="danger"
        :loading="submitLoading"
        @confirm="confirmCancelSchedule"
        @cancel="cancelConfirmOpen = false"
    />
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import ConfirmDialog from "~/components/ui/ConfirmDialog.vue";
import LocationMap from "~/components/ui/LocationMap.vue";
import {
    getLocalDateStr,
    generateAvailableAmPmTimes,
    formatDuration,
} from "~/utils/time";

function formatServiceDuration(minutes?: number | null): string {
    if (!minutes) return "0 hrs";

    const hours = Math.round((minutes / 60) * 100) / 100;
    const formatted = formatDuration(hours);

    return formatted ? `${formatted} (${hours} hrs)` : `${hours} hrs`;
}

function durationLabelFor(service: ScheduleServiceItem): string {
    if (service.type === "ADL")
        return formatServiceDuration(service.duration_minutes);

    return service.duration_minutes ? `${service.duration_minutes} min` : "—";
}
import { fullName, initials } from "~/utils/user";
import type { Employee } from "~/types/employee";
import type { ScheduleItem, ScheduleServiceItem } from "~/types/schedule";
import {
    CalendarCheck2,
    Check,
    CircleHelp,
    LoaderCircle,
    Mail,
    Phone,
    Search,
    Users,
} from "lucide-vue-next";
import { useSchedule } from "~/composables/useSchedule";
import { useToast } from "~/composables/useToast";
import type { AnyCaaRecord } from "node:dns";

const props = defineProps<{
    open: boolean;
    schedule: ScheduleItem | null;
    submitLoading?: boolean;
    employees?: Employee[];
    isFetchingEmployees?: boolean;
}>();

const emit = defineEmits<{
    (e: "close"): void;

    (
        e: "schedule",
        payload: {
            schedule_id: number;
            status: string;
            date: string;
            preferred_time: string;
            assignments: {
                employee_id: number | null;
                schedule_services_id: number;
            }[];
        },
    ): void;

    (e: "start-edit", schedule: ScheduleItem): void;
}>();

const route = useRoute();
const router = useRouter();
const { error: toastError } = useToast();

const patientUuid = computed(() => {
    const uuid = props.schedule?.patient?.patient_uuid;

    if (!uuid || uuid === route.params.p_uuid) return null;

    return uuid;
});

function viewPatient() {
    if (!patientUuid.value) return;

    router.push({
        path: `/app/branches/${route.params.uuid}/patients/${patientUuid.value}`,
    });

    close();
}

const hasCoordinates = computed(
    () =>
        typeof props.schedule?.latitude === "number" &&
        typeof props.schedule?.longitude === "number",
);

const googleMapsUrl = computed(() =>
    hasCoordinates.value
        ? `https://www.google.com/maps/search/?api=1&query=${props.schedule?.latitude},${props.schedule?.longitude}`
        : "",
);

const scheduleHasAdlService = computed(() =>
    (props.schedule?.services ?? []).some((s) => s.type === "ADL"),
);

const { scheduleStatusTheme, scheduleStatusLabel, statusItems } = useSchedule();

const isEditing = ref(false);

const todayStr = getLocalDateStr(new Date());

const form = ref({
    date: "",
    preferred_time: "",
    status: "",
});

interface AssignmentEntry {
    employee_id: number;
    note: string;
}

const assignments = ref<Record<number, AssignmentEntry[]>>({});

const NOTE_PRESETS_ADL = ["AM Shift", "PM Shift", "Full Shift"];
const NOTE_PRESETS_OTHER = [
    "Primary",
    "Assistance",
    "Supervisor",
    "Observer",
    "Stand-by",
];

function presetsFor(service: ScheduleServiceItem) {
    return service.type === "ADL" ? NOTE_PRESETS_ADL : NOTE_PRESETS_OTHER;
}

const errors = ref<Record<string, string>>({});

const isFacilitySchedule = computed(
    () => props.schedule?.category?.toLowerCase() === "facility",
);

const employeeSearch = ref("");

const bodyGridClass =
    "xl:grid xl:items-start xl:gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]";

const leftColClass = "xl:col-start-1";

const servicesColClass = "xl:col-start-2 xl:row-span-3 xl:row-start-1";

function employeeLabel(employee: Employee) {
    return (
        employee.full_name ||
        fullName(employee.first_name, "", employee.last_name)
    );
}

function employeeById(employeeId: string | number) {
    return (props.employees ?? []).find(
        (e) => Number(e.employee_id) === Number(employeeId),
    );
}

function requiredRoleFor(service: ScheduleServiceItem): string | null {
    if (service.type === "Medical") return "nurse";
    if (service.type === "ADL") return "caregiver";

    return null;
}

function requiredRoleLabel(service: ScheduleServiceItem) {
    const role = requiredRoleFor(service);

    return role ? role.charAt(0).toUpperCase() + role.slice(1) : "Staff";
}

function eligibleFor(service: ScheduleServiceItem) {
    const required = requiredRoleFor(service);
    const term = employeeSearch.value.trim().toLowerCase();

    return (props.employees ?? []).filter((employee) => {
        if (required && (employee.role_name ?? "").toLowerCase() !== required) {
            if (
                !isAssigned(service.schedule_services_id, employee.employee_id)
            ) {
                return false;
            }
        }

        if (!term) return true;

        return employeeLabel(employee).toLowerCase().includes(term);
    });
}

function entriesFor(serviceId: number): AssignmentEntry[] {
    return assignments.value[serviceId] ?? [];
}

function isAssigned(serviceId: number, employeeId: string | number) {
    return entriesFor(serviceId).some(
        (entry) => Number(entry.employee_id) === Number(employeeId),
    );
}

function isPickDisabled(service: ScheduleServiceItem, employee: Employee) {
    if (isAssigned(service.schedule_services_id, employee.employee_id)) {
        return false;
    }

    if (employee.is_busy) return true;

    return service.type !== "ADL" && !employee.is_assigned;
}

function noteFor(serviceId: number, employeeId: number) {
    return (
        entriesFor(serviceId).find(
            (entry) => Number(entry.employee_id) === Number(employeeId),
        )?.note ?? ""
    );
}

function isEmployeeClockedIn(
    serviceId: number,
    employeeId: string | number | null | undefined,
) {
    if (!employeeId) return false;

    const service = props.schedule?.services?.find(
        (s) => s.schedule_services_id === serviceId,
    );

    const assignee = service?.assignees?.find(
        (a) => Number(a.employee_id) === Number(employeeId),
    );

    return (assignee?.online ?? []).some(
        (log) => log.in_timestamp && !log.out_timestamp,
    );
}

function toggleAssignee(serviceId: number, employeeId: number) {
    const current = entriesFor(serviceId);

    const index = current.findIndex(
        (entry) => Number(entry.employee_id) === Number(employeeId),
    );

    if (index !== -1 && isEmployeeClockedIn(serviceId, employeeId)) {
        toastError(
            "This staff member is currently clocked in and can't be unassigned.",
        );
        return;
    }

    assignments.value = {
        ...assignments.value,
        [serviceId]:
            index === -1
                ? [...current, { employee_id: employeeId, note: "" }]
                : current.filter((_, i) => i !== index),
    };
}

function setNote(serviceId: number, employeeId: number, note: string) {
    assignments.value = {
        ...assignments.value,
        [serviceId]: entriesFor(serviceId).map((entry) =>
            Number(entry.employee_id) === Number(employeeId)
                ? { ...entry, note }
                : entry,
        ),
    };
}

function togglePreset(serviceId: number, employeeId: number, preset: string) {
    const current = noteFor(serviceId, employeeId);

    setNote(serviceId, employeeId, current === preset ? "" : preset);
}

const availableTimeSlots = computed(() =>
    generateAvailableAmPmTimes(form.value.date),
);

const displayTime = computed(() =>
    availableTimeSlots.value.length ? "Select time" : "No available time slots",
);

watch(
    () => props.schedule,
    (schedule) => {
        resetForm();

        if (!schedule) return;

        form.value = {
            date: schedule.scheduled_date ?? "",
            preferred_time: schedule.start_time ?? "",
            status: schedule.status ?? "Pending",
        };

        hydrateAssignments(schedule);
    },
    {
        immediate: true,
    },
);

function hydrateAssignments(schedule?: ScheduleItem | null) {
    assignments.value = {};

    schedule?.services?.forEach((service) => {
        assignments.value[service.schedule_services_id] = (
            service.assignees ?? []
        )
            .filter((assignee) => assignee.is_active !== false)
            .map((assignee) => ({
                employee_id: Number(assignee.employee_id),
                note: assignee.note ?? "",
            }));
    });
}

function resetForm() {
    isEditing.value = false;
    assignments.value = {};
    errors.value = {};
}

function startEdit() {
    if (!props.schedule) return;
    if (props.schedule.status === "cancelled") return;

    isEditing.value = true;

    form.value = {
        date: props.schedule.scheduled_date ?? "",
        preferred_time: props.schedule.start_time ?? "",
        status: props.schedule.status ?? "Pending",
    };

    hydrateAssignments(props.schedule);

    emit("start-edit", props.schedule);
}

function cancelEdit() {
    resetForm();
}

function update(key: keyof typeof form.value, value: string) {
    form.value[key] = value;
    delete errors.value[key];
}

function validate() {
    errors.value = {};

    if (!form.value.date) {
        errors.value.date = "Please select a schedule date.";
    }

    if (!form.value.preferred_time) {
        errors.value.preferred_time = "Please select a preferred time.";
    }

    return !Object.keys(errors.value).length;
}

const cancelConfirmOpen = ref(false);

function buildSchedulePayload() {
    if (!props.schedule) return null;

    return {
        schedule_id: props.schedule.schedule_id,
        status: form.value.status,
        date: form.value.date,
        preferred_time: form.value.preferred_time,
        assignments: (props.schedule.services ?? []).flatMap(
            (
                service,
            ): {
                schedule_services_id: number;
                employee_id: number | null;
                note: string | null;
            }[] => {
                const entries = entriesFor(service.schedule_services_id);

                if (!entries.length) {
                    return [
                        {
                            schedule_services_id: service.schedule_services_id,
                            employee_id: null,
                            note: null,
                        },
                    ];
                }

                return entries.map((entry) => ({
                    schedule_services_id: service.schedule_services_id,
                    employee_id: Number(entry.employee_id),
                    note: entry.note.trim() || null,
                }));
            },
        ),
    };
}

function handleSchedule() {
    if (!props.schedule) return;

    if (!validate()) return;

    if (form.value.status === "cancelled") {
        cancelConfirmOpen.value = true;
        return;
    }

    const payload = buildSchedulePayload();

    if (payload) {
        emit("schedule", payload);
    }
}

function confirmCancelSchedule() {
    cancelConfirmOpen.value = false;

    const payload = buildSchedulePayload();

    if (payload) {
        emit("schedule", payload);
    }
}

function close() {
    resetForm();
    emit("close");
}
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.95) translateY(10px);
}

.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}
</style>
