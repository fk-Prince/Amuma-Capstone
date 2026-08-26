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
                class="relative flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-4"
                >
                    <div class="min-w-0">
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-slate-400"
                        >
                            Schedule Details
                        </p>

                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold text-slate-800">
                                {{ schedule.schedule_code }}
                            </h2>

                            <span
                                class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium capitalize text-slate-600"
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
                        class="flex items-center gap-3 rounded-xl bg-slate-50 p-3"
                        :class="leftColClass"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary"
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
                            <p class="text-xs text-slate-400">Patient</p>
                            <p class="truncate font-semibold text-slate-800">
                                {{ schedule.patient?.full_name ?? "—" }}
                            </p>
                        </div>

                        <button
                            v-if="patientUuid"
                            type="button"
                            class="flex shrink-0 items-center gap-1.5 rounded-lg border border-primary/20 bg-white px-3 py-1.5 text-xs font-medium text-primary transition hover:bg-primary/5"
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
                        v-if="isFacilitySchedule"
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
                        :class="leftColClass"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 py-3"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600"
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
                                        class="text-sm font-semibold tracking-tight text-slate-800"
                                    >
                                        Facility Admission
                                    </p>

                                    <p class="text-xs text-slate-400">
                                        Current room assignment
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                :class="
                                    schedule.patient?.is_admitted
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-amber-100 text-amber-700'
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
                                class="mb-4 flex items-center justify-between rounded-xl bg-emerald-50 px-4 py-3"
                            >
                                <div>
                                    <p
                                        class="text-[11px] font-medium uppercase tracking-wider text-emerald-600"
                                    >
                                        Assigned Accommodation
                                    </p>

                                    <p
                                        class="mt-0.5 text-sm font-semibold text-emerald-900"
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
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm"
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
                            class="flex items-center gap-3 border-t border-slate-100 bg-amber-50/60 px-4 py-4"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600"
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
                                <p class="text-sm font-semibold text-slate-800">
                                    Patient is not currently admitted
                                </p>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    No active facility admission or room
                                    assignment was found.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="space-y-4 rounded-xl border border-sky-100 bg-sky-50 p-4"
                        :class="leftColClass"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-600"
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
                                    class="text-xs font-medium uppercase tracking-wide text-sky-600"
                                >
                                    {{
                                        schedule.is_onsite
                                            ? "Location"
                                            : "Homecare Address"
                                    }}
                                </p>

                                <p
                                    class="mt-1 text-sm font-semibold text-slate-800"
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
                                    class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-sky-600 hover:text-sky-700 hover:underline"
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
                                    class="h-[300px] w-full animate-pulse rounded-xl border border-sky-100 bg-sky-100/50"
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
                        <div class="rounded-xl border border-slate-100 p-3">
                            <p class="text-xs text-slate-400">Date</p>
                            <p class="mt-1 text-sm font-medium text-slate-700">
                                {{ schedule.scheduled_date }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-slate-100 p-3">
                            <p class="text-xs text-slate-400">Time</p>
                            <p class="mt-1 text-sm font-medium text-slate-700">
                                {{ schedule.start_time }}
                                <!-- -
                                {{ schedule.end_time }} -->
                            </p>
                        </div>

                        <!-- Category already sits in the header, so this slot
                             carries the duration instead. -->
                        <div class="rounded-xl border border-slate-100 p-3">
                            <p class="text-xs text-slate-400">Duration</p>
                            <p class="mt-1 text-sm font-medium text-slate-700">
                                {{
                                    schedule.total_hours
                                        ? `${schedule.total_hours} hrs`
                                        : "—"
                                }}
                            </p>
                        </div>
                    </div>

                    <div :class="servicesColClass">
                        <p class="mb-2 text-sm font-semibold text-slate-800">
                            Services
                        </p>

                        <!-- Full width, not a two-column grid: a single service
                             would otherwise sit in a half-empty row. -->
                        <div class="space-y-3">
                            <div
                                v-for="service in schedule.services"
                                :key="service.schedule_services_id"
                                class="rounded-xl border border-slate-100 p-3"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <!-- ADL bookings carry no service row,
                                             so the card would be headless
                                             without a fallback name. -->
                                        <p
                                            class="text-sm font-semibold text-slate-800"
                                        >
                                            {{
                                                service.service_name ??
                                                "Activity of Daily Living"
                                            }}
                                        </p>

                                        <p
                                            class="mt-0.5 text-xs text-slate-400"
                                        >
                                            {{ service.type }}
                                            •
                                            {{ service.duration_minutes }} mins
                                        </p>
                                    </div>

                                    <span
                                        v-if="!isEditing"
                                        class="rounded-full px-2 py-1 text-[11px] font-medium"
                                        :class="
                                            service.assignees?.length
                                                ? 'bg-primary/10 text-primary'
                                                : 'bg-rose-100 text-rose-600'
                                        "
                                    >
                                        {{
                                            service.assignees?.length
                                                ? "Assigned"
                                                : "Needs Assignment"
                                        }}
                                    </span>
                                </div>

                                <!-- Same picker as the assign modal: a plain
                                     search over a multi-select list, two
                                     columns once there is room, and only the
                                     role the service actually allows. -->
                                <div v-if="isEditing" class="mt-4 space-y-3">
                                    <div
                                        v-if="isFetchingEmployees"
                                        class="space-y-2"
                                    >
                                        <div
                                            v-for="i in 4"
                                            :key="i"
                                            class="h-[68px] animate-pulse rounded-xl bg-slate-100"
                                        />
                                    </div>

                                    <template v-else>
                                        <div
                                            class="flex flex-wrap items-center justify-between gap-2"
                                        >
                                            <p
                                                class="text-xs font-semibold uppercase tracking-wide text-slate-400"
                                            >
                                                Assign
                                                {{ requiredRoleLabel(service) }}
                                                <span class="text-slate-300">
                                                    ·
                                                </span>
                                                {{
                                                    entriesFor(
                                                        service.schedule_services_id,
                                                    ).length
                                                }}
                                                selected
                                            </p>

                                            <div class="relative w-full sm:w-64">
                                                <Search
                                                    class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                                />

                                                <input
                                                    v-model="employeeSearch"
                                                    type="text"
                                                    placeholder="Search name"
                                                    class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                />
                                            </div>
                                        </div>

                                        <div
                                            v-if="!eligibleFor(service).length"
                                            class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-center text-xs text-slate-500"
                                        >
                                            {{
                                                employeeSearch
                                                    ? "No staff match that search."
                                                    : `No ${requiredRoleLabel(service).toLowerCase()} is available for this branch.`
                                            }}
                                        </div>

                                        <div
                                            v-else
                                            class="space-y-2"
                                        >
                                            <div
                                                v-for="employee in eligibleFor(
                                                    service,
                                                )"
                                                :key="employee.employee_id"
                                                class="rounded-xl border transition"
                                                :class="[
                                                    isAssigned(
                                                        service.schedule_services_id,
                                                        employee.employee_id,
                                                    )
                                                        ? 'border-primary/40 bg-primary/[0.04] ring-1 ring-primary/20'
                                                        : 'border-slate-200 bg-white hover:border-primary/30',
                                                    employee.is_busy
                                                        ? 'opacity-60'
                                                        : '',
                                                ]"
                                            >
                                                <button
                                                    type="button"
                                                    :disabled="employee.is_busy"
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
                                                                class="truncate text-sm font-semibold text-slate-800"
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
                                                            class="block truncate text-xs capitalize text-slate-500"
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
                                                            class="mt-0.5 flex items-center gap-1 text-[11px] text-slate-400"
                                                        >
                                                            <Phone
                                                                class="h-3 w-3 shrink-0"
                                                            />
                                                            {{
                                                                employee.phone_number
                                                            }}
                                                        </span>
                                                    </span>

                                                    <span
                                                        v-if="employee.is_busy"
                                                        class="shrink-0 rounded-full bg-amber-50 px-2 py-1 text-[11px] font-medium text-amber-700"
                                                    >
                                                        Conflict
                                                    </span>

                                                    <span
                                                        v-else-if="
                                                            !employee.is_assigned
                                                        "
                                                        class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-500"
                                                    >
                                                        Not specialized
                                                    </span>

                                                    <span
                                                        v-else
                                                        class="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-medium text-emerald-700"
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
                                                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
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
                                                        <button
                                                            v-for="preset in NOTE_PRESETS"
                                                            :key="preset"
                                                            type="button"
                                                            class="rounded-full border px-2.5 py-0.5 text-[11px] font-medium transition"
                                                            :class="
                                                                noteFor(
                                                                    service.schedule_services_id,
                                                                    Number(
                                                                        employee.employee_id,
                                                                    ),
                                                                ) === preset
                                                                    ? 'border-primary bg-primary text-white'
                                                                    : 'border-slate-200 text-slate-500 hover:border-primary/40 hover:text-primary'
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
                                        class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400"
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
                                            class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
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
                                                        class="truncate text-sm font-semibold text-slate-800"
                                                    >
                                                        {{ employee.full_name }}
                                                    </p>

                                                    <p
                                                        class="truncate text-xs capitalize text-slate-500"
                                                    >
                                                        {{
                                                            employee.employee_role ??
                                                            "Staff"
                                                        }}
                                                    </p>
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
                                        class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500"
                                    >
                                        No employee has been assigned yet.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex shrink-0 justify-end gap-2 border-t border-slate-100 bg-slate-50 px-6 py-3"
                >
                    <template v-if="isEditing">
                        <button
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
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
                            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
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
import { getLocalDateStr } from "~/utils/time";
import { generateAvailableAmPmTimes } from "~/utils/time-slot";
import { fullName, initials } from "~/utils/user";
import type { Employee } from "~/types/employee";
import type { ScheduleItem, ScheduleServiceItem } from "~/types/schedule";
import {
    CalendarCheck2,
    Check,
    LoaderCircle,
    Phone,
    Search,
} from "lucide-vue-next";
import { useSchedule } from "~/composables/useSchedule";

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

