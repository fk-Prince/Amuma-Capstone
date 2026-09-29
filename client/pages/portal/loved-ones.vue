<template>
    <div class="min-h-full first:space-y-5">
        <div v-if="isLoading" class="space-y-5 animate-pulse">
            <div
                class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 sm:p-6 dark:bg-secondary dark:border-white/10"
            >
                <div
                    class="flex flex-col sm:flex-row items-start gap-4 sm:gap-5"
                >
                    <div
                        class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-200 shrink-0 dark:bg-white/15"
                    ></div>

                    <div class="flex-1 min-w-0 w-full space-y-3">
                        <div class="flex items-center gap-2">
                            <div
                                class="h-6 w-40 sm:w-48 rounded-md bg-gray-200 dark:bg-white/15"
                            ></div>
                            <div
                                class="h-5 w-20 rounded-full bg-gray-100 dark:bg-white/10"
                            ></div>
                        </div>

                        <div
                            class="h-4 w-40 rounded bg-gray-100 dark:bg-white/10"
                        ></div>
                        <div
                            class="h-4 w-full max-w-md rounded bg-gray-100 dark:bg-white/10"
                        ></div>
                        <div
                            class="h-4 w-32 rounded bg-gray-100 dark:bg-white/10"
                        ></div>

                        <div class="flex flex-wrap gap-2 pt-1">
                            <div
                                class="h-7 w-20 rounded-full bg-gray-100 dark:bg-white/10"
                            ></div>
                            <div
                                class="h-7 w-24 rounded-full bg-gray-100 dark:bg-white/10"
                            ></div>
                            <div
                                class="h-7 w-32 rounded-full bg-gray-100 dark:bg-white/10"
                            ></div>
                        </div>

                        <div class="flex flex-wrap gap-2 pt-1">
                            <div
                                class="h-9 w-36 rounded-full bg-gray-200 dark:bg-white/15"
                            ></div>
                            <div
                                class="h-9 w-28 rounded-full bg-gray-200 dark:bg-white/15"
                            ></div>
                            <div
                                class="h-9 w-28 rounded-full bg-gray-200 dark:bg-white/15"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div
                    v-for="card in 3"
                    :key="card"
                    class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 dark:bg-secondary dark:border-white/10"
                >
                    <div
                        class="h-4 w-36 rounded bg-gray-200 mb-5 dark:bg-white/15"
                    ></div>

                    <div class="space-y-5">
                        <div
                            v-for="row in 4"
                            :key="row"
                            class="flex items-start gap-3"
                        >
                            <div
                                class="w-8 h-8 rounded-lg bg-gray-100 shrink-0 dark:bg-white/10"
                            ></div>

                            <div class="flex-1 min-w-0 space-y-2 pt-1">
                                <div
                                    class="h-3.5 w-32 rounded bg-gray-200 dark:bg-white/15"
                                ></div>
                                <div
                                    class="h-3 w-24 rounded bg-gray-100 dark:bg-white/10"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 sm:p-5 dark:bg-secondary dark:border-white/10"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5"
                >
                    <div
                        class="flex items-center gap-1 bg-gray-50 rounded-lg p-1 w-fit dark:bg-white/5"
                    >
                        <div
                            class="h-7 w-24 rounded-md bg-gray-200 dark:bg-white/15"
                        ></div>
                        <div
                            class="h-7 w-24 rounded-md bg-gray-100 dark:bg-white/10"
                        ></div>
                    </div>

                    <div
                        class="h-4 w-14 rounded bg-gray-100 dark:bg-white/10"
                    ></div>
                </div>

                <div class="space-y-3">
                    <div class="px-2 sm:px-4 pt-2 space-y-2">
                        <div
                            class="h-4 w-36 rounded bg-gray-200 dark:bg-white/15"
                        ></div>
                        <div
                            class="h-3 w-full max-w-md rounded bg-gray-100 dark:bg-white/10"
                        ></div>
                    </div>

                    <div class="px-2 sm:px-4">
                        <div
                            class="h-10 rounded-lg bg-gray-50 dark:bg-white/5"
                        ></div>
                    </div>

                    <div
                        v-for="row in 4"
                        :key="row"
                        class="grid grid-cols-2 sm:grid-cols-4 gap-4 px-2 sm:px-4 py-4 border-b border-gray-50 dark:border-white/10"
                    >
                        <div
                            class="h-3.5 w-28 max-w-full rounded bg-gray-100 dark:bg-white/10"
                        ></div>
                        <div
                            class="h-3.5 w-24 max-w-full rounded bg-gray-100 dark:bg-white/10"
                        ></div>
                        <div
                            class="hidden sm:block h-3.5 w-20 max-w-full rounded bg-gray-100 dark:bg-white/10"
                        ></div>
                        <div
                            class="hidden sm:block h-3.5 w-16 max-w-full rounded bg-gray-100 dark:bg-white/10"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <EmptyState
            v-else-if="noPatients"
            title="You currently have no patients"
            cta-label="Book a Service"
            cta-to="/booking/search"
        />

        <div
            v-else-if="loadError"
            class="bg-white dark:bg-secondary rounded-lg border border-gray-100 dark:border-white/10 shadow-sm p-8 sm:p-10 text-center"
        >
            <p class="text-sm text-rose-500 dark:text-rose-300">
                {{ loadError }}
            </p>

            <button
                type="button"
                @click="loadPatientData"
                class="mt-2 text-xs text-primary-600 font-medium hover:text-primary-700 dark:text-primary-300 dark:hover:text-primary-300"
            >
                Retry
            </button>
        </div>

        <template v-else>
            <div
                class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 sm:p-6 dark:bg-secondary dark:border-white/10"
            >
                <div class="flex items-center justify-between mb-4">
                    <p
                        class="text-sm font-semibold text-gray-800 dark:text-white"
                    >
                        My Loved Ones
                    </p>

                    <div
                        v-if="lovedOnes.length > 1"
                        class="flex items-center gap-1"
                    >
                        <button
                            type="button"
                            @click="prevLovedOne"
                            class="w-6 h-6 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-50 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-400"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </button>

                        <button
                            type="button"
                            @click="nextLovedOne"
                            class="w-6 h-6 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-50 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/5 dark:hover:text-gray-400"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div class="overflow-hidden">
                    <div
                        class="flex transition-transform duration-300 ease-in-out"
                        :style="{
                            transform: `translateX(-${selectedIndex * 100}%)`,
                        }"
                    >
                        <div
                            v-for="lo in lovedOnes"
                            :key="lo.patient_id"
                            class="w-full shrink-0 flex flex-col sm:flex-row items-start gap-4 sm:gap-5 px-1"
                        >
                            <div class="relative shrink-0">
                                <img
                                    v-if="lo.photo"
                                    :src="lo.photo"
                                    class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover object-top"
                                    alt=""
                                />

                                <div
                                    v-else
                                    class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center text-2xl font-bold dark:bg-primary-500/10 dark:text-primary-300"
                                >
                                    {{ initials(lo.name) }}
                                </div>

                                <button
                                    type="button"
                                    class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-primary-500 text-white flex items-center justify-center border-2 border-white hover:bg-primary-600"
                                >
                                    <Pencil class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <div class="flex-1 min-w-0 w-full">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2
                                        class="text-xl font-bold text-gray-900 break-words dark:text-white"
                                    >
                                        {{ lo.name }}
                                    </h2>

                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[11px] font-medium capitalize"
                                        :class="
                                            statusStyle(lo.admissionStatus)
                                                .badge
                                        "
                                    >
                                        {{ lo.admissionStatus }}
                                    </span>

                                    <span
                                        v-if="
                                            lo.roomType?.toLowerCase() === 'vip'
                                        "
                                        class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-600 text-[11px] font-medium flex items-center gap-1 dark:bg-amber-500/10 dark:text-amber-300"
                                    >
                                        <Crown class="w-3 h-3" />
                                        VIP
                                    </span>
                                </div>

                                <p
                                    v-if="lo.branchName !== 'N/A'"
                                    class="text-sm font-medium text-primary-600 mt-1.5 flex items-start gap-1.5 dark:text-primary-300"
                                >
                                    <Building2
                                        class="w-3.5 h-3.5 shrink-0 mt-0.5"
                                    />
                                    <span class="break-words">{{
                                        lo.branchName
                                    }}</span>
                                </p>

                                <p
                                    v-if="lo.address !== 'N/A'"
                                    class="text-sm text-gray-400 flex items-start gap-1.5 mt-0.5 dark:text-gray-500"
                                >
                                    <MapPin
                                        class="w-3.5 h-3.5 shrink-0 mt-0.5"
                                    />
                                    <span class="break-words">{{
                                        lo.address
                                    }}</span>
                                </p>

                                <p
                                    v-if="lo.status === 'Admitted'"
                                    class="text-sm text-primary-500 font-medium mt-1 dark:text-primary-300"
                                >
                                    Room {{ lo.roomLabel }} · Bed
                                    {{ lo.bedLabel }}
                                </p>

                                <p
                                    v-else-if="lo.status === 'Discharged'"
                                    class="text-sm text-gray-500 font-medium mt-1 dark:text-gray-400"
                                >
                                    Discharged {{ lo.dischargeDate }}
                                </p>

                                <div
                                    class="flex items-center gap-2 flex-wrap mt-3"
                                >
                                    <span
                                        v-if="lo.age"
                                        class="px-2.5 py-1 rounded-full bg-gray-50 text-gray-600 text-xs dark:bg-white/5 dark:text-gray-400"
                                    >
                                        {{ lo.age }} yrs old
                                    </span>

                                    <span
                                        v-if="lo.bloodType !== 'N/A'"
                                        class="px-2.5 py-1 rounded-full bg-gray-50 text-gray-600 text-xs dark:bg-white/5 dark:text-gray-400"
                                    >
                                        {{ lo.bloodType }} blood type
                                    </span>

                                    <span
                                        v-if="lo.admissionDate !== 'N/A'"
                                        class="px-2.5 py-1 rounded-full bg-gray-50 text-gray-600 text-xs dark:bg-white/5 dark:text-gray-400"
                                    >
                                        {{ lo.admissionLabel }}
                                        {{ lo.admissionDate }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center gap-2.5 flex-wrap mt-4"
                                >
                                    <NuxtLink
                                        v-for="link in patientLinks"
                                        :key="link.to"
                                        :to="patientRoute(link.to, lo.uuid)"
                                        class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-primary-500 text-primary-600 text-sm font-medium hover:bg-primary-500 hover:text-white transition-colors dark:text-primary-300"
                                    >
                                        <component
                                            :is="link.icon"
                                            class="w-3.5 h-3.5"
                                        />
                                        {{ link.label }}
                                    </NuxtLink>

                                    <button
                                        v-if="lo.status === 'Homecare'"
                                        type="button"
                                        class="flex items-center gap-1.5 px-4 py-2 rounded-full bg-primary-500 text-white text-sm font-medium hover:bg-primary-600 transition-colors"
                                        @click="showBookAgainModal = true"
                                    >
                                        <CalendarPlus class="w-3.5 h-3.5" />
                                        Book Again
                                    </button>

                                    <button
                                        v-if="lo.status === 'Admitted'"
                                        type="button"
                                        class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-primary-500 text-primary-600 text-sm font-medium hover:bg-primary-500 hover:text-white transition-colors dark:text-primary-300"
                                        @click="openCaregiverShifts"
                                    >
                                        <HeartHandshake class="w-3.5 h-3.5" />
                                        View Caregiver
                                    </button>

                                    <button
                                        v-if="lo.status === 'Admitted'"
                                        type="button"
                                        class="flex items-center gap-1.5 px-4 py-2 rounded-full bg-primary-500 text-white text-sm font-medium hover:bg-primary-600 transition-colors"
                                        @click="openAdmissionTimeline"
                                    >
                                        <History class="w-3.5 h-3.5" />
                                        View Admission Timeline
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="lovedOnes.length > 1"
                    class="flex items-center justify-center gap-1.5 mt-4"
                >
                    <button
                        v-for="(lo, idx) in lovedOnes"
                        :key="lo.patient_id"
                        type="button"
                        @click="selectedIndex = idx"
                        class="h-1.5 rounded-full transition-all"
                        :class="
                            selectedIndex === idx
                                ? 'w-5 bg-primary-500'
                                : 'w-1.5 bg-gray-200 hover:bg-gray-300 dark:bg-white/15 dark:hover:bg-white/20'
                        "
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div
                    class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 sm:p-5 min-w-0 dark:bg-secondary dark:border-white/10"
                >
                    <p
                        class="text-sm font-semibold text-gray-800 mb-4 dark:text-white"
                    >
                        Personal Information
                    </p>

                    <ul class="space-y-4">
                        <li class="flex items-start gap-3 min-w-0">
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 dark:bg-primary-500/10 dark:text-primary-300"
                            >
                                <Cake class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 break-words dark:text-white"
                                >
                                    {{ lovedOne.birthdate }}
                                </p>
                                <p
                                    class="text-xs text-gray-400 break-words dark:text-gray-500"
                                >
                                    {{ lovedOne.age }} ·
                                    {{ lovedOne.gender }}
                                </p>
                            </div>
                        </li>

                        <li class="flex items-start gap-3 min-w-0">
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 dark:bg-primary-500/10 dark:text-primary-300"
                            >
                                <Droplet class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 break-words dark:text-white"
                                >
                                    {{ lovedOne.bloodType }}
                                </p>
                                <p
                                    class="text-xs text-gray-400 dark:text-gray-500"
                                >
                                    Blood type
                                </p>
                            </div>
                        </li>

                        <li class="flex items-start gap-3 min-w-0">
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 dark:bg-primary-500/10 dark:text-primary-300"
                            >
                                <Phone class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <a
                                    v-if="isValidPhone(lovedOne.contactNumber)"
                                    :href="`tel:${lovedOne.contactNumber}`"
                                    class="text-sm font-medium text-gray-800 hover:text-primary-600 transition-colors break-all dark:text-white dark:hover:text-primary-300"
                                >
                                    {{ lovedOne.contactNumber }}
                                </a>

                                <p
                                    v-else
                                    class="text-sm font-medium text-gray-800 break-words dark:text-white"
                                >
                                    {{ lovedOne.contactNumber }}
                                </p>
                                <p
                                    class="text-xs text-gray-400 dark:text-gray-500"
                                >
                                    Contact number
                                </p>
                            </div>
                        </li>

                        <li class="flex items-start gap-3 min-w-0">
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 dark:bg-primary-500/10 dark:text-primary-300"
                            >
                                <Home class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 break-words dark:text-white"
                                >
                                    {{ lovedOne.homeAddress }}
                                </p>
                                <p
                                    class="text-xs text-gray-400 dark:text-gray-500"
                                >
                                    Home address
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div
                    class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 sm:p-5 min-w-0 flex flex-col dark:bg-secondary dark:border-white/10"
                >
                    <p
                        class="text-sm font-semibold text-gray-800 mb-4 dark:text-white"
                    >
                        Diagnosis
                    </p>

                    <template v-if="latestDiagnosis">
                        <p
                            class="text-sm font-medium text-gray-800 break-words dark:text-white"
                        >
                            {{ latestDiagnosis.diagnosis || "Diagnosis" }}
                        </p>

                        <p
                            class="mt-0.5 text-xs text-gray-400 dark:text-gray-500"
                        >
                            {{
                                latestDiagnosis.diagnosis_date
                                    ? `Last updated ${formatDate(latestDiagnosis.diagnosis_date)}`
                                    : "No date recorded"
                            }}
                        </p>
                    </template>

                    <p v-else class="text-xs text-gray-400 dark:text-gray-500">
                        No diagnosis recorded yet.
                    </p>

                    <button
                        v-if="lovedOne.diagnoses.length"
                        type="button"
                        class="mt-4 flex items-center justify-center gap-1.5 w-full px-4 py-2 rounded-xl border border-primary-500 text-primary-600 text-xs font-medium hover:bg-primary-500 hover:text-white transition-colors dark:text-primary-300"
                        @click="showDiagnosisModal = true"
                    >
                        <FileText class="w-3.5 h-3.5" />
                        View all diagnoses ({{ lovedOne.diagnoses.length }})
                    </button>

                    <p
                        class="text-sm font-semibold text-gray-800 mt-6 mb-4 dark:text-white"
                    >
                        Assessment
                    </p>

                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{
                            latestAssessmentDate
                                ? `Last updated ${formatDate(latestAssessmentDate)}`
                                : "No assessment recorded yet."
                        }}
                    </p>

                    <button
                        v-if="lovedOne.assessments.length"
                        type="button"
                        class="mt-4 flex items-center justify-center gap-1.5 w-full px-4 py-2 rounded-xl border border-primary-500 text-primary-600 text-xs font-medium hover:bg-primary-500 hover:text-white transition-colors dark:text-primary-300"
                        @click="showAssessmentModal = true"
                    >
                        <ClipboardList class="w-3.5 h-3.5" />
                        View all assessments ({{ lovedOne.assessments.length }})
                    </button>

                    <p
                        class="text-sm font-semibold text-gray-800 mt-6 mb-3 dark:text-white"
                    >
                        Allergies
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="a in lovedOne.allergies"
                            :key="a"
                            class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-600 text-xs font-medium max-w-full break-words dark:bg-rose-500/10 dark:text-rose-300"
                        >
                            {{ a }}
                        </span>

                        <span
                            v-if="!lovedOne.allergies.length"
                            class="text-xs text-gray-400 dark:text-gray-500"
                        >
                            No known allergies
                        </span>
                    </div>
                </div>

                <div
                    class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 sm:p-5 min-w-0 dark:bg-secondary dark:border-white/10"
                >
                    <p
                        class="text-sm font-semibold text-gray-800 mb-4 dark:text-white"
                    >
                        Patient Access & Emergency Contact
                    </p>

                    <div class="flex items-center gap-3 min-w-0">
                        <span
                            class="w-10 h-10 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 dark:bg-primary-500/10 dark:text-primary-300"
                        >
                            <UserRound class="w-5 h-5" />
                        </span>

                        <div class="min-w-0">
                            <p
                                class="text-sm font-semibold text-gray-800 break-words dark:text-white"
                            >
                                {{ lovedOne.client.name }}
                            </p>
                            <p
                                class="text-xs text-gray-400 break-words dark:text-gray-500"
                            >
                                Your relationship to this patient ·
                                {{ lovedOne.relationship }}
                            </p>
                        </div>
                    </div>

                    <li class="flex items-start gap-3 min-w-0 mt-4">
                        <span
                            class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 dark:bg-primary-500/10 dark:text-primary-300"
                        >
                            <Phone class="w-4 h-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <a
                                v-if="isValidPhone(lovedOne.client.phone)"
                                :href="`tel:${lovedOne.client.phone}`"
                                class="text-sm font-medium text-gray-800 hover:text-primary-600 transition-colors break-all dark:text-white dark:hover:text-primary-300"
                            >
                                <span class="break-all">{{
                                    lovedOne.client.phone
                                }}</span>
                            </a>

                            <p
                                v-else
                                class="text-sm font-medium text-gray-800 break-words dark:text-white"
                            >
                                {{ lovedOne.contactNumber }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                Contact number
                            </p>
                        </div>
                    </li>

                    <ul class="mt-4 space-y-3">
                        <li
                            v-if="lovedOne.client.email !== 'N/A'"
                            class="flex items-start gap-3 min-w-0"
                        >
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 dark:bg-primary-500/10 dark:text-primary-300"
                            >
                                <Mail class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 break-all dark:text-white"
                                >
                                    {{ lovedOne.client.email }}
                                </p>
                                <p
                                    class="text-xs text-gray-400 dark:text-gray-500"
                                >
                                    Email
                                </p>
                            </div>
                        </li>

                        <li
                            v-if="lovedOne.accessGrantedAt !== 'N/A'"
                            class="flex items-start gap-3 min-w-0"
                        >
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 dark:bg-primary-500/10 dark:text-primary-300"
                            >
                                <ShieldCheck class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 dark:text-white"
                                >
                                    {{
                                        lovedOne.haveAccess
                                            ? "Full access"
                                            : "Access revoked"
                                    }}
                                </p>
                                <p
                                    class="text-xs text-gray-400 break-words dark:text-gray-500"
                                >
                                    Granted {{ lovedOne.accessGrantedAt }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 sm:p-5 min-w-0 dark:bg-secondary dark:border-white/10"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4"
                >
                    <div
                        class="flex items-center gap-1 bg-gray-50 rounded-lg p-1 w-fit max-w-full dark:bg-white/5"
                    >
                        <button
                            type="button"
                            @click="activeHealthTab = 'medications'"
                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors whitespace-nowrap"
                            :class="
                                activeHealthTab === 'medications'
                                    ? 'bg-white text-primary-600 shadow-sm dark:bg-secondary dark:text-primary-300'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-200 dark:hover:text-gray-400'
                            "
                        >
                            Medications
                        </button>

                        <button
                            type="button"
                            @click="activeHealthTab = 'vitals'"
                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors whitespace-nowrap"
                            :class="
                                activeHealthTab === 'vitals'
                                    ? 'bg-white text-primary-600 shadow-sm dark:bg-secondary dark:text-primary-300'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-200 dark:hover:text-gray-400'
                            "
                        >
                            Vital Signs
                        </button>
                    </div>

                    <NuxtLink
                        v-if="activeHealthTab === 'medications'"
                        to="/portal/medications"
                        class="text-xs text-primary-600 font-medium whitespace-nowrap dark:text-primary-300"
                    >
                        View All
                    </NuxtLink>

                    <NuxtLink
                        v-else
                        to="/portal/vitals"
                        class="text-xs text-primary-600 font-medium whitespace-nowrap dark:text-primary-300"
                    >
                        View All
                    </NuxtLink>
                </div>

                <div
                    v-if="activeHealthTab === 'medications'"
                    class="space-y-3 min-w-0"
                >
                    <div class="px-2 sm:px-4 pt-4">
                        <h3
                            class="text-sm font-semibold text-gray-900 dark:text-white"
                        >
                            Latest Medications
                        </h3>

                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            Recently recorded medications for this patient.
                        </p>
                    </div>

                    <div
                        v-if="latestMedications.length"
                        class="overflow-x-auto min-w-0"
                    >
                        <MedicationTable
                            :medications="latestMedications"
                            view="client"
                            variant="preview"
                            disabled
                        />
                    </div>

                    <div
                        v-else
                        class="mx-2 sm:mx-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center dark:border-white/10 dark:bg-white/5"
                    >
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            No medications recorded
                        </p>

                        <p
                            class="mt-1 text-xs text-gray-400 dark:text-gray-500"
                        >
                            Medication records will appear here once added.
                        </p>
                    </div>
                </div>

                <div v-else class="space-y-3 min-w-0">
                    <div class="px-2 sm:px-4 pt-4">
                        <h3
                            class="text-sm font-semibold text-gray-900 dark:text-white"
                        >
                            Latest Vital Signs
                        </h3>

                        <p
                            class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                        >
                            Most recently recorded vital signs for this patient.
                        </p>
                    </div>

                    <div
                        v-if="latestVitals.length"
                        class="overflow-x-auto min-w-0"
                    >
                        <VitalSignsTable
                            :vitals="latestVitals"
                            variant="preview"
                        />
                    </div>

                    <div
                        v-else
                        class="mx-2 sm:mx-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center dark:border-white/10 dark:bg-white/5"
                    >
                        <p
                            class="text-sm font-medium text-gray-500 dark:text-gray-400"
                        >
                            No vital signs recorded
                        </p>

                        <p
                            class="mt-1 text-xs text-gray-400 dark:text-gray-500"
                        >
                            Vital sign records will appear here once added.
                        </p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <BookAgainModal
        :open="showBookAgainModal"
        :patient-id="lovedOne.patient_id"
        :patient-name="lovedOne.name"
        :branch-uuid="lovedOne.branchUuid"
        @close="showBookAgainModal = false"
    />

    <DiagnosisModal
        :open="showDiagnosisModal"
        :patient-name="lovedOne.name"
        :diagnoses="lovedOne.diagnoses"
        @close="showDiagnosisModal = false"
    />

    <AssessmentModal
        :open="showAssessmentModal"
        :patient-name="lovedOne.name"
        :assessments="lovedOne.assessments"
        @close="showAssessmentModal = false"
    />

    <AdmissionTimelineModal
        :open="showAdmissionTimelineModal"
        :loading="admissionsLoading"
        :patient-name="lovedOne.name"
        :admissions="admissionTimeline"
        @close="showAdmissionTimelineModal = false"
    />

    <CaregiverShiftTimelineModal
        :open="showCaregiverShiftsModal"
        :loading="caregiverShiftsLoading"
        :patient-name="lovedOne.name"
        :shifts="caregiverShifts"
        @close="showCaregiverShiftsModal = false"
    />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import MedicationTable from "~/components/sections/app/Patient/MedicationTable.vue";
import VitalSignsTable from "~/components/sections/app/Patient/VitalSignsTable.vue";
import BookAgainModal from "~/components/sections/app/Patient/BookAgainModal.vue";
import DiagnosisModal from "~/components/portal/DiagnosisModal.vue";
import AssessmentModal from "~/components/portal/AssessmentModal.vue";
import AdmissionTimelineModal from "~/components/portal/AdmissionTimelineModal.vue";
import CaregiverShiftTimelineModal from "~/components/portal/CaregiverShiftTimelineModal.vue";
import type { PortalAssessment, PortalDiagnosis } from "~/types/patient";
import type { CaregiverShift } from "~/types/caregiver-shift";
import EmptyState from "~/components/ui/EmptyState.vue";
import { calculateAge } from "~/utils/user";
import {
    Pencil,
    Crown,
    Building2,
    MapPin,
    MessageSquare,
    Calendar,
    CalendarPlus,
    CreditCard,
    Cake,
    Droplet,
    Phone,
    Home,
    UserRound,
    BedDouble,
    Mail,
    ShieldCheck,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    FileText,
    Activity,
    Pill,
    History,
    HeartHandshake,
} from "lucide-vue-next";
import { patientAccessService } from "../../api/patient-access/PatientAccessService";
import type { Medication } from "~/types/medication";
import type { Vital } from "~/types/medication";

useHead({ title: "My Loved Ones" });

definePageMeta({
    layout: "portal",
    middleware: "portal",
});

interface Payment {
    payment_id: number;
    reference_id: string | null;
    amount: number;
    payment_method: string;
    created_at: string;
}

interface Invoice {
    invoice_id: number;
    invoice_code: string;
    status: string;
    statusLabel: string;
    total: number;
    adjusted_total: number;
    amount_paid: number;
    balance_due: number;
    refund_status: string;
    latestPayment: Payment | null;
}

interface LovedOne {
    patient_id: number;
    uuid: string | null;
    name: string;
    address: string;
    branchName: string;
    branchUuid: string | null;
    homeAddress: string;
    status: string;
    photo: string | null;
    roomLabel: string;
    bedLabel: string;
    roomType: string | null;
    roomFloor: string | null;
    birthdate: string;
    age: string;
    gender: string;
    bloodType: string;
    contactNumber: string;
    relationship: string;
    haveAccess: boolean;
    client: {
        name: string;
        phone: string;
        email: string;
    };
    accessGrantedAt: string;
    emergencyContact: {
        name: string;
        relation: string;
        phone: string;
    };
    admissionDate: string;
    admissionLabel: string;
    admissionStatus: string;
    dischargeDate: string;
    assignedCaregiver: string;
    conditions: string[];
    allergies: string[];
    assessments: PortalAssessment[];
    diagnoses: PortalDiagnosis[];
    medications: Medication[];
    vitals: Vital[];
}

function fallbackLovedOne(): LovedOne {
    return {
        patient_id: 0,
        uuid: null,
        name: "Unnamed Resident",
        address: "N/A",
        branchName: "N/A",
        branchUuid: null,
        homeAddress: "N/A",
        status: "Unknown",
        photo: null,
        roomLabel: "N/A",
        bedLabel: "N/A",
        roomType: null,
        roomFloor: null,
        birthdate: "N/A",
        age: "N/A",
        gender: "N/A",
        bloodType: "N/A",
        contactNumber: "N/A",
        relationship: "N/A",
        haveAccess: false,
        client: {
            name: "N/A",
            phone: "N/A",
            email: "N/A",
        },
        accessGrantedAt: "N/A",
        emergencyContact: {
            name: "N/A",
            relation: "N/A",
            phone: "N/A",
        },
        admissionDate: "N/A",
        admissionLabel: "Admission",
        admissionStatus: "N/A",
        dischargeDate: "N/A",
        assignedCaregiver: "N/A",
        conditions: [],
        allergies: [],
        assessments: [],
        diagnoses: [],
        medications: [],
        vitals: [],
    };
}

const isLoading = ref(true);
const loadError = ref<string | null>(null);
const noPatients = ref(false);
const lovedOnes = ref<LovedOne[]>([]);
const selectedIndex = ref(0);
const activeHealthTab = ref<"medications" | "vitals">("medications");
function nextLovedOne() {
    if (!lovedOnes.value.length) return;

    selectedIndex.value = (selectedIndex.value + 1) % lovedOnes.value.length;
}

function prevLovedOne() {
    if (!lovedOnes.value.length) return;

    selectedIndex.value =
        (selectedIndex.value - 1 + lovedOnes.value.length) %
        lovedOnes.value.length;
}

const { resolveIndex, syncQuery } = usePatientQuerySelection();

watch(selectedIndex, () =>
    syncQuery(lovedOnes.value[selectedIndex.value]?.uuid),
);

const lovedOne = computed(
    () => lovedOnes.value[selectedIndex.value] ?? fallbackLovedOne(),
);

const patientLinks = [
    { to: "/portal/schedule", label: "View Schedule", icon: Calendar },
    { to: "/portal/updates", label: "View Updates", icon: Activity },
    { to: "/portal/medications", label: "View Medication", icon: Pill },
    { to: "/portal/balance", label: "View Balance", icon: CreditCard },
];

function patientRoute(path: string, uuid: string | null) {
    return {
        path,
        query: uuid ? { patient: uuid } : undefined,
    };
}

const showBookAgainModal = ref(false);
const showDiagnosisModal = ref(false);
const showAssessmentModal = ref(false);
const showAdmissionTimelineModal = ref(false);
const admissionsLoading = ref(false);
const admissionTimeline = ref<any[]>([]);
const admissionTimelineLoadedFor = ref<number | null>(null);

async function loadAdmissionTimeline(patientId: number) {
    admissionsLoading.value = true;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "admissions",
            patient_id: patientId,
        });

        admissionTimeline.value = Array.isArray(res?.data?.admissions)
            ? res.data.admissions
            : [];

        admissionTimelineLoadedFor.value = patientId;
    } catch (err) {
        console.error("Error loading admission timeline:", err);
        admissionTimeline.value = [];
    } finally {
        admissionsLoading.value = false;
    }
}

