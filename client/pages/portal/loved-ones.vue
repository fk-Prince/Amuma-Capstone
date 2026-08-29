<template>
    <div class="p-4 sm:p-6 lg:p-8 space-y-5">
        <div v-if="isLoading" class="space-y-5 animate-pulse">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-gray-200 shrink-0"></div>

                <div
                    class="flex-1 flex items-center gap-3 sm:gap-4 overflow-hidden px-1 py-1"
                >
                    <div
                        v-for="item in 5"
                        :key="item"
                        class="flex shrink-0 flex-col items-center gap-1.5"
                    >
                        <div
                            class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-200"
                        ></div>
                        <div class="h-3 w-12 rounded bg-gray-100"></div>
                    </div>
                </div>

                <div class="w-8 h-8 rounded-full bg-gray-200 shrink-0"></div>
            </div>

            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-6"
            >
                <div
                    class="flex flex-col sm:flex-row items-start gap-4 sm:gap-5"
                >
                    <div
                        class="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gray-200 shrink-0"
                    ></div>

                    <div class="flex-1 min-w-0 w-full space-y-3">
                        <div class="flex items-center gap-2">
                            <div
                                class="h-6 w-40 sm:w-48 rounded-md bg-gray-200"
                            ></div>
                            <div
                                class="h-5 w-20 rounded-full bg-gray-100"
                            ></div>
                        </div>

                        <div class="h-4 w-40 rounded bg-gray-100"></div>
                        <div
                            class="h-4 w-full max-w-md rounded bg-gray-100"
                        ></div>
                        <div class="h-4 w-32 rounded bg-gray-100"></div>

                        <div class="flex flex-wrap gap-2 pt-1">
                            <div
                                class="h-7 w-20 rounded-full bg-gray-100"
                            ></div>
                            <div
                                class="h-7 w-24 rounded-full bg-gray-100"
                            ></div>
                            <div
                                class="h-7 w-32 rounded-full bg-gray-100"
                            ></div>
                        </div>

                        <div class="flex flex-wrap gap-2 pt-1">
                            <div
                                class="h-9 w-36 rounded-full bg-gray-200"
                            ></div>
                            <div
                                class="h-9 w-28 rounded-full bg-gray-200"
                            ></div>
                            <div
                                class="h-9 w-28 rounded-full bg-gray-200"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div
                    v-for="card in 3"
                    :key="card"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
                >
                    <div class="h-4 w-36 rounded bg-gray-200 mb-5"></div>

                    <div class="space-y-5">
                        <div
                            v-for="row in 4"
                            :key="row"
                            class="flex items-start gap-3"
                        >
                            <div
                                class="w-8 h-8 rounded-lg bg-gray-100 shrink-0"
                            ></div>

                            <div class="flex-1 min-w-0 space-y-2 pt-1">
                                <div
                                    class="h-3.5 w-32 rounded bg-gray-200"
                                ></div>
                                <div class="h-3 w-24 rounded bg-gray-100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5"
                >
                    <div
                        class="flex items-center gap-1 bg-gray-50 rounded-lg p-1 w-fit"
                    >
                        <div class="h-7 w-24 rounded-md bg-gray-200"></div>
                        <div class="h-7 w-24 rounded-md bg-gray-100"></div>
                    </div>

                    <div class="h-4 w-14 rounded bg-gray-100"></div>
                </div>

                <div class="space-y-3">
                    <div class="px-2 sm:px-4 pt-2 space-y-2">
                        <div class="h-4 w-36 rounded bg-gray-200"></div>
                        <div
                            class="h-3 w-full max-w-md rounded bg-gray-100"
                        ></div>
                    </div>

                    <div class="px-2 sm:px-4">
                        <div class="h-10 rounded-lg bg-gray-50"></div>
                    </div>

                    <div
                        v-for="row in 4"
                        :key="row"
                        class="grid grid-cols-2 sm:grid-cols-4 gap-4 px-2 sm:px-4 py-4 border-b border-gray-50"
                    >
                        <div
                            class="h-3.5 w-28 max-w-full rounded bg-gray-100"
                        ></div>
                        <div
                            class="h-3.5 w-24 max-w-full rounded bg-gray-100"
                        ></div>
                        <div
                            class="hidden sm:block h-3.5 w-20 max-w-full rounded bg-gray-100"
                        ></div>
                        <div
                            class="hidden sm:block h-3.5 w-16 max-w-full rounded bg-gray-100"
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
            class="bg-white dark:bg-secondary rounded-2xl border border-gray-100 dark:border-white/10 shadow-sm p-8 sm:p-10 text-center"
        >
            <p class="text-sm text-rose-500">{{ loadError }}</p>

            <button
                type="button"
                @click="loadPatientData"
                class="mt-2 text-xs text-primary-600 font-medium hover:text-primary-700"
            >
                Retry
            </button>
        </div>

        <template v-else>
            <div
                v-if="lovedOnes.length > 1"
                class="flex items-center gap-2 min-w-0"
            >
                <button
                    type="button"
                    :disabled="selectedIndex === 0"
                    @click="scrollCarousel(-1)"
                    class="shrink-0 w-8 h-8 rounded-full border border-gray-200 bg-white flex items-center justify-center text-gray-400 hover:text-primary-600 hover:border-primary-300 transition-colors disabled:opacity-30 disabled:pointer-events-none"
                >
                    <ChevronLeft class="w-4 h-4" />
                </button>

                <div
                    ref="carouselRef"
                    class="no-scrollbar flex flex-1 min-w-0 items-center gap-3 sm:gap-4 overflow-x-auto scroll-smooth px-1 py-1"
                >
                    <button
                        v-for="(lo, idx) in lovedOnes"
                        :key="lo.patient_id"
                        :data-idx="idx"
                        type="button"
                        @click="selectedIndex = idx"
                        class="group flex shrink-0 flex-col items-center gap-1.5"
                    >
                        <span class="relative">
                            <img
                                :src="lo.photo"
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-full object-cover object-top border-2 transition-colors"
                                :class="
                                    selectedIndex === idx
                                        ? 'border-primary-500'
                                        : 'border-transparent group-hover:border-gray-200'
                                "
                                alt=""
                            />

                            <span
                                v-if="selectedIndex === idx"
                                class="absolute -bottom-0.5 -right-0.5 w-[18px] h-[18px] rounded-full bg-primary-500 border-2 border-white flex items-center justify-center"
                            >
                                <Check class="w-2.5 h-2.5 text-white" />
                            </span>
                        </span>

                        <span
                            class="max-w-[64px] truncate text-xs font-medium transition-colors"
                            :class="
                                selectedIndex === idx
                                    ? 'text-primary-600'
                                    : 'text-gray-500'
                            "
                        >
                            {{ lo.name.split(" ")[0] }}
                        </span>
                    </button>
                </div>

                <button
                    type="button"
                    :disabled="selectedIndex === lovedOnes.length - 1"
                    @click="scrollCarousel(1)"
                    class="shrink-0 w-8 h-8 rounded-full border border-gray-200 bg-white flex items-center justify-center text-gray-400 hover:text-primary-600 hover:border-primary-300 transition-colors disabled:opacity-30 disabled:pointer-events-none"
                >
                    <ChevronRight class="w-4 h-4" />
                </button>
            </div>

            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-6"
            >
                <div
                    class="flex flex-col sm:flex-row items-start gap-4 sm:gap-5"
                >
                    <div class="relative shrink-0">
                        <img
                            :src="lovedOne.photo"
                            class="w-20 h-20 sm:w-24 sm:h-24 rounded-full object-cover object-top"
                            alt=""
                        />

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
                                class="text-xl font-bold text-gray-900 break-words"
                            >
                                {{ lovedOne.name }}
                            </h2>

                            <span
                                class="px-2.5 py-0.5 rounded-full text-[11px] font-medium capitalize"
                                :class="
                                    statusStyle(lovedOne.admissionStatus).badge
                                "
                            >
                                {{ lovedOne.admissionStatus }}
                            </span>

                            <span
                                v-if="isVip"
                                class="px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-600 text-[11px] font-medium flex items-center gap-1"
                            >
                                <Crown class="w-3 h-3" />
                                VIP
                            </span>
                        </div>

                        <p
                            v-if="lovedOne.branchName !== 'N/A'"
                            class="text-sm font-medium text-primary-600 mt-1.5 flex items-start gap-1.5"
                        >
                            <Building2 class="w-3.5 h-3.5 shrink-0 mt-0.5" />
                            <span class="break-words">{{
                                lovedOne.branchName
                            }}</span>
                        </p>

                        <p
                            v-if="lovedOne.address !== 'N/A'"
                            class="text-sm text-gray-400 flex items-start gap-1.5 mt-0.5"
                        >
                            <MapPin class="w-3.5 h-3.5 shrink-0 mt-0.5" />
                            <span class="break-words">{{
                                lovedOne.address
                            }}</span>
                        </p>

                        <p
                            v-if="lovedOne.status === 'Admitted'"
                            class="text-sm text-primary-500 font-medium mt-1"
                        >
                            {{ lovedOne.roomLabel }} / {{ lovedOne.bedLabel }}
                        </p>

                        <p
                            v-else-if="lovedOne.status === 'Discharged'"
                            class="text-sm text-gray-500 font-medium mt-1"
                        >
                            Discharged {{ lovedOne.dischargeDate }}
                        </p>

                        <div class="flex items-center gap-2 flex-wrap mt-3">
                            <span
                                v-if="lovedOne.age"
                                class="px-2.5 py-1 rounded-full bg-gray-50 text-gray-600 text-xs"
                            >
                                {{ lovedOne.age }} yrs old
                            </span>

                            <span
                                v-if="lovedOne.bloodType !== 'N/A'"
                                class="px-2.5 py-1 rounded-full bg-gray-50 text-gray-600 text-xs"
                            >
                                {{ lovedOne.bloodType }} blood type
                            </span>

                            <span
                                v-if="lovedOne.admissionDate !== 'N/A'"
                                class="px-2.5 py-1 rounded-full bg-gray-50 text-gray-600 text-xs"
                            >
                                {{ lovedOne.admissionLabel }}
                                {{ lovedOne.admissionDate }}
                            </span>
                        </div>

                        <div class="flex items-center gap-2.5 flex-wrap mt-4">
                            <NuxtLink
                                to="/portal/messages"
                                class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-primary-500 text-primary-600 text-sm font-medium hover:bg-primary-500 hover:text-white transition-colors"
                            >
                                <MessageSquare class="w-3.5 h-3.5" />
                                Message Caregiver
                            </NuxtLink>

                            <NuxtLink
                                to="/portal/schedule"
                                class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-primary-500 text-primary-600 text-sm font-medium hover:bg-primary-500 hover:text-white transition-colors"
                            >
                                <Calendar class="w-3.5 h-3.5" />
                                Schedule
                            </NuxtLink>

                            <NuxtLink
                                to="/portal/balance"
                                class="flex items-center gap-1.5 px-4 py-2 rounded-full border border-primary-500 text-primary-600 text-sm font-medium hover:bg-primary-500 hover:text-white transition-colors"
                            >
                                <CreditCard class="w-3.5 h-3.5" />
                                View Billing
                            </NuxtLink>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                <div
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5 min-w-0"
                >
                    <p class="text-sm font-semibold text-gray-800 mb-4">
                        Personal Information
                    </p>

                    <ul class="space-y-4">
                        <li class="flex items-start gap-3 min-w-0">
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0"
                            >
                                <Cake class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 break-words"
                                >
                                    {{ lovedOne.birthdate }}
                                </p>
                                <p class="text-xs text-gray-400 break-words">
                                    {{ lovedOne.age }} years old ·
                                    {{ lovedOne.gender }}
                                </p>
                            </div>
                        </li>

                        <li class="flex items-start gap-3 min-w-0">
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0"
                            >
                                <Droplet class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 break-words"
                                >
                                    {{ lovedOne.bloodType }}
                                </p>
                                <p class="text-xs text-gray-400">Blood type</p>
                            </div>
                        </li>

                        <li class="flex items-start gap-3 min-w-0">
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0"
                            >
                                <Phone class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <a
                                    v-if="isValidPhone(lovedOne.contactNumber)"
                                    :href="`tel:${lovedOne.contactNumber}`"
                                    class="text-sm font-medium text-gray-800 hover:text-primary-600 transition-colors break-all"
                                >
                                    {{ lovedOne.contactNumber }}
                                </a>

                                <p
                                    v-else
                                    class="text-sm font-medium text-gray-800 break-words"
                                >
                                    {{ lovedOne.contactNumber }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    Contact number
                                </p>
                            </div>
                        </li>

                        <li class="flex items-start gap-3 min-w-0">
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0"
                            >
                                <Home class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 break-words"
                                >
                                    {{ lovedOne.homeAddress }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    Home address
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5 min-w-0"
                >
                    <p class="text-sm font-semibold text-gray-800 mb-4">
                        Patient Access & Emergency Contact
                    </p>

                    <div class="flex items-center gap-3 min-w-0">
                        <span
                            class="w-10 h-10 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center shrink-0"
                        >
                            <UserRound class="w-5 h-5" />
                        </span>

                        <div class="min-w-0">
                            <p
                                class="text-sm font-semibold text-gray-800 break-words"
                            >
                                {{ lovedOne.client.name }}
                            </p>
                            <p class="text-xs text-gray-400 break-words">
                                Your relationship to this patient ·
                                {{ lovedOne.relationship }}
                            </p>
                        </div>
                    </div>

                    <li class="flex items-start gap-3 min-w-0 mt-4">
                        <span
                            class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0"
                        >
                            <Phone class="w-4 h-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <a
                                v-if="isValidPhone(lovedOne.client.phone)"
                                :href="`tel:${lovedOne.client.phone}`"
                                class="text-sm font-medium text-gray-800 hover:text-primary-600 transition-colors break-all"
                            >
                                <span class="break-all">{{
                                    lovedOne.client.phone
                                }}</span>
                            </a>

                            <p
                                v-else
                                class="text-sm font-medium text-gray-800 break-words"
                            >
                                {{ lovedOne.contactNumber }}
                            </p>
                            <p class="text-xs text-gray-400">Contact number</p>
                        </div>
                    </li>

                    <ul class="mt-4 space-y-3">
                        <li
                            v-if="lovedOne.client.email !== 'N/A'"
                            class="flex items-start gap-3 min-w-0"
                        >
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0"
                            >
                                <Mail class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 break-all"
                                >
                                    {{ lovedOne.client.email }}
                                </p>
                                <p class="text-xs text-gray-400">Email</p>
                            </div>
                        </li>

                        <li
                            v-if="lovedOne.accessGrantedAt !== 'N/A'"
                            class="flex items-start gap-3 min-w-0"
                        >
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0"
                            >
                                <ShieldCheck class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800">
                                    {{
                                        lovedOne.haveAccess
                                            ? "Full access"
                                            : "Access revoked"
                                    }}
                                </p>
                                <p class="text-xs text-gray-400 break-words">
                                    Granted {{ lovedOne.accessGrantedAt }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5 min-w-0"
                >
                    <p class="text-sm font-semibold text-gray-800 mb-4">
                        Care Details
                    </p>

                    <ul class="space-y-4">
                        <li
                            v-if="lovedOne.admissionDate !== 'N/A'"
                            class="flex items-start gap-3 min-w-0"
                        >
                            <span
                                class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                :class="
                                    lovedOne.status === 'Discharged'
                                        ? 'bg-gray-100 text-gray-500'
                                        : 'bg-primary-50 text-primary-600'
                                "
                            >
                                <Calendar class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 break-words"
                                >
                                    {{ lovedOne.admissionDate }}
                                </p>
                                <p class="text-xs text-gray-400 break-words">
                                    {{
                                        lovedOne.status === "Discharged"
                                            ? `Discharged ${lovedOne.dischargeDate}`
                                            : lovedOne.admissionStatus
                                    }}
                                </p>
                            </div>
                        </li>

                        <li
                            v-if="lovedOne.status === 'Admitted'"
                            class="flex items-start gap-3 min-w-0"
                        >
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0"
                            >
                                <BedDouble class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 break-words"
                                >
                                    {{ lovedOne.roomLabel }}
                                </p>
                                <p class="text-xs text-gray-400 break-words">
                                    Room {{ lovedOne.roomType }}
                                    <span v-if="lovedOne.roomFloor">
                                        · Floor {{ lovedOne.roomFloor }}
                                    </span>
                                </p>
                            </div>
                        </li>

                        <li
                            v-else-if="lovedOne.status === 'Homecare'"
                            class="flex items-start gap-3 min-w-0"
                        >
                            <span
                                class="w-8 h-8 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center shrink-0"
                            >
                                <Home class="w-4 h-4" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm font-medium text-gray-800 break-words"
                                >
                                    {{ lovedOne.homeAddress }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    Homecare address
                                </p>
                            </div>
                        </li>
                    </ul>

                    <p class="text-sm font-semibold text-gray-800 mt-6 mb-3">
                        Allergies
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="a in lovedOne.allergies"
                            :key="a"
                            class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-600 text-xs font-medium max-w-full break-words"
                        >
                            {{ a }}
                        </span>

                        <span
                            v-if="!lovedOne.allergies.length"
                            class="text-xs text-gray-400"
                        >
                            No known allergies
                        </span>
                    </div>

                    <p class="text-sm font-semibold text-gray-800 mt-6 mb-3">
                        Conditions
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="c in lovedOne.conditions"
                            :key="c"
                            class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 text-xs font-medium max-w-full break-words"
                        >
                            {{ c }}
                        </span>

                        <span
                            v-if="!lovedOne.conditions.length"
                            class="text-xs text-gray-400"
                        >
                            No recorded conditions
                        </span>
                    </div>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5 min-w-0"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4"
                >
                    <div
                        class="flex items-center gap-1 bg-gray-50 rounded-lg p-1 w-fit max-w-full"
                    >
                        <button
                            type="button"
                            @click="activeHealthTab = 'medications'"
                            class="px-3 py-1.5 rounded-md text-xs font-medium transition-colors whitespace-nowrap"
                            :class="
                                activeHealthTab === 'medications'
                                    ? 'bg-white text-primary-600 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700'
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
                                    ? 'bg-white text-primary-600 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700'
                            "
                        >
                            Vital Signs
                        </button>
                    </div>

                    <NuxtLink
                        v-if="activeHealthTab === 'medications'"
                        to="/portal/medications"
                        class="text-xs text-primary-600 font-medium whitespace-nowrap"
                    >
                        View All
                    </NuxtLink>

                    <NuxtLink
                        v-else
                        to="/portal/vitals"
                        class="text-xs text-primary-600 font-medium whitespace-nowrap"
                    >
                        View All
                    </NuxtLink>
                </div>

                <div v-if="medicationsLoading" class="space-y-3 animate-pulse">
                    <div class="px-2 sm:px-4 pt-4 space-y-2">
                        <div class="h-4 w-36 rounded bg-gray-200"></div>
                        <div
                            class="h-3 w-full max-w-md rounded bg-gray-100"
                        ></div>
                    </div>

                    <div class="px-2 sm:px-4">
                        <div class="h-10 rounded-lg bg-gray-50"></div>
                    </div>

                    <div
                        v-for="row in 4"
                        :key="row"
                        class="grid grid-cols-2 sm:grid-cols-4 gap-4 px-2 sm:px-4 py-4 border-b border-gray-50"
                    >
                        <div
                            class="h-3.5 w-28 max-w-full rounded bg-gray-100"
                        ></div>
                        <div
                            class="h-3.5 w-24 max-w-full rounded bg-gray-100"
                        ></div>
                        <div
                            class="hidden sm:block h-3.5 w-20 max-w-full rounded bg-gray-100"
                        ></div>
                        <div
                            class="hidden sm:block h-3.5 w-16 max-w-full rounded bg-gray-100"
                        ></div>
                    </div>
                </div>

                <div
                    v-else-if="activeHealthTab === 'medications'"
                    class="space-y-3 min-w-0"
                >
                    <div class="px-2 sm:px-4 pt-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Latest Medications
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Recently recorded medications for this client.
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
                        class="mx-2 sm:mx-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center"
                    >
                        <p class="text-sm font-medium text-gray-500">
                            No medications recorded
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            Medication records will appear here once added.
                        </p>
                    </div>
                </div>

                <div v-else class="space-y-3 min-w-0">
                    <div class="px-2 sm:px-4 pt-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Latest Vital Signs
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Most recently recorded vital signs for this client.
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
                        class="mx-2 sm:mx-4 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center"
                    >
                        <p class="text-sm font-medium text-gray-500">
                            No vital signs recorded
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            Vital sign records will appear here once added.
                        </p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from "vue";
import MedicationTable from "~/components/sections/app/Patient/MedicationTable.vue";
import VitalSignsTable from "~/components/sections/app/Patient/VitalSignsTable.vue";
import EmptyState from "~/components/ui/EmptyState.vue";
import {
    Pencil,
    Crown,
    Building2,
    MapPin,
    MessageSquare,
    Calendar,
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
    Check,
} from "lucide-vue-next";
import { patientAccessService } from "../../api/patient-access/PatientAccessService";
import type { Medication } from "~/types/medication";
import type { Vital } from "~/types/medication";

useHead({ title: "My Loved Ones" });

definePageMeta({
    layout: "portal",
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
    facilityPeriod: {
        start_date: string | null;
        end_date: string | null;
    };
    latestPayment: Payment | null;
}

interface LovedOne {
    patient_id: number;
    name: string;
    address: string;
    branchName: string;
    homeAddress: string;
    status: string;
    photo: string;
    roomLabel: string;
    bedLabel: string;
    roomType: string | null;
    roomFloor: string | null;
    birthdate: string;
    age: number;
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
    medications: Medication[];
    vitals: Vital[];
}

const PLACEHOLDER_PHOTO = "https://placehold.co/200x200?text=Photo";

function fallbackLovedOne(): LovedOne {
    return {
        patient_id: 0,
        name: "Unnamed Resident",
        address: "N/A",
        branchName: "N/A",
        homeAddress: "N/A",
        status: "Unknown",
        photo: PLACEHOLDER_PHOTO,
        roomLabel: "N/A",
        bedLabel: "N/A",
        roomType: null,
        roomFloor: null,
        birthdate: "N/A",
        age: 0,
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
const carouselRef = ref<HTMLElement | null>(null);

function scrollCarousel(direction: number) {
    const next = selectedIndex.value + direction;

    if (next < 0 || next >= lovedOnes.value.length) {
        return;
    }

    selectedIndex.value = next;
}

watch(selectedIndex, (idx) => {
    nextTick(() => {
        const target = carouselRef.value?.querySelector(
            `[data-idx="${idx}"]`,
        ) as HTMLElement | null;

        target?.scrollIntoView({
            behavior: "smooth",
            inline: "center",
            block: "nearest",
        });
    });
});

const lovedOne = computed(
    () => lovedOnes.value[selectedIndex.value] ?? fallbackLovedOne(),
);

const isVip = computed(() => lovedOne.value.roomType?.toLowerCase() === "vip");

function calcAge(dob: string | null | undefined): number {
    if (!dob) return 0;

    const birth = new Date(dob);

    if (Number.isNaN(birth.getTime())) return 0;

    const now = new Date();

    let age = now.getFullYear() - birth.getFullYear();

    const monthDiff = now.getMonth() - birth.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) {
        age--;
    }

    return Math.max(0, age);
}

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
            badge: "bg-emerald-50 text-emerald-600",
        },
        waiting: {
            badge: "bg-amber-50 text-amber-600",
        },
        homecare: {
            badge: "bg-primary-50 text-primary-600",
        },
        discharged: {
            badge: "bg-gray-100 text-gray-500",
        },
        inactive: {
            badge: "bg-gray-100 text-gray-500",
        },
    };

    return (
        map[normalized] ?? {
            badge: "bg-gray-100 text-gray-500",
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
        name: patient.full_name || "Unnamed Resident",
        address: org.full_address || "N/A",
        branchName: org.name || "N/A",
        homeAddress: patient.full_address || "N/A",
        status: statusLabel,
        photo: patient.avatar || PLACEHOLDER_PHOTO,
        roomType: room.room_type || null,
        roomFloor: room.floor || null,
        roomLabel: roomNumber || "N/A",
        bedLabel: bedNumber || "N/A",
        birthdate: patient.date_of_birth
            ? formatDate(patient.date_of_birth)
            : "N/A",
        age: calcAge(patient.date_of_birth),
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
            phone: client.phone_number || "N/A",
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
        medications: [],
        vitals: [],
    };
}

async function loadPatientData() {
    isLoading.value = true;
    loadError.value = null;
    noPatients.value = false;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "profile",
        });

        const records: any[] = Array.isArray(res?.data) ? res.data : [];

        if (records.length) {
            lovedOnes.value = records.map(mapPatientRecord);
            selectedIndex.value = 0;
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

const medicationsLoading = ref(false);
const medicationsLoadedFor = ref<number | null>(null);

async function loadMedicationData(patientId: number) {
    if (!patientId || medicationsLoadedFor.value === patientId) return;

    medicationsLoading.value = true;

    try {
        const res = await patientAccessService.retrieveAction({
            action: "overview",
            section: "medication",
            patient_id: patientId,
            latest_only: true,
        });

        const { medications, vitals } = splitRecordsByCategory(
            res?.data?.patient?.medication,
        );

        const target = lovedOnes.value.find(
            (lo) => lo.patient_id === patientId,
        );

        if (target) {
            target.medications = medications;
            target.vitals = vitals;
        }

        medicationsLoadedFor.value = patientId;
    } catch (err) {
        console.error("Error loading medication data:", err);
    } finally {
        medicationsLoading.value = false;
    }
}

watch(selectedIndex, () => {
    if (lovedOne.value.patient_id) {
        loadMedicationData(lovedOne.value.patient_id);
    }
});

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

    if (lovedOne.value.patient_id) {
        await loadMedicationData(lovedOne.value.patient_id);
    }
});
</script>

<style scoped>
.no-scrollbar {
    scrollbar-width: none;
    -ms-overflow-style: none;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