// Hidden when the modal is already open on that patient's own page.
const patientUuid = computed(() => {
    const uuid = props.schedule?.patient?.patient_uuid;

    if (!uuid || uuid === route.params.p_uuid) return null;

    return uuid;
});

/**
 * The schedule modal is opened from both the branch schedules board and the
 * patient page, so the branch uuid comes from the route rather than the
 * schedule payload.
 */
function viewPatient() {
    if (!patientUuid.value) return;

    router.push({
        path: `/app/branches/${route.params.uuid}/patients/${patientUuid.value}`,
    });

    close();
}

/**
 * Only homecare visits booked through the map picker carry coordinates, so
 * the map and the Google Maps link are both gated on them.
 */
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

/** schedule_services_id → the people assigned to it, each with its own note. */
const assignments = ref<Record<number, AssignmentEntry[]>>({});

const NOTE_PRESETS = [
    "Primary",
    "Assistance",
    "Supervisor",
    "Observer",
    "Stand-by",
];

const errors = ref<Record<string, string>>({});

const isFacilitySchedule = computed(
    () => props.schedule?.category?.toLowerCase() === "facility",
);

const employeeSearch = ref("");

/**
 * Two columns from xl up, in both the read-only and the editing view. The left
 * column stacks patient, location and the schedule details/form; services take
 * the right column and span all of those rows. `xl:grid` rather than `grid`
 * so the plain `space-y-4` stack still applies on narrower screens.
 */
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