async function openAdmissionTimeline() {
    showAdmissionTimelineModal.value = true;

    if (
        lovedOne.value.patient_id &&
        admissionTimelineLoadedFor.value !== lovedOne.value.patient_id
    ) {
        await loadAdmissionTimeline(lovedOne.value.patient_id);
    }
}

const showCaregiverShiftsModal = ref(false);
const caregiverShiftsLoading = ref(false);
const caregiverShifts = ref<CaregiverShift[]>([]);
const caregiverShiftsLoadedFor = ref<number | null>(null);

async function loadCaregiverShifts(patientId: number) {
    caregiverShiftsLoading.value = true;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "caregiver_shifts",
            patient_id: patientId,
        });

        caregiverShifts.value = Array.isArray(res?.data?.caregiver_shifts)
            ? res.data.caregiver_shifts
            : [];

        caregiverShiftsLoadedFor.value = patientId;
    } catch (err) {
        console.error("Error loading caregiver shifts:", err);
        caregiverShifts.value = [];
    } finally {
        caregiverShiftsLoading.value = false;
    }
}

async function openCaregiverShifts() {
    showCaregiverShiftsModal.value = true;

    if (
        lovedOne.value.patient_id &&
        caregiverShiftsLoadedFor.value !== lovedOne.value.patient_id
    ) {
        await loadCaregiverShifts(lovedOne.value.patient_id);
    }
}

