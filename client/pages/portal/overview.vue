<template>
    <div
        v-if="isLoading"
        class="grid gap-5 items-start p-4 sm:p-6 lg:p-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-3"
    >
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="h-4 w-24 bg-gray-200 rounded animate-pulse" />
                <div class="flex items-center gap-1">
                    <div
                        class="w-6 h-6 rounded-full bg-gray-200 animate-pulse"
                    />
                    <div
                        class="w-6 h-6 rounded-full bg-gray-200 animate-pulse"
                    />
                </div>
            </div>

            <div class="flex flex-col items-center text-center px-1">
                <div
                    class="w-20 h-20 rounded-full bg-gray-200 animate-pulse mb-3"
                />
                <div class="h-4 w-32 bg-gray-200 rounded animate-pulse mb-2" />
                <div class="h-3 w-24 bg-gray-200 rounded animate-pulse mb-2" />
                <div class="h-3 w-28 bg-gray-200 rounded animate-pulse mb-3" />
                <div
                    class="h-5 w-16 bg-gray-200 rounded-full animate-pulse mb-4"
                />
                <div
                    class="h-9 w-full bg-gray-200 rounded-full animate-pulse"
                />
            </div>

            <div class="flex items-center justify-center gap-1.5 mt-4">
                <div class="w-5 h-1.5 rounded-full bg-gray-200 animate-pulse" />
                <div
                    class="w-1.5 h-1.5 rounded-full bg-gray-200 animate-pulse"
                />
                <div
                    class="w-1.5 h-1.5 rounded-full bg-gray-200 animate-pulse"
                />
            </div>
        </div>

        <div
            class="md:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
        >
            <div class="flex items-center justify-between mb-3">
                <div class="h-4 w-28 bg-gray-200 rounded animate-pulse" />
                <div class="h-4 w-20 bg-gray-200 rounded animate-pulse" />
            </div>
            <div
                class="w-full h-[280px] rounded-xl bg-gray-200 animate-pulse"
            />
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="h-4 w-28 bg-gray-200 rounded animate-pulse mb-4" />
            <div class="space-y-3">
                <div
                    v-for="n in 4"
                    :key="n"
                    class="flex items-center justify-between rounded-xl px-3 py-2.5"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-gray-200 animate-pulse"
                        />
                        <div>
                            <div
                                class="h-3.5 w-32 bg-gray-200 rounded animate-pulse mb-1.5"
                            />
                            <div
                                class="h-3 w-20 bg-gray-200 rounded animate-pulse"
                            />
                        </div>
                    </div>
                    <div class="h-3 w-12 bg-gray-200 rounded animate-pulse" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="h-4 w-32 bg-gray-200 rounded animate-pulse mb-4" />
            <div class="flex gap-5 items-stretch w-full">
                <div
                    class="flex-1 h-[68px] rounded-xl bg-gray-200 animate-pulse"
                />
                <div
                    class="flex-1 h-[68px] rounded-xl bg-gray-200 animate-pulse"
                />
            </div>
            <div class="mt-4 space-y-2">
                <div class="h-3 w-20 bg-gray-200 rounded animate-pulse" />
                <div class="h-3.5 w-28 bg-gray-200 rounded animate-pulse" />
                <div class="h-3 w-16 bg-gray-200 rounded animate-pulse mt-2" />
                <div class="h-3.5 w-20 bg-gray-200 rounded animate-pulse" />
            </div>
            <div
                class="h-9 w-full bg-gray-200 rounded-full animate-pulse mt-4"
            />
        </div>

        <div
            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 h-full flex flex-col"
        >
            <div class="flex items-center justify-between mb-4">
                <div class="h-4 w-36 bg-gray-200 rounded animate-pulse" />
                <div class="h-3 w-12 bg-gray-200 rounded animate-pulse" />
            </div>
            <div class="space-y-3">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-gray-200 animate-pulse"
                        />
                        <div>
                            <div
                                class="h-3.5 w-28 bg-gray-200 rounded animate-pulse mb-1.5"
                            />
                            <div
                                class="h-3 w-16 bg-gray-200 rounded animate-pulse"
                            />
                        </div>
                    </div>
                    <div class="h-3.5 w-14 bg-gray-200 rounded animate-pulse" />
                </div>
            </div>
            <div
                class="h-3 w-32 bg-gray-200 rounded animate-pulse mt-auto pt-4"
            />
        </div>
    </div>

    <div v-else-if="noPatients" class="p-4 sm:p-6 lg:p-8">
        <EmptyState
            title="You currently have no patients"
            cta-label="Book a Service"
            cta-to="/booking/search"
        />
    </div>

    <div v-else-if="loadError" class="p-8">
        <div
            class="bg-white dark:bg-secondary rounded-2xl border border-gray-100 dark:border-white/10 shadow-sm p-8 text-center"
        >
            <p class="text-rose-500 text-sm">
                {{ loadError }}
            </p>
            <button
                @click="loadPatientData"
                class="mt-2 text-xs text-primary-600 font-medium"
            >
                Retry
            </button>
        </div>
    </div>

    <div
        v-else
        class="grid gap-5 items-start p-4 sm:p-6 lg:p-8 grid-cols-1 md:grid-cols-2 lg:grid-cols-3"
    >
        <div
            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 relative overflow-hidden"
        >
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold text-gray-800">My Loved Ones</p>

                <div
                    v-if="lovedOnes.length > 1"
                    class="flex items-center gap-1"
                >
                    <button
                        @click="prevLovedOne"
                        class="w-6 h-6 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-50 hover:text-gray-600"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>

                    <button
                        @click="nextLovedOne"
                        class="w-6 h-6 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-50 hover:text-gray-600"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <div v-if="lovedOnes.length" class="overflow-hidden">
                <div
                    class="flex transition-transform duration-300 ease-in-out"
                    :style="{
                        transform: `translateX(-${selectedIndex * 100}%)`,
                    }"
                >
                    <div
                        v-for="lo in lovedOnes"
                        :key="lo.patient_id"
                        class="w-full shrink-0 flex flex-col items-center text-center px-1"
                    >
                        <img
                            :src="lo.photo"
                            class="w-20 h-20 rounded-full object-cover object-top mb-3"
                            alt=""
                        />

                        <p class="font-semibold text-gray-900">
                            {{ lo.full_name }}
                        </p>

                        <p class="text-xs font-medium text-primary-600 mt-1">
                            {{ lo.branch_name }}
                        </p>

                        <p class="text-xs text-gray-400">
                            {{ lo.location_name }}
                        </p>

                        <span
                            v-if="lo.location_type === 'facility'"
                            class="mt-2 px-2.5 py-0.5 rounded-full text-[11px] font-medium"
                            :class="statusStyle(lo.status).badge"
                        >
                            {{
                                lo.status.toLowerCase() === "waiting"
                                    ? "Waiting to Admit"
                                    : lo.status
                            }}
                        </span>

                        <span
                            v-else
                            class="mt-2 px-2.5 py-0.5 rounded-full text-[11px] font-medium"
                            :class="statusStyle('homecare').badge"
                        >
                            Homecare
                        </span>

                        <NuxtLink
                            to="/portal/loved-ones"
                            class="mt-4 w-full border border-primary-600 text-primary-600 text-sm font-medium py-2 rounded-full transition-colors hover:bg-primary-500 hover:text-white hover:border-primary-500 text-center"
                        >
                            View Full Profile
                        </NuxtLink>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-8">
                <p class="text-gray-500">You currently have no patients.</p>
            </div>

            <div
                v-if="lovedOnes.length > 1"
                class="flex items-center justify-center gap-1.5 mt-4"
            >
                <button
                    v-for="(lo, idx) in lovedOnes"
                    :key="lo.patient_id"
                    @click="selectedIndex = idx"
                    class="h-1.5 rounded-full transition-all"
                    :class="
                        selectedIndex === idx
                            ? 'w-5 bg-primary-500'
                            : 'w-1.5 bg-gray-200 hover:bg-gray-300'
                    "
                />
            </div>
        </div>

        <div
            v-if="isAdmission && primaryLovedOne"
            class="md:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
        >
            <div class="flex items-center justify-between mb-3">
                <p
                    class="text-sm font-semibold text-gray-800 flex items-center gap-1.5"
                >
                    <Camera class="w-4 h-4 text-gray-500" />
                    Live Camera
                </p>

                <div class="flex items-center gap-2 text-xs">
                    <span class="text-gray-400">
                        {{ primaryLovedOne.room_label }}
                    </span>

                    <span
                        class="flex items-center gap-1 text-primary-600 font-medium"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-500" />
                        Live
                    </span>
                </div>
            </div>

            <div
                class="relative rounded-xl overflow-hidden bg-gray-900 w-full h-[280px]"
            >
                <img
                    :src="roomFeedPhoto"
                    class="w-full h-full object-cover"
                    alt="Live room feed"
                />

                <div
                    class="absolute top-3 left-3 bg-black/50 text-white text-[11px] px-2 py-1 rounded"
                >
                    {{
                        now.toLocaleDateString("en-US", {
                            month: "long",
                            day: "2-digit",
                            year: "numeric",
                        })
                    }}
                    · {{ formattedTime }}
                </div>

                <div
                    class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent px-4 py-3 flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <button
                            @click="cameraPlaying = !cameraPlaying"
                            class="text-white"
                        >
                            <component
                                :is="cameraPlaying ? Pause : Play"
                                class="w-4 h-4"
                            />
                        </button>

                        <button class="text-white/80 hover:text-white">
                            <SkipBack class="w-4 h-4" />
                        </button>

                        <button class="text-white/80 hover:text-white">
                            <SkipForward class="w-4 h-4" />
                        </button>

                        <button
                            @click="cameraMuted = !cameraMuted"
                            class="text-white"
                        >
                            <component
                                :is="cameraMuted ? VolumeX : Volume2"
                                class="w-4 h-4"
                            />
                        </button>
                    </div>

                    <button class="text-white/80 hover:text-white">
                        <Maximize class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>

        <div
            v-else-if="
                isHomecare && primaryLovedOne && primaryLovedOne.schedule.adl
            "
            class="md:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 h-full flex flex-col"
        >
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p
                        class="text-sm font-semibold text-gray-800 flex items-center gap-1.5"
                    >
                        <Home class="w-4 h-4 text-gray-500" />
                        Homecare Attendance
                    </p>

                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ adlVisitSubtitle }}
                    </p>
                </div>

                <span
                    class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium shrink-0"
                    :class="
                        scheduleStatusTheme(primaryLovedOne.schedule.adl.status)
                            .badge
                    "
                >
                    <span
                        v-if="primaryLovedOne.schedule.adl.status === 'ongoing'"
                        class="w-1.5 h-1.5 rounded-full bg-current"
                    />
                    {{
                        scheduleStatusLabel(primaryLovedOne.schedule.adl.status)
                    }}
                </span>
            </div>

            <div
                v-if="adlServices.length"
                class="flex-1 overflow-y-auto grid grid-cols-1 gap-3 content-start mb-5"
            >
                <div
                    v-for="service in adlServices"
                    :key="service.schedule_services_id"
                    class="rounded-xl bg-gray-50 p-3.5"
                >
                    <div class="flex items-center gap-3 mb-3.5">
                        <template v-if="assigneesOf(service).length <= 1">
                            <img
                                v-if="primaryAssignee(service)?.avatar"
                                :src="
                                    primaryAssignee(service)?.avatar ??
                                    undefined
                                "
                                class="w-9 h-9 rounded-full object-cover shrink-0"
                                alt=""
                            />

                            <span
                                v-else
                                class="w-9 h-9 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 text-xs font-semibold shrink-0"
                            >
                                <UserRound
                                    v-if="!primaryAssignee(service)"
                                    class="w-4 h-4"
                                />
                                <template v-else>
                                    {{
                                        initials(
                                            primaryAssignee(service)?.full_name,
                                        )
                                    }}
                                </template>
                            </span>

                            <div class="min-w-0">
                                <p
                                    class="text-sm font-medium text-gray-800 truncate"
                                >
                                    {{
                                        primaryAssignee(service)?.full_name ||
                                        "Unassigned"
                                    }}
                                </p>

                                <p class="text-xs text-gray-400 truncate">
                                    {{ service.service_name || "ADL Service" }}
                                </p>
                            </div>
                        </template>

                        <template v-else>
                            <div class="flex -space-x-2 shrink-0">
                                <template
                                    v-for="(assignee, idx) in assigneesOf(
                                        service,
                                    ).slice(0, 3)"
                                    :key="idx"
                                >
                                    <img
                                        v-if="assignee.avatar"
                                        :src="assignee.avatar"
                                        class="w-9 h-9 rounded-full object-cover ring-2 ring-gray-50"
                                        alt=""
                                    />

                                    <span
                                        v-else
                                        class="w-9 h-9 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 text-[10px] font-semibold ring-2 ring-gray-50"
                                    >
                                        {{ initials(assignee.full_name) }}
                                    </span>
                                </template>

                                <span
                                    v-if="assigneesOf(service).length > 3"
                                    class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 text-[10px] font-semibold ring-2 ring-gray-50"
                                >
                                    +{{ assigneesOf(service).length - 3 }}
                                </span>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-sm font-medium text-gray-800 truncate"
                                >
                                    {{
                                        primaryAssignee(service)?.full_name ||
                                        "Unassigned"
                                    }}
                                    <span class="text-gray-400 font-normal">
                                        +{{ assigneesOf(service).length - 1 }}
                                        more
                                    </span>
                                </p>

                                <p class="text-xs text-gray-400 truncate">
                                    {{ service.service_name || "ADL Service" }}
                                </p>
                            </div>
                        </template>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div
                            class="flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-2"
                        >
                            <span
                                class="w-6 h-6 rounded-md bg-gray-50 flex items-center justify-center text-gray-400 shrink-0"
                            >
                                <Clock class="w-3 h-3" />
                            </span>
                            <div class="min-w-0">
                                <p
                                    class="text-[10px] uppercase text-gray-400 truncate"
                                >
                                    Scheduled
                                </p>
                                <p
                                    class="text-xs font-semibold text-gray-800 truncate"
                                >
                                    {{
                                        formatDuration(
                                            (service.duration_minutes ?? 0) /
                                                60,
                                        ) || "0 hrs"
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-2"
                        >
                            <span
                                class="w-6 h-6 rounded-md bg-gray-50 flex items-center justify-center text-gray-400 shrink-0"
                            >
                                <Timer class="w-3 h-3" />
                            </span>
                            <div class="min-w-0">
                                <p
                                    class="text-[10px] uppercase text-gray-400 truncate"
                                >
                                    Worked
                                </p>
                                <p
                                    class="text-xs font-semibold text-gray-800 truncate"
                                >
                                    {{
                                        formatDuration(
                                            serviceWorkedMinutesCapped(
                                                service,
                                            ) / 60,
                                        ) || "0 hrs"
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-1.5 rounded-lg bg-white px-2.5 py-2"
                        >
                            <span
                                class="w-6 h-6 rounded-md bg-gray-50 flex items-center justify-center text-gray-400 shrink-0"
                            >
                                <Hourglass class="w-3 h-3" />
                            </span>
                            <div class="min-w-0">
                                <p
                                    class="text-[10px] uppercase text-gray-400 truncate"
                                >
                                    Remaining
                                </p>
                                <p
                                    class="text-xs font-semibold text-gray-800 truncate"
                                >
                                    {{
                                        formatDuration(
                                            serviceRemainingMinutes(service) /
                                                60,
                                        ) || "0 hrs"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex-1 flex flex-col items-center justify-center text-center py-6 mb-5"
            >
                <span
                    class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-300 mb-2"
                >
                    <UserRound class="w-5 h-5" />
                </span>
                <p class="text-xs font-medium text-gray-500">
                    No caregiver assigned yet
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    We'll notify you once a caregiver is assigned.
                </p>
            </div>

            <div class="mt-auto">
                <div class="grid grid-cols-2 gap-3">
                    <button
                        @click="generateAttendanceQr('in')"
                        :disabled="isGeneratingQr"
                        class="border border-primary-600 text-primary-600 text-sm font-medium py-2 rounded-full hover:bg-primary-500 hover:text-white hover:border-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{
                            isGeneratingQr && qrDirection === "in"
                                ? "Generating..."
                                : "Generate Time-In QR"
                        }}
                    </button>

                    <button
                        @click="generateAttendanceQr('out')"
                        :disabled="isGeneratingQr"
                        class="border border-primary-600 text-primary-600 text-sm font-medium py-2 rounded-full hover:bg-primary-500 hover:text-white hover:border-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{
                            isGeneratingQr && qrDirection === "out"
                                ? "Generating..."
                                : "Generate Time-Out QR"
                        }}
                    </button>
                </div>

                <QrCodeModal
                    :show="showQrModal"
                    :token="qrToken"
                    :mode="qrDirection === 'in' ? 'clock-in' : 'clock-out'"
                    description="Show this code to the caregiver to scan on arrival."
                    @close="closeQrModal"
                />
            </div>
        </div>

        <div
            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 h-full"
        >
            <p class="text-sm font-semibold text-gray-800 mb-4">
                Latest's Updates
            </p>

            <p
                v-if="!updates.length"
                class="py-6 text-center text-xs text-gray-400"
            >
                No updates recorded yet.
            </p>

            <ul v-else class="space-y-3">
                <li
                    v-for="u in updates"
                    :key="u.id"
                    class="flex items-center justify-between rounded-xl px-3 py-2.5"
                    :class="u.highlighted ? 'bg-violet-50' : ''"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="w-8 h-8 rounded-lg flex items-center justify-center"
                            :class="
                                u.highlighted
                                    ? 'bg-violet-100 text-violet-600'
                                    : 'bg-primary-50 text-primary-600'
                            "
                        >
                            <Pill v-if="u.icon === 'med'" class="w-4 h-4" />

                            <Utensils
                                v-else-if="u.icon === 'meal'"
                                class="w-4 h-4"
                            />

                            <Activity
                                v-else-if="u.icon === 'therapy'"
                                class="w-4 h-4"
                            />

                            <CalendarClock v-else class="w-4 h-4" />
                        </span>

                        <div>
                            <p
                                class="text-sm font-medium"
                                :class="
                                    u.highlighted
                                        ? 'text-violet-700'
                                        : 'text-gray-800'
                                "
                            >
                                {{ u.title }}
                            </p>

                            <p
                                v-if="u.subtitle"
                                class="text-xs"
                                :class="
                                    u.highlighted
                                        ? 'text-violet-500'
                                        : 'text-gray-400'
                                "
                            >
                                {{ u.subtitle }}
                            </p>

                            <p
                                v-if="u.description"
                                class="text-xs mt-0.5"
                                :class="
                                    u.highlighted
                                        ? 'text-violet-500'
                                        : 'text-gray-400'
                                "
                            >
                                {{ u.description }}
                            </p>
                        </div>
                    </div>

                    <span
                        class="text-xs font-medium"
                        :class="
                            u.highlighted ? 'text-violet-600' : 'text-gray-400'
                        "
                    >
                        {{ u.time }}
                    </span>
                </li>
            </ul>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-sm font-semibold text-gray-800 mb-4">
                Upcoming Payment
            </p>

            <div class="flex gap-5 items-stretch w-full">
                <div
                    class="flex-1 bg-primary-50 rounded-xl p-4 flex items-center justify-between"
                >
                    <div>
                        <p class="text-xs text-gray-400">Overall Balance</p>

                        <p class="text-lg font-semibold text-gray-900">
                            {{ peso(billing.overall_balance) }}
                        </p>
                    </div>

                    <span
                        class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 shrink-0"
                    >
                        <Calendar class="w-[18px] h-[18px]" />
                    </span>
                </div>

                <div
                    class="flex-1 bg-emerald-50 rounded-xl p-4 flex items-center justify-between"
                >
                    <div>
                        <p class="text-xs text-emerald-600">
                            Refundable Amount
                        </p>

                        <p class="text-lg font-semibold text-emerald-700">
                            {{ peso(billing.refundable_amount) }}
                        </p>
                    </div>

                    <span
                        class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0"
                    >
                        <ArrowDownCircle class="w-[18px] h-[18px]" />
                    </span>
                </div>
            </div>

            <div
                v-if="billing.latest_invoice_code"
                class="mt-4 text-xs space-y-1"
            >
                <p class="text-gray-400">Latest Invoice</p>

                <p class="font-semibold text-gray-800">
                    {{ billing.latest_invoice_code }}
                </p>

                <p class="text-gray-400 mt-2">Status</p>

                <p class="font-semibold text-gray-800 capitalize">
                    {{ billing.status || "N/A" }}
                </p>

                <p class="text-gray-400 mt-2">Invoice Balance</p>

                <p class="font-semibold text-gray-800">
                    {{ peso(billing.balance_due) }}
                </p>
            </div>

            <NuxtLink
                to="/portal/balance"
                class="mt-4 w-full border border-primary-600 text-primary-600 text-sm font-medium py-2 rounded-full transition-colors hover:bg-primary-500 hover:text-white hover:border-primary-500 text-center block"
            >
                View Bill Date
            </NuxtLink>
        </div>

        <div
            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 h-full flex flex-col"
        >
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-semibold text-gray-800">
                    Recent Transactions
                </p>

                <NuxtLink
                    to="/portal/balance"
                    class="text-xs text-primary-600 font-medium"
                >
                    View All
                </NuxtLink>
            </div>

            <ul v-if="transactions.length" class="space-y-3">
                <li
                    v-for="t in transactions.slice(0, 3)"
                    :key="t.payment_id"
                    class="flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="w-8 h-8 rounded-lg flex items-center justify-center bg-emerald-50 text-emerald-600"
                        >
                            <ArrowDownCircle class="w-4 h-4" />
                        </span>

                        <div>
                            <p class="text-sm font-medium text-gray-800">
                                Payment Received
                            </p>

                            <p class="text-xs text-gray-400">
                                {{ t.created_at }}
                            </p>
                        </div>
                    </div>

                    <span class="text-sm font-semibold text-emerald-600">
                        +{{ peso(t.amount) }}
                    </span>
                </li>
            </ul>

            <p v-else class="text-xs text-gray-400 py-4 text-center">
                No transactions yet.
            </p>

            <NuxtLink
                to="/portal/balance"
                class="mt-auto pt-4 text-xs text-primary-600 font-medium flex items-center gap-1"
            >
                View All Transactions →
            </NuxtLink>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from "vue";

import { patientAccessService } from "../../api/patient-access/PatientAccessService";
import { onlineScheduleService } from "~/api/online-schedule/OnlineScheduleService";
import { useSchedule } from "~/composables/useSchedule";
import { useToast } from "~/composables/useToast";
import { formatDuration } from "~/utils/time";
import { formatCurrency } from "~/utils/currency";
import QrCodeModal from "~/components/ui/QrCodeModal.vue";
import EmptyState from "~/components/ui/EmptyState.vue";
import type { ScheduleItem, ScheduleServiceItem } from "~/types/schedule";
import type { PatientActivity } from "~/types/patient-activity";

import {
    Camera,
    Play,
    Pause,
    SkipBack,
    SkipForward,
    Volume2,
    VolumeX,
    Maximize,
    Pill,
    Utensils,
    Activity,
    CalendarClock,
    Calendar,
    ArrowDownCircle,
    ChevronLeft,
    ChevronRight,
    Home,
    Clock,
    Timer,
    Hourglass,
    UserRound,
} from "lucide-vue-next";

useHead({
    title: "Overview",
});

definePageMeta({
    layout: "portal",
});

const roomFeedPhoto = "https://placehold.co/800x450?text=Live+Feed";

interface PatientSchedule {
    adl: ScheduleItem | null;
    medical: ScheduleItem | null;
}

interface PatientData {
    patient_id: number;
    full_name: string;
    branch_name: string;
    location_name: string;
    status: string;
    photo: string;
    room_label: string;
    room_no: string | null;
    bed_no: string | null;
    blood_type: string | null;
    phone_number: string | null;
    room_type: string | null;
    location_type: "facility" | "homecare" | "none";
    schedule: PatientSchedule;
}

interface BillingData {
    overall_balance: number;
    refundable_amount: number;
    latest_invoice_code: string | null;
    balance_due: number;
    status: string;
}

interface TransactionData {
    payment_id: number;
    reference_id: string | null;
    amount: number;
    created_at: string;
}

interface UpdateItem {
    id: string;
    icon: PatientActivity["type"];
    title: string;
    subtitle?: string;
    description?: string;
    time: string;
    highlighted?: boolean;
}

const UPDATES_LIMIT = 5;

const now = ref(new Date());

let clockTimer: ReturnType<typeof setInterval> | undefined;

const formattedTime = computed(() =>
    now.value.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
    }),
);

