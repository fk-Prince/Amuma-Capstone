<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="branchStore.showModal"
                class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-primary-900/50 p-3 backdrop-blur-sm sm:items-center sm:p-4"
                @click.self="branchStore.closeModal"
            >
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-2 scale-95 opacity-0"
                    enter-to-class="translate-y-0 scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div
                        v-if="branchStore.showModal"
                        class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-[0_0_40px_rgba(10,40,87,0.15)] ring-1 ring-primary-100/60 dark:bg-secondary dark:ring-primary-500/20"
                        role="dialog"
                        aria-modal="true"
                        :aria-label="
                            agencyStep ? 'Select an agency' : 'Select a branch'
                        "
                    >
                        <div
                            class="flex items-start justify-between gap-3 border-b border-primary-100/80 bg-primary-50/40 px-4 py-3.5 sm:px-5 sm:py-4 dark:border-primary-500/20 dark:bg-primary-500/10"
                        >
                            <div
                                class="flex min-w-0 items-center gap-2.5 sm:gap-3"
                            >
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary sm:h-10 sm:w-10"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        class="h-4.5 w-4.5 sm:h-5 sm:w-5"
                                    >
                                        <path
                                            d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                                        />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <h2
                                        class="truncate text-sm font-semibold leading-tight text-primary-900 dark:text-white sm:text-base"
                                    >
                                        {{
                                            agencyStep
                                                ? "Select an agency"
                                                : "Select a branch"
                                        }}
                                    </h2>
                                    <p
                                        class="mt-0.5 truncate text-[11px] text-muted dark:text-gray-400 sm:text-xs"
                                    >
                                        {{
                                            agencyStep
                                                ? "Choose which agency you want to manage"
                                                : "Choose which branch you want to manage"
                                        }}
                                    </p>
                                </div>
                            </div>

                            <button
                                v-if="branchStore.activeBranch"
                                type="button"
                                aria-label="Close dialog"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-primary-400 transition-colors duration-200 hover:bg-primary-100 hover:text-primary-700 dark:hover:bg-primary-500/15 dark:hover:text-primary-300"
                                @click="branchStore.closeModal"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="h-4 w-4"
                                >
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                            </button>
                        </div>

                        <div
                            v-if="agencyStep"
                            class="flex flex-wrap items-center gap-1 border-b border-primary-100/70 px-3 py-2 sm:px-4 dark:border-primary-500/20"
                        >
                            <button
                                v-for="filter in statusFilters"
                                :key="filter.value"
                                type="button"
                                class="inline-flex min-w-[72px] items-center justify-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[11px] font-medium transition-colors sm:min-w-[84px] sm:text-xs"
                                :class="
                                    statusFilter === filter.value
                                        ? 'bg-primary text-white shadow-sm'
                                        : 'text-muted hover:bg-primary-50 hover:text-primary-700 dark:text-gray-400 dark:hover:bg-primary-500/10 dark:hover:text-primary-300'
                                "
                                @click="statusFilter = filter.value"
                            >
                                <component
                                    :is="filter.icon"
                                    class="h-3.5 w-3.5 shrink-0"
                                    :class="
                                        statusFilter === filter.value
                                            ? ''
                                            : filter.iconClass
                                    "
                                />
                                {{ filter.label }}
                            </button>
                        </div>

                        <div
                            v-if="agencyStep"
                            class="branch-scroll grid content-start items-start max-h-[calc(100vh-11rem)] gap-2.5 overflow-y-auto p-2.5 sm:min-h-[22rem] sm:max-h-[40rem] sm:grid-cols-2 sm:p-3"
                        >
                            <button
                                v-for="agency in filteredAgencies"
                                :key="agency.agency_id"
                                type="button"
                                class="group flex h-[72px] w-full min-w-0 items-center gap-3 rounded-xl border p-3 text-left transition-all duration-200 sm:p-3.5"
                                :class="
                                    agency.agency_id ===
                                    branchStore.activeBranch?.agency?.agency_id
                                        ? 'border-primary-300 bg-primary-50 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:ring-primary-500/20'
                                        : 'border-slate-200 hover:border-primary-200 hover:bg-primary-50/70 dark:border-white/10 dark:hover:border-primary-500/20 dark:hover:bg-primary-500/10'
                                "
                                @click="
                                    selectedAgencyId = agency.agency_id ?? null
                                "
                            >
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-primary-50 ring-2 ring-primary-100 dark:bg-primary-500/10 dark:ring-primary-500/20"
                                >
                                    <img
                                        v-if="
                                            agency.image &&
                                            !brokenImages.has(
                                                `agency-${agency.agency_id}`,
                                            )
                                        "
                                        :src="getBranchImage(agency.image)"
                                        :alt="agency.name"
                                        class="h-full w-full object-cover"
                                        @error="
                                            brokenImages.add(
                                                `agency-${agency.agency_id}`,
                                            )
                                        "
                                    />

                                    <Building2
                                        v-else
                                        class="h-5 w-5 text-primary-400 dark:text-primary-300"
                                    />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p
                                        class="flex min-w-0 items-center gap-1 text-[13px] font-semibold text-primary-900 dark:text-white sm:text-sm"
                                    >
                                        <span class="min-w-0 truncate">
                                            {{ agency.name }}
                                        </span>

                                        <BadgeCheck
                                            v-if="agency.status === 'verified'"
                                            class="h-3.5 w-3.5 shrink-0 text-emerald-500 dark:text-emerald-300"
                                        />
                                        <Clock
                                            v-else-if="
                                                agency.status === 'pending'
                                            "
                                            class="h-3.5 w-3.5 shrink-0 text-amber-500 dark:text-amber-400"
                                        />
                                        <Ban
                                            v-else-if="
                                                agency.status === 'rejected'
                                            "
                                            class="h-3.5 w-3.5 shrink-0 text-rose-500 dark:text-rose-400"
                                        />
                                    </p>

                                    <p
                                        class="mt-0.5 text-[11px] text-muted dark:text-gray-400 sm:text-xs"
                                    >
                                        {{ agency.branches.length }}
                                        {{
                                            agency.branches.length === 1
                                                ? "branch"
                                                : "branches"
                                        }}
                                    </p>
                                </div>

                                <ChevronRight
                                    class="h-4 w-4 shrink-0 text-primary-300"
                                />
                            </button>
                        </div>

                        <template v-else>
                            <div
                                v-if="currentAgency"
                                class="border-b border-primary-100/70 px-4 py-3.5 sm:px-5 dark:border-primary-500/20"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-primary-100 dark:bg-secondary dark:ring-primary-500/20"
                                    >
                                        <img
                                            v-if="
                                                currentAgency.image &&
                                                !brokenImages.has(
                                                    `agency-${currentAgency.agency_id}`,
                                                )
                                            "
                                            :src="
                                                getBranchImage(
                                                    currentAgency.image,
                                                )
                                            "
                                            :alt="currentAgency.name"
                                            class="h-full w-full object-cover"
                                            @error="
                                                brokenImages.add(
                                                    `agency-${currentAgency.agency_id}`,
                                                )
                                            "
                                        />

                                        <Building2
                                            v-else
                                            class="h-5 w-5 text-primary-400 dark:text-primary-300"
                                        />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="flex min-w-0 items-center gap-1 text-sm font-semibold text-primary-900 dark:text-white"
                                        >
                                            <span class="truncate">
                                                {{ currentAgency.name }}
                                            </span>
                                            <BadgeCheck
                                                v-if="
                                                    currentAgency.status ===
                                                    'verified'
                                                "
                                                class="h-4 w-4 shrink-0 text-emerald-500 dark:text-emerald-300"
                                            />
                                        </p>

                                        <p
                                            v-if="currentAgency.email"
                                            class="mt-0.5 flex min-w-0 items-center gap-1.5 text-[11px] text-muted sm:text-xs dark:text-gray-400"
                                        >
                                            <Mail
                                                class="h-3.5 w-3.5 shrink-0 text-primary-300"
                                            />
                                            <span class="truncate">
                                                {{ currentAgency.email }}
                                            </span>
                                        </p>
                                    </div>

                                    <div
                                        class="flex min-w-0 max-w-[45%] shrink-0 flex-col items-end gap-1.5"
                                    >
                                        <p
                                            v-if="currentAgency.registered_by"
                                            class="flex min-w-0 items-center gap-1.5 text-[11px] text-muted sm:text-xs dark:text-gray-400"
                                        >
                                            <User
                                                class="h-3.5 w-3.5 shrink-0 text-primary-300"
                                            />
                                            <span class="truncate">
                                                Registered by
                                                <span
                                                    class="font-medium text-primary-900 dark:text-white"
                                                >
                                                    {{
                                                        currentAgency
                                                            .registered_by
                                                            .name ||
                                                        currentAgency
                                                            .registered_by.email
                                                    }}
                                                </span>
                                            </span>
                                        </p>

                                        <button
                                            v-if="hasMultipleAgencies"
                                            type="button"
                                            class="rounded-lg border border-primary-100 px-2.5 py-1.5 text-xs font-medium text-primary-600 transition-colors hover:bg-primary-50 dark:border-primary-500/20 dark:text-primary-300 dark:hover:bg-primary-500/10"
                                            @click="selectedAgencyId = null"
                                        >
                                            Change agency
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex flex-wrap items-center gap-1 border-b border-primary-100/70 px-3 py-2 sm:px-4 dark:border-primary-500/20"
                            >
                                <button
                                    v-for="filter in statusFilters"
                                    :key="filter.value"
                                    type="button"
                                    class="inline-flex min-w-[72px] items-center justify-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[11px] font-medium transition-colors sm:min-w-[84px] sm:text-xs"
                                    :class="
                                        statusFilter === filter.value
                                            ? 'bg-primary text-white shadow-sm'
                                            : 'text-muted hover:bg-primary-50 hover:text-primary-700 dark:text-gray-400 dark:hover:bg-primary-500/10 dark:hover:text-primary-300'
                                    "
                                    @click="statusFilter = filter.value"
                                >
                                    <component
                                        :is="filter.icon"
                                        class="h-3.5 w-3.5 shrink-0"
                                        :class="
                                            statusFilter === filter.value
                                                ? ''
                                                : filter.iconClass
                                        "
                                    />
                                    {{ filter.label }}
                                </button>
                            </div>

                            <div
                                v-if="filteredBranchesForView.length"
                                class="branch-scroll grid content-start items-start max-h-[calc(100vh-11rem)] gap-2.5 overflow-y-auto p-2.5 sm:min-h-[22rem] sm:max-h-[40rem] sm:grid-cols-2 sm:p-3"
                            >
                                <button
                                    v-for="branch in filteredBranchesForView"
                                    :key="branch.uuid"
                                    type="button"
                                    class="group w-full min-w-0 rounded-xl border p-3 text-left transition-all duration-200 sm:p-3.5"
                                    :class="[
                                        branch.uuid ===
                                        branchStore.activeBranch?.uuid
                                            ? 'border-primary-300 bg-primary-50 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:ring-primary-500/20'
                                            : 'border-slate-200 hover:border-primary-200 hover:bg-primary-50/70 dark:border-white/10 dark:hover:border-primary-500/20 dark:hover:bg-primary-500/10',
                                        branch.status === 'rejected'
                                            ? 'opacity-70'
                                            : '',
                                    ]"
                                    @click="branchStore.selectBranch(branch)"
                                >
                                    <div
                                        class="flex w-full min-w-0 items-start gap-2.5 sm:gap-3"
                                    >
                                        <div
                                            class="relative h-10 w-10 shrink-0 overflow-hidden rounded-xl bg-primary-50 ring-2 ring-primary-100 transition-transform duration-200 group-hover:scale-[1.03] sm:h-11 sm:w-11 dark:bg-primary-500/10 dark:ring-primary-500/20"
                                        >
                                            <img
                                                v-if="
                                                    branch.image &&
                                                    !brokenImages.has(
                                                        branch.uuid ?? '',
                                                    )
                                                "
                                                :src="
                                                    getBranchImage(branch.image)
                                                "
                                                :alt="branch.name"
                                                class="h-full w-full object-cover"
                                                @error="
                                                    brokenImages.add(
                                                        branch.uuid ?? '',
                                                    )
                                                "
                                            />

                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.5"
                                                    class="h-5 w-5 text-primary-400"
                                                >
                                                    <path
                                                        d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                                                    />
                                                </svg>
                                            </div>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex min-w-0 items-start justify-between gap-2"
                                            >
                                                <p
                                                    class="flex min-w-0 flex-1 items-center gap-1 text-[13px] font-semibold text-primary-900 dark:text-white sm:text-sm"
                                                >
                                                    <span
                                                        class="min-w-0 truncate"
                                                    >
                                                        {{ branch.name }}
                                                    </span>

                                                    <BadgeCheck
                                                        v-if="
                                                            branch.status ===
                                                            'verified'
                                                        "
                                                        class="h-3.5 w-3.5 shrink-0 text-emerald-500 dark:text-emerald-300"
                                                    />
                                                    <Clock
                                                        v-else-if="
                                                            branch.status ===
                                                            'pending'
                                                        "
                                                        class="h-3.5 w-3.5 shrink-0 text-amber-500 dark:text-amber-400"
                                                    />
                                                    <Ban
                                                        v-else-if="
                                                            branch.status ===
                                                            'rejected'
                                                        "
                                                        class="h-3.5 w-3.5 shrink-0 text-rose-500 dark:text-rose-400"
                                                    />
                                                </p>

                                                <span
                                                    v-if="
                                                        branch.uuid ===
                                                        branchStore.activeBranch
                                                            ?.uuid
                                                    "
                                                    class="shrink-0 rounded-full bg-primary-100 px-1.5 py-0.5 text-[9px] font-semibold text-primary-600 sm:px-2 sm:text-[10px] dark:bg-primary-500/15 dark:text-primary-300"
                                                >
                                                    Selected
                                                </span>
                                                <span
                                                    v-else-if="
                                                        branch.status ===
                                                        'pending'
                                                    "
                                                    class="shrink-0 rounded-full bg-amber-50 px-1.5 py-0.5 text-[9px] font-semibold text-amber-600 sm:px-2 sm:text-[10px] dark:bg-amber-500/10 dark:text-amber-400"
                                                >
                                                    Pending
                                                </span>
                                                <span
                                                    v-else-if="
                                                        branch.status ===
                                                        'rejected'
                                                    "
                                                    class="shrink-0 rounded-full bg-rose-50 px-1.5 py-0.5 text-[9px] font-semibold text-rose-600 sm:px-2 sm:text-[10px] dark:bg-rose-500/10 dark:text-rose-400"
                                                >
                                                    Rejected
                                                </span>
                                            </div>

                                            <p
                                                class="mt-1 flex min-h-[2rem] w-full min-w-0 items-center gap-1.5 text-[11px] leading-4 text-muted dark:text-gray-400 sm:text-xs"
                                            >
                                                <Location
                                                    class="h-3.5 w-3.5 shrink-0"
                                                />

                                                <span
                                                    class="min-w-0 flex-1 overflow-hidden text-ellipsis line-clamp-2"
                                                >
                                                    {{
                                                        branch.location
                                                            ?.address ??
                                                        "No address on file"
                                                    }}
                                                </span>
                                            </p>

                                            <div
                                                class="mt-2 flex min-w-0 items-start justify-between gap-2"
                                            >
                                                <div
                                                    class="flex min-w-0 flex-1 flex-wrap gap-1"
                                                >
                                                    <span
                                                        v-for="plan in branch.plan"
                                                        :key="plan.plan_code"
                                                        class="rounded-full border px-1.5 py-0.5 text-[10px] font-medium sm:px-2 sm:text-[11px]"
                                                        :class="{
                                                            'border-primary-200 bg-primary-50 text-primary dark:border-primary-500/20 dark:bg-primary-500/10':
                                                                plan.plan_code ===
                                                                'A',
                                                            'border-green-200 bg-green-50 text-accent':
                                                                plan.plan_code ===
                                                                'B',
                                                            'border-orange-200 bg-orange-50 text-secondary':
                                                                plan.plan_code ===
                                                                'C',
                                                        }"
                                                    >
                                                        {{ plan.name }}
                                                    </span>
                                                </div>

                                                <span
                                                    class="max-w-[45%] shrink-0 truncate rounded-full border px-1.5 py-0.5 text-[10px] font-medium sm:max-w-[50%] sm:px-2 sm:text-[11px]"
                                                    :class="
                                                        roleMeta[
                                                            branch?.role_name ??
                                                                ''
                                                        ]?.class ||
                                                        'bg-primary-50 text-primary-600 border-primary-200 dark:bg-primary-500/10 dark:text-primary-300 dark:border-primary-500/20'
                                                    "
                                                >
                                                    {{
                                                        formatRole(
                                                            branch?.role_name ??
                                                                "",
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            class="mt-1 hidden h-4 w-4 shrink-0 text-primary-300 opacity-0 transition-all duration-200 group-hover:translate-x-0 group-hover:opacity-100 sm:block sm:-translate-x-1"
                                        >
                                            <polyline points="9 18 15 12 9 6" />
                                        </svg>
                                    </div>

                                    <div
                                        class="mt-2.5 grid w-full min-w-0 grid-cols-1 gap-x-3 gap-y-1 border-t border-primary-100/70 pt-2.5 text-[10px] text-muted dark:text-gray-400 sm:grid-cols-2 sm:text-[11px] dark:border-primary-500/20"
                                    >
                                        <span
                                            v-if="branch.contact_number"
                                            class="flex min-w-0 items-center gap-1.5"
                                        >
                                            <Phone
                                                class="h-3 w-3 shrink-0 text-primary-300"
                                            />
                                            <span class="min-w-0 truncate">
                                                {{ branch.contact_number }}
                                            </span>
                                        </span>

                                        <span
                                            v-if="branch.email"
                                            class="flex min-w-0 items-center gap-1.5"
                                        >
                                            <Mail
                                                class="h-3 w-3 shrink-0 text-primary-300"
                                            />
                                            <span class="min-w-0 truncate">
                                                {{ branch.email }}
                                            </span>
                                        </span>
                                    </div>
                                </button>
                            </div>

                            <div
                                v-else
                                class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center sm:min-h-[22rem]"
                            >
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-50 text-primary-400 dark:bg-primary-500/10"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        class="h-5 w-5"
                                    >
                                        <path
                                            d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01"
                                        />
                                    </svg>
                                </div>

                                <p
                                    class="text-sm font-medium text-primary-900 dark:text-white"
                                >
                                    {{
                                        statusFilter === "all"
                                            ? "No branches yet"
                                            : "No matching branches"
                                    }}
                                </p>

                                <p
                                    class="max-w-[220px] text-xs text-muted dark:text-gray-400"
                                >
                                    {{
                                        statusFilter === "all"
                                            ? "You don't have access to any branches at the moment."
                                            : `No branches with a "${statusFilter}" status in this agency.`
                                    }}
                                </p>
                            </div>
                        </template>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import Location from "../icons/location.vue";
import {
    BadgeCheck,
    Ban,
    Building2,
    ChevronRight,
    Clock,
    LayoutGrid,
    Mail,
    Phone,
    User,
} from "lucide-vue-next";
import { useBranchStore } from "~/stores/branch";
import { formatRole, roleMeta } from "~/utils/user";
import { getBranchImage } from "~/types/branch.js";

const branchStore = useBranchStore();

const brokenImages = reactive(new Set<string>());
const selectedAgencyId = ref<number | null>(null);

const selectableAgencies = computed(() =>
    branchStore.agencies.filter((agency) => agency.branches.length),
);

const hasMultipleAgencies = computed(() => selectableAgencies.value.length > 1);

const agencyStep = computed(
    () => hasMultipleAgencies.value && selectedAgencyId.value === null,
);

const currentAgency = computed(() =>
    hasMultipleAgencies.value
        ? selectableAgencies.value.find(
              (agency) => agency.agency_id === selectedAgencyId.value,
          )
        : selectableAgencies.value[0],
);

const branchesForView = computed(() => currentAgency.value?.branches ?? []);

const statusFilters = [
    { label: "All", value: "all", icon: LayoutGrid, iconClass: "" },
    {
        label: "Verified",
        value: "verified",
        icon: BadgeCheck,
        iconClass: "text-emerald-500 dark:text-emerald-300",
    },
    {
        label: "Pending",
        value: "pending",
        icon: Clock,
        iconClass: "text-amber-500 dark:text-amber-400",
    },
    {
        label: "Rejected",
        value: "rejected",
        icon: Ban,
        iconClass: "text-rose-500 dark:text-rose-400",
    },
] as const;

const statusFilter = ref<(typeof statusFilters)[number]["value"]>("verified");

const filteredAgencies = computed(() =>
    statusFilter.value === "all"
        ? selectableAgencies.value
        : selectableAgencies.value.filter(
              (agency) => agency.status === statusFilter.value,
          ),
);

const filteredBranchesForView = computed(() =>
    statusFilter.value === "all"
        ? branchesForView.value
        : branchesForView.value.filter(
              (branch) => branch.status === statusFilter.value,
          ),
);

watch(
    () => branchStore.showModal,
    (open) => {
        if (open) {
            selectedAgencyId.value = null;
            statusFilter.value = "all";
        }
    },
);
</script>