const latestDiagnosis = computed(() => lovedOne.value.diagnoses[0] ?? null);

const latestAssessmentDate = computed(
    () => lovedOne.value.assessments[0]?.recorded_at ?? null,
);

watch(selectedIndex, () => {
    showDiagnosisModal.value = false;
    showAssessmentModal.value = false;
});

function formatDate(
    value: string | null | undefined,
    fallback = "N/A",
): string {
    if (!value) return fallback;

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) return fallback;

    return date.toLocaleDateString("en-US", {
        month: "long",
        day: "2-digit",
        year: "numeric",
    });
}

function isValidPhone(value: string | null | undefined): boolean {
    if (!value || value === "N/A") return false;

    return value.replace(/\D/g, "").length >= 7;
}

function normalizeStatus(value: string | null | undefined): string {
    return String(value ?? "").toLowerCase();
}

function statusStyle(status: string) {
    const normalized = normalizeStatus(status);

    const map: Record<string, { badge: string }> = {
        admitted: {
            badge: "bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300",
        },
        waiting: {
            badge: "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300",
        },
        homecare: {
            badge: "bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-300",
        },
        discharged: {
            badge: "bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400",
        },
        inactive: {
            badge: "bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400",
        },
    };

    return (
        map[normalized] ?? {
            badge: "bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400",
        }
    );
}