const isLoading = ref(true);

const loadError = ref<string | null>(null);

const noPatients = ref(false);

const rawRecords = ref<any[]>([]);

const lovedOnes = ref<PatientData[]>([]);

const selectedIndex = ref(0);

const primaryLovedOne = computed(
    () => lovedOnes.value[selectedIndex.value] ?? null,
);

const isAdmission = computed(
    () => primaryLovedOne.value?.location_type === "facility",
);

const isHomecare = computed(
    () => primaryLovedOne.value?.location_type === "homecare",
);

const adlServices = computed(
    () => primaryLovedOne.value?.schedule.adl?.services ?? [],
);

const adlVisitSubtitle = computed(() => {
    const adl = primaryLovedOne.value?.schedule.adl;

    if (!adl) {
        return "";
    }

    const category = adl.category || "ADL";

    if (!adl.scheduled_date) {
        return `${category} visit`;
    }

    const date = new Date(adl.scheduled_date).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });

    return `${category} visit · ${date}${adl.start_time ? ` at ${adl.start_time}` : ""}`;
});

const { scheduleStatusTheme, scheduleStatusLabel } = useSchedule();

const { error: showError } = useToast();

function initials(name?: string | null) {
    if (!name) {
        return "?";
    }

    return name
        .split(" ")
        .map((word) => word.charAt(0))
        .slice(0, 2)
        .join("")
        .toUpperCase();
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

function serviceWorkedMinutes(service: ScheduleServiceItem) {
    return (service.assignees ?? []).reduce(
        (total, assignee) =>
            total +
            (assignee.online ?? []).reduce(
                (sum, scan) => sum + workedMinutes(scan),
                0,
            ),
        0,
    );
}

function serviceWorkedMinutesCapped(service: ScheduleServiceItem) {
    return Math.min(
        serviceWorkedMinutes(service),
        service.duration_minutes ?? 0,
    );
}

function serviceRemainingMinutes(service: ScheduleServiceItem) {
    return Math.max(
        (service.duration_minutes ?? 0) - serviceWorkedMinutesCapped(service),
        0,
    );
}

const billing = ref<BillingData>({
    overall_balance: 0,
    refundable_amount: 0,
    latest_invoice_code: null,
    balance_due: 0,
    status: "",
});

const transactions = ref<TransactionData[]>([]);

function formatActivityTime(value: string) {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return "";
    }

    return date.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
    });
}