/**
 * A Medical service can only be staffed by a nurse and an ADL service only by
 * a caregiver — the backend rejects anything else outright, so the picker only
 * lists the role that can actually be saved.
 */
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
        if (
            required &&
            (employee.role_name ?? "").toLowerCase() !== required
        ) {
            // Someone already assigned before the rule tightened stays
            // visible, otherwise they could never be removed.
            if (!isAssigned(service.schedule_services_id, employee.employee_id)) {
                return false;
            }
        }

        if (!term) return true;

        // Name only — matching on role or email surfaces people the typist
        // wasn't looking for.
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

function noteFor(serviceId: number, employeeId: number) {
    return (
        entriesFor(serviceId).find(
            (entry) => Number(entry.employee_id) === Number(employeeId),
        )?.note ?? ""
    );
}

function toggleAssignee(serviceId: number, employeeId: number) {
    const current = entriesFor(serviceId);

    const index = current.findIndex(
        (entry) => Number(entry.employee_id) === Number(employeeId),
    );

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

/** Seeds the editor from every active assignee, notes included. */
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
        // One row per assigned person, so a service can carry several. A
        // service left empty still sends a null row, which is how the backend
        // learns to unassign everyone from it.
        assignments: (props.schedule.services ?? []).flatMap((service) => {
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
        }),
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