function getAdmissionStatusLabel(status: string | null | undefined): string {
    switch (normalizeStatus(status)) {
        case "admitted":
            return "Admitted";
        case "waiting":
            return "Waiting to admit";
        case "cancelled":
            return "Cancelled";
        case "discharged":
            return "Discharged";
        default:
            return status || "N/A";
    }
}

function splitRecordsByCategory(rawRecords: any[] | null | undefined): {
    medications: Medication[];
    vitals: Vital[];
} {
    const records = Array.isArray(rawRecords) ? rawRecords : [];

    const medications: Medication[] = records
        .filter((entry) => ["Medication", "PRN"].includes(entry?.category))
        .map((entry) => ({
            ...entry,
            durationLabel: entry.duration ? `${entry.duration} Days` : "0 Days",
            schedules: Array.isArray(entry.schedules) ? entry.schedules : [],
        })) as Medication[];

    const vitals: Vital[] = records
        .filter((entry) => entry?.category === "Vital Signs")
        .map((entry) => ({ ...entry })) as Vital[];

    return {
        medications,
        vitals,
    };
}

function mapPatientRecord(item: any): LovedOne {
    const patient = item?.patient ?? {};
    const org = item?.organization ?? {};
    const ctx = item?.location_context ?? {};
    const access = item?.access ?? {};
    const client = item?.client ?? {};
    const admission = ctx?.admission ?? {};
    const bed = ctx?.bed ?? {};
    const room = ctx?.room ?? {};

    const status = normalizeStatus(ctx.status);

    const statusLabel =
        status === "admitted"
            ? "Admitted"
            : status === "waiting" || status === "cancelled"
              ? "Waiting"
              : status === "discharged"
                ? "Discharged"
                : normalizeStatus(ctx.type) === "homecare"
                  ? "Homecare"
                  : "Inactive";

    const roomNumber = room.room_no || null;
    const bedNumber = bed.bed_no || null;

    return {
        patient_id: patient.patient_id ?? 0,
        uuid: patient.uuid ?? null,
        name: patient.full_name || "Unnamed Resident",
        address: org.full_address || "N/A",
        branchName: org.name || "N/A",
        branchUuid: org.uuid || null,
        homeAddress: patient.full_address || "N/A",
        status: statusLabel,
        photo: patient.avatar || null,
        roomType: room.room_type || null,
        roomFloor: room.floor || null,
        roomLabel: roomNumber || "N/A",
        bedLabel: bedNumber || "N/A",
        birthdate: patient.date_of_birth
            ? formatDate(patient.date_of_birth)
            : "N/A",
        age: calculateAge(patient.date_of_birth, false),
        gender: patient.gender || "N/A",
        bloodType: patient.blood_type || "N/A",
        contactNumber: patient.phone_number || "N/A",
        relationship: access.relationship_type || "N/A",
        haveAccess: Boolean(access.have_access),
        client: {
            name:
                [client.first_name, client.last_name]
                    .filter(Boolean)
                    .join(" ") || "N/A",
            phone: formatPhone(client.phone_number) || "N/A",
            email: client.email || "N/A",
        },
        accessGrantedAt: access.granted_at
            ? formatDate(access.granted_at)
            : "N/A",
        emergencyContact: {
            name: "N/A",
            relation: access.relationship_type || "N/A",
            phone: "N/A",
        },
        admissionDate: admission.admitted_at
            ? formatDate(admission.admitted_at)
            : "N/A",
        admissionLabel:
            status === "waiting" || status === "cancelled"
                ? "Scheduled"
                : "Admitted",
        admissionStatus: admission.status
            ? getAdmissionStatusLabel(admission.status)
            : statusLabel,
        dischargeDate: admission.end_date
            ? formatDate(admission.end_date)
            : "N/A",
        assignedCaregiver: "N/A",
        conditions: Array.isArray(patient.conditions) ? patient.conditions : [],
        allergies: Array.isArray(patient.allergies) ? patient.allergies : [],
        assessments: Array.isArray(patient.assessments)
            ? patient.assessments
            : [],
        diagnoses: Array.isArray(patient.diagnoses) ? patient.diagnoses : [],
        ...splitRecordsByCategory(patient.medication),
    };
}