const updates = computed<UpdateItem[]>(() => {
    const record = rawRecords.value[selectedIndex.value];
    const activities: PatientActivity[] = Array.isArray(record?.activities)
        ? record.activities
        : [];

    return activities.slice(0, UPDATES_LIMIT).map((activity) => ({
        id: activity.id,
        icon: activity.type,
        title: activity.title,
        subtitle: activity.subtitle || undefined,
        description: activity.description || undefined,
        time: formatActivityTime(activity.occurredAt),
    }));
});

const cameraPlaying = ref(true);

const cameraMuted = ref(true);

const isGeneratingQr = ref(false);

const qrDirection = ref<"in" | "out" | null>(null);

const qrToken = ref<string | null>(null);

const showQrModal = ref(false);

onMounted(() => {
    clockTimer = setInterval(() => {
        now.value = new Date();
    }, 1000 * 30);

    loadPatientData();
});

onUnmounted(() => {
    if (clockTimer) {
        clearInterval(clockTimer);
    }
});

function peso(amount: number) {
    return formatCurrency(amount, { treatMissingAsZero: true });
}

function statusStyle(status: string) {
    const map: Record<string, { badge: string }> = {
        admitted: {
            badge: "bg-emerald-50 text-emerald-600",
        },

        pending: {
            badge: "bg-amber-50 text-amber-600",
        },

        ongoing: {
            badge: "bg-amber-50 text-amber-600",
        },

        homecare: {
            badge: "bg-primary-50 text-primary-600",
        },

        discharged: {
            badge: "bg-gray-100 text-gray-500",
        },

        cancelled: {
            badge: "bg-gray-100 text-gray-500",
        },

        "no active record": {
            badge: "bg-gray-100 text-gray-500",
        },
    };

    return (
        map[status?.toLowerCase()] ?? {
            badge: "bg-gray-100 text-gray-500",
        }
    );
}

function nextLovedOne() {
    if (!lovedOnes.value.length) {
        return;
    }

    selectedIndex.value = (selectedIndex.value + 1) % lovedOnes.value.length;
}

function prevLovedOne() {
    if (!lovedOnes.value.length) {
        return;
    }

    selectedIndex.value =
        (selectedIndex.value - 1 + lovedOnes.value.length) %
        lovedOnes.value.length;
}

function mapSchedule(item: any, type: "adl" | "medical"): ScheduleItem | null {
    if (!item) {
        return null;
    }

    return {
        schedule_id: Number(item.schedule_id ?? 0),
        schedule_code: item.schedule_code ?? "",
        status: item.status ?? "",
        category: item.category ?? null,
        scheduled_date: item.scheduled_date ?? null,
        scheduled_at: item.scheduled_at ?? null,
        start_time: item.start_time ?? null,
        end_time: item.end_time ?? null,
        total_hours: Number(item.total_hours ?? 0),
        total_duration_minutes: Number(item.total_duration_minutes ?? 0),
        services: Array.isArray(item.services) ? item.services : [],
        type,
    };
}

function mapPatientSchedules(schedule: any): PatientSchedule {
    return {
        adl: mapSchedule(schedule?.adl, "adl"),

        medical: mapSchedule(schedule?.medical, "medical"),
    };
}