async function loadPatientData() {
    isLoading.value = true;
    loadError.value = null;
    noPatients.value = false;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "profile,details,recent_medication",
        });

        const records: any[] = Array.isArray(res?.data) ? res.data : [];

        if (records.length) {
            lovedOnes.value = records.map(mapPatientRecord);
            selectedIndex.value = resolveIndex(lovedOnes.value);
        } else {
            lovedOnes.value = [];
            noPatients.value = true;
        }
    } catch (err: any) {
        console.error("Error loading loved ones:", err);
        loadError.value = err?.message || "Failed to load patient data.";
    } finally {
        isLoading.value = false;
    }
}

const latestMedications = computed<Medication[]>(() => {
    const medications = lovedOne.value?.medications ?? [];

    return [...medications]
        .sort((a, b) => {
            const dateA = new Date(a.recorded_date ?? 0).getTime();
            const dateB = new Date(b.recorded_date ?? 0).getTime();

            return dateB - dateA;
        })
        .slice(0, 5);
});

const latestVitals = computed<Vital[]>(() => {
    const vitals = lovedOne.value?.vitals ?? [];

    return [...vitals]
        .sort((a, b) => {
            const dateA = new Date(a.recordedDate ?? 0).getTime();
            const dateB = new Date(b.recordedDate ?? 0).getTime();

            return dateB - dateA;
        })
        .slice(0, 5);
});

onMounted(async () => {
    await loadPatientData();
});
</script>