function deriveStatusLabel(ctx: any): string {
    if (ctx?.type === "facility" || ctx?.type === "admission_fallback") {
        return (ctx?.status ?? "Unknown")
            .toString()
            .replace(/_/g, " ")
            .replace(/\b\w/g, (c: string) => c.toUpperCase());
    }

    if (ctx?.type === "homecare") {
        return "Homecare";
    }

    return "Inactive";
}

function deriveLocationType(
    ctx: any,
    schedule: PatientSchedule,
): "facility" | "homecare" | "none" {
    const type = ctx?.type?.toString().toLowerCase();

    if (type === "facility" || type === "admission_fallback") {
        return "facility";
    }

    if (type === "homecare" || type === "adl") {
        return "homecare";
    }

    if (type === "none" && schedule?.adl) {
        return "homecare";
    }

    return "none";
}

function mapPatientRecord(item: any): PatientData {
    const patient = item?.patient ?? {};

    const org = item?.organization ?? {};

    const ctx = item?.location_context ?? {};

    const rawSchedule = item?.schedule ?? {};

    const schedule = mapPatientSchedules(rawSchedule);

    const locationType = deriveLocationType(ctx, schedule);

    return {
        patient_id: Number(patient?.patient_id ?? 0),

        full_name:
            patient?.full_name ??
            [patient?.first_name, patient?.middle_name, patient?.last_name]
                .filter(Boolean)
                .join(" "),

        branch_name: org?.name ?? "N/A",

        location_name: org?.full_address ?? "N/A",

        status: deriveStatusLabel(ctx),

        photo: patient?.photo ?? "https://placehold.co/200x200?text=Patient",

        room_label: ctx?.room?.room_no ?? ctx?.bed?.bed_no ?? "Room N/A",

        room_no: ctx?.room?.room_no ?? null,

        bed_no: ctx?.bed?.bed_no ?? null,

        blood_type: patient?.blood_type ?? null,

        phone_number: patient?.phone_number ?? null,

        room_type: ctx?.room?.room_type ?? null,

        location_type: locationType,

        schedule,
    };
}

function updateBillingFromRecord(item: any) {
    const invoice = item?.latest_invoice;

    billing.value = {
        overall_balance: Number(item?.patient_balance ?? 0),

        refundable_amount: Number(item?.patient_refundable ?? 0),

        latest_invoice_code: invoice?.invoice_code ?? null,

        balance_due: Number(invoice?.balance_due ?? 0),

        status: invoice?.status ?? "",
    };

    transactions.value = Array.isArray(invoice?.payments)
        ? invoice.payments.map((payment: any) => ({
              payment_id: Number(payment?.payment_id ?? 0),

              reference_id: payment?.reference_id ?? null,

              amount: Number(payment?.amount ?? 0),

              created_at: payment?.created_at ?? "",
          }))
        : [];
}

watch(selectedIndex, (idx) => {
    const record = rawRecords.value[idx];

    if (record) {
        updateBillingFromRecord(record);
    }

    closeQrModal();
});

async function generateAttendanceQr(direction: "in" | "out") {
    const scheduleServicesId = adlServices.value[0]?.schedule_services_id;

    if (!scheduleServicesId) {
        showError(
            "Can't generate QR code",
            "No ADL service is available for this visit yet.",
        );

        return;
    }

    isGeneratingQr.value = true;

    qrDirection.value = direction;

    qrToken.value = null;

    try {
        const res = await onlineScheduleService.generateQr({
            type: direction,
            schedule_services_id: scheduleServicesId,
        });

        qrToken.value = res?.token ?? res?.data?.token ?? null;

        showQrModal.value = Boolean(qrToken.value);
    } catch (err: any) {
        const message =
            err?.response?.data?.message ??
            err?.message ??
            "Please try again in a moment.";

        showError("Failed to generate attendance QR", message);

        console.error("Failed to generate attendance QR:", err);
    } finally {
        isGeneratingQr.value = false;
    }
}

function closeQrModal() {
    showQrModal.value = false;

    qrToken.value = null;

    qrDirection.value = null;
}

async function loadPatientData() {
    isLoading.value = true;

    loadError.value = null;

    noPatients.value = false;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "profile,financials,schedule,activity",
        });

        const records: any[] = Array.isArray(res?.data) ? res.data : [];

        if (!records.length) {
            lovedOnes.value = [];

            rawRecords.value = [];

            noPatients.value = true;

            return;
        }

        const mappedPatients = records.map(mapPatientRecord);

        lovedOnes.value = mappedPatients;

        rawRecords.value = records;

        const homecareIndex = mappedPatients.findIndex(
            (patient) => patient.location_type === "homecare",
        );

        selectedIndex.value = homecareIndex >= 0 ? homecareIndex : 0;

        const selectedRecord = rawRecords.value[selectedIndex.value];

        if (selectedRecord) {
            updateBillingFromRecord(selectedRecord);
        }
    } catch (err: any) {
        loadError.value = err?.message ?? "Failed to load patient data.";
    } finally {
        isLoading.value = false;
    }
}
function assigneesOf(service: ScheduleServiceItem) {
    return service.assignees ?? [];
}

function primaryAssignee(service: ScheduleServiceItem) {
    return assigneesOf(service)[0] ?? null;
}
</script>
