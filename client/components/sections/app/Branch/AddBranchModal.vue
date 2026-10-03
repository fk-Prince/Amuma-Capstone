<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
        >
            <Transition
                appear
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95 translate-y-2"
                enter-to-class="opacity-100 scale-100 translate-y-0"
            >
                <div
                    class="flex max-h-[94vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Add branch"
                >
                    <div
                        class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <Building2 class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <h2
                                    class="text-lg font-semibold leading-tight text-gray-900 dark:text-white"
                                >
                                    Add a new branch
                                </h2>

                                <p
                                    class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Connecting to
                                    <span
                                        class="font-medium text-secondary dark:text-white"
                                    >
                                        {{ agencyName }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            aria-label="Close dialog"
                            class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                            :disabled="processing"
                            @click="requestClose"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div
                        class="shrink-0 border-b border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <ol class="flex w-full items-start">
                            <li
                                v-for="(step, index) in STEPS"
                                :key="step"
                                :class="[
                                    'flex items-start',
                                    index < STEPS.length - 1
                                        ? 'flex-1'
                                        : 'shrink-0',
                                ]"
                            >
                                <div
                                    class="flex shrink-0 flex-col items-center"
                                >
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs font-semibold transition-all"
                                        :class="
                                            currentStep > index + 1
                                                ? 'border-primary bg-primary text-white'
                                                : currentStep === index + 1
                                                  ? 'border-primary bg-white text-primary ring-4 ring-primary/10 dark:bg-secondary'
                                                  : 'border-slate-200 bg-white text-slate-400 dark:border-white/10 dark:bg-secondary dark:text-gray-500'
                                        "
                                    >
                                        <Check
                                            v-if="currentStep > index + 1"
                                            class="h-3.5 w-3.5 stroke-[2.5]"
                                        />
                                        <span v-else>{{ index + 1 }}</span>
                                    </div>

                                    <span
                                        class="mt-1.5 max-w-[6rem] text-center text-[11px] font-medium leading-tight"
                                        :class="
                                            currentStep >= index + 1
                                                ? 'text-slate-800 dark:text-white'
                                                : 'text-slate-400 dark:text-gray-500'
                                        "
                                    >
                                        {{ step }}
                                    </span>
                                </div>

                                <div
                                    v-if="index < STEPS.length - 1"
                                    class="mx-3 mt-[15px] h-px flex-1 transition-colors"
                                    :class="
                                        currentStep > index + 1
                                            ? 'bg-primary'
                                            : 'bg-slate-200 dark:bg-white/10'
                                    "
                                />
                            </li>
                        </ol>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                        <p
                            v-if="stepError"
                            class="mb-4 rounded-lg border border-danger/20 bg-danger/5 px-4 py-2 text-sm text-danger dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400"
                        >
                            {{ stepError }}
                        </p>

                        <!-- Step 1 — how to add this branch, then plan if purchasing -->
                        <div v-if="currentStep === 1">
                            <!-- Choose: use a slot on an existing subscription, or buy another -->
                            <div v-if="!addMode" class="space-y-4">
                                <div class="mb-6 text-center">
                                    <p
                                        class="text-base font-semibold text-slate-900 dark:text-white"
                                    >
                                        How do you want to add this branch?
                                    </p>
                                    <p
                                        class="mt-1 text-sm text-muted dark:text-gray-400"
                                    >
                                        {{ capacity?.used ?? 0 }} of
                                        {{ capacity?.capacity ?? 0 }} branches
                                        used across your subscriptions.
                                    </p>
                                </div>

                                <button
                                    v-for="option in availableSubscriptions"
                                    :key="option.uuid"
                                    type="button"
                                    class="flex w-full items-start gap-4 rounded-xl border border-slate-200 p-5 text-left transition hover:border-primary hover:bg-primary-50/40 dark:border-white/10 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/10"
                                    @click="chooseSubscription(option.uuid)"
                                >
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                                    >
                                        <Check class="h-5 w-5" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-semibold text-secondary dark:text-white"
                                        >
                                            Use my {{ option.plan_name }}
                                            {{
                                                planTypeLabel(option.plan_type)
                                            }}
                                            subscription
                                        </p>

                                        <p
                                            class="mt-1 text-xs leading-5 text-muted dark:text-gray-400"
                                        >
                                            {{ option.slots_left }} of
                                            {{ option.branch_limit }} slots
                                            left. The branch shares this plan
                                            and runs until
                                            {{ formatDate(option.end_date) }}.
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                                    >
                                        No charge
                                    </span>
                                </button>

                                <div
                                    v-if="!canUseCapacity"
                                    class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50/60 p-4 text-sm text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
                                >
                                    <Building2
                                        class="mt-0.5 h-4 w-4 shrink-0"
                                    />
                                    <span>
                                        All
                                        {{ capacity?.capacity ?? 0 }} branch
                                        slots on your subscription are used.
                                    </span>
                                </div>

                                <button
                                    v-for="option in additionalOptions"
                                    :key="`additional-${option.uuid}`"
                                    type="button"
                                    class="flex w-full items-start gap-4 rounded-xl border border-slate-200 p-5 text-left transition hover:border-primary hover:bg-primary-50/40 dark:border-white/10 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/10"
                                    @click="chooseAdditional(option.uuid)"
                                >
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                    >
                                        <Building2 class="h-5 w-5" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm font-semibold text-secondary dark:text-white"
                                        >
                                            Add an additional branch to my
                                            {{ option.plan_name }}
                                            {{ planTypeLabel(option.plan_type) }}
                                            subscription
                                        </p>

                                        <p
                                            class="mt-1 text-xs leading-5 text-muted dark:text-gray-400"
                                        >
                                            ₱{{ formatMoney(option.additional_branch_price) }}
                                            / year, prorated for the
                                            {{ option.months }}
                                            {{ option.months === 1 ? "month" : "months" }}
                                            left until
                                            {{ formatDate(option.end_date) }}.
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary"
                                    >
                                        ₱{{ formatMoney(option.amount) }}
                                    </span>
                                </button>
                            </div>

                            <!-- Chose capacity: nothing to pick, the plan is inherited -->
                            <div
                                v-else-if="addMode === 'capacity'"
                                class="space-y-4"
                            >
                                <div
                                    class="rounded-xl border border-slate-200 p-5 dark:border-white/10"
                                >
                                    <p
                                        class="text-sm font-semibold text-secondary dark:text-white"
                                    >
                                        {{ availableSubscription?.plan_name }} —
                                        included
                                    </p>
                                    <p
                                        class="mt-1 text-xs leading-5 text-muted dark:text-gray-400"
                                    >
                                        This branch joins your existing
                                        subscription, so it uses the same plan
                                        and expires with it on
                                        {{
                                            formatDate(
                                                availableSubscription?.end_date,
                                            )
                                        }}.
                                    </p>
                                </div>

                                <button
                                    v-if="optionCount > 1"
                                    type="button"
                                    class="text-xs font-medium text-primary hover:underline"
                                    @click="resetChoice"
                                >
                                    Choose a different option
                                </button>
                            </div>

                            <div
                                v-else-if="addMode === 'additional'"
                                class="space-y-4"
                            >
                                <div
                                    class="rounded-xl border border-slate-200 p-5 dark:border-white/10"
                                >
                                    <p
                                        class="text-sm font-semibold text-secondary dark:text-white"
                                    >
                                        {{ additionalOption?.plan_name }} —
                                        additional branch
                                    </p>
                                    <p
                                        class="mt-1 text-xs leading-5 text-muted dark:text-gray-400"
                                    >
                                        This branch joins your existing
                                        subscription and expires with it on
                                        {{ formatDate(additionalOption?.end_date) }}.
                                        You pay ₱{{ formatMoney(additionalOption?.amount ?? 0) }},
                                        prorated for the time left. It renews
                                        with the subscription at
                                        ₱{{ formatMoney(additionalOption?.additional_branch_price ?? 0) }}
                                        / year.
                                    </p>

                                    <div
                                        class="mt-3 space-y-1.5 border-t border-slate-100 pt-3 text-xs dark:border-white/10"
                                    >
                                        <div class="flex justify-between">
                                            <span class="text-muted dark:text-gray-400">
                                                Additional branch (yearly)
                                            </span>
                                            <span class="font-medium text-secondary dark:text-white">
                                                ₱{{ formatMoney(additionalOption?.additional_branch_price ?? 0) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between">
                                            <span class="text-muted dark:text-gray-400">
                                                Months left on subscription
                                            </span>
                                            <span class="font-medium text-secondary dark:text-white">
                                                {{ additionalOption?.months ?? 0 }} of 12
                                            </span>
                                        </div>

                                        <div class="flex justify-between border-t border-slate-100 pt-1.5 dark:border-white/10">
                                            <span class="font-semibold text-secondary dark:text-white">
                                                You pay today
                                            </span>
                                            <span class="font-bold text-primary">
                                                ₱{{ formatMoney(additionalOption?.amount ?? 0) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    v-if="optionCount > 1"
                                    type="button"
                                    class="text-xs font-medium text-primary hover:underline"
                                    @click="resetChoice"
                                >
                                    Choose a different option
                                </button>
                            </div>

                        </div>

                        <!-- Step 2 — branch details (no agency form, it is inherited) -->
                        <div v-else-if="currentStep === 2">
                            <div
                                class="mb-5 flex items-start gap-2.5 rounded-lg border border-primary/10 bg-primary/5 px-4 py-3 text-[13px] text-primary"
                            >
                                <Link2 class="mt-0.5 h-4 w-4 shrink-0" />
                                <span>
                                    This branch will be created under
                                    <strong>{{ agencyName }}</strong> — your
                                    agency details carry over automatically, so
                                    you only need the branch information.
                                </span>
                            </div>

                            <BranchForm
                                v-model:branch="form.branch"
                                v-model:errors="errors"
                            />
                        </div>

                        <!-- Step 3 — operational configuration -->
                        <div v-else-if="currentStep === 3">
                            <div class="mb-5">
                                <h3
                                    class="text-lg font-semibold text-slate-900 dark:text-white"
                                >
                                    Branch configuration
                                </h3>
                                <p
                                    class="mt-1 text-sm text-slate-500 dark:text-gray-400"
                                >
                                    Set up the operational preferences for this
                                    branch.
                                </p>
                            </div>

                            <SubcriptionConfigure
                                :setting="form.settings"
                                :errors="errors"
                                @update:errors="errors = $event"
                            />
                        </div>

                        <!-- Step 4 — review -->
                        <div
                            v-else-if="currentStep === 4"
                            class="mx-auto w-full max-w-2xl space-y-4"
                        >
                            <div
                                class="mx-auto mb-5 w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-[0_8px_30px_rgba(15,23,42,0.06)] dark:border-white/10 dark:bg-secondary"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                                    >
                                        <component
                                            :is="
                                                planIcon(
                                                    effectivePlan?.plan_code,
                                                )
                                            "
                                            class="h-5 w-5"
                                        />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="truncate text-base font-bold text-secondary dark:text-white"
                                        >
                                            {{ effectivePlan?.name }}
                                        </p>

                                        <p
                                            class="mt-0.5 text-xs text-muted dark:text-gray-400"
                                        >
                                            {{
                                                planTypeLabel(effectivePlanType)
                                            }}
                                            ·
                                            {{
                                                branchLimitText(
                                                    effectivePlanType,
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="usesExistingCapacity"
                                        class="shrink-0 text-right"
                                    >
                                        <p
                                            class="text-sm font-bold text-emerald-600 dark:text-emerald-400"
                                        >
                                            Included
                                        </p>

                                        <p
                                            class="text-[11px] text-muted dark:text-gray-400"
                                        >
                                            no extra charge
                                        </p>
                                    </div>

                                    <div v-else class="shrink-0 text-right">
                                        <p
                                            class="text-lg font-bold text-primary"
                                        >
                                            <span
                                                v-if="loadingTotal"
                                                class="inline-block h-5 w-20 animate-pulse rounded bg-slate-200 dark:bg-white/10 align-middle"
                                            />
                                            <template v-else>
                                                ₱{{ formatMoney(total) }}
                                            </template>
                                        </p>

                                        <p
                                            class="text-[11px] text-muted dark:text-gray-400"
                                        >
                                            {{ usesAdditional ? "prorated" : "/ year" }}
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-if="
                                        !usesExistingCapacity &&
                                        !usesAdditional &&
                                        !loadingTotal
                                    "
                                    class="mt-3 rounded-lg px-3 py-2 text-xs leading-5"
                                    :class="
                                        purchaseWithTrial
                                            ? 'bg-emerald-50/70 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                            : 'bg-slate-50 text-slate-600 dark:bg-white/5 dark:text-gray-300'
                                    "
                                >
                                    <template v-if="purchaseWithTrial">
                                        <span class="font-semibold"
                                            >1 month free testing</span
                                        >
                                        first, then your paid year starts.
                                        Cancel anytime during testing for a full
                                        refund.
                                    </template>
                                    <template v-else>
                                        Your agency has already used its free
                                        month, so this paid year starts as soon
                                        as the branch is approved.
                                    </template>
                                </p>

                                <p
                                    v-if="effectivePlan?.description"
                                    class="mt-3 border-t border-slate-100 pt-3 text-xs leading-relaxed text-muted dark:border-white/10 dark:text-gray-400"
                                >
                                    {{ effectivePlan?.description }}
                                </p>
                            </div>

                            <section
                                class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-secondary"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between"
                                >
                                    <h3
                                        class="text-sm font-semibold text-slate-900 dark:text-white"
                                    >
                                        Branch details
                                    </h3>
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-primary hover:underline"
                                        @click="currentStep = 2"
                                    >
                                        Edit
                                    </button>
                                </div>

                                <SummaryRow
                                    label="Branch name"
                                    :value="form.branch.name"
                                />
                                <SummaryRow
                                    label="Email"
                                    :value="form.branch.email"
                                />
                                <SummaryRow
                                    label="Contact number"
                                    :value="form.branch.contact_number"
                                />
                                <SummaryRow
                                    label="Address"
                                    :value="branchAddress"
                                />
                                <SummaryRow
                                    label="TIN"
                                    :value="form.branch.tin"
                                />
                                <SummaryRow
                                    label="Description"
                                    :value="form.branch.description"
                                />
                            </section>

                            <section
                                class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-secondary"
                            >
                                <div
                                    class="mb-3 flex items-center justify-between"
                                >
                                    <h3
                                        class="text-sm font-semibold text-slate-900 dark:text-white"
                                    >
                                        Configuration
                                    </h3>
                                    <button
                                        type="button"
                                        class="text-xs font-semibold text-primary hover:underline"
                                        @click="currentStep = 3"
                                    >
                                        Edit
                                    </button>
                                </div>

                                <SummaryRow
                                    label="Operating hours"
                                    :value="`${form.settings.opening} – ${form.settings.closing}`"
                                />
                                <SummaryRow
                                    label="Time zone"
                                    :value="form.settings.time_zone"
                                />
                                <SummaryRow
                                    label="Currency"
                                    :value="form.settings.currency"
                                />
                            </section>
                        </div>

                        <!-- Step 5 — payment or confirm -->
                        <div
                            v-else-if="currentStep === 5"
                            class="mx-auto w-full max-w-2xl"
                        >
                            <div
                                v-if="usesExistingCapacity"
                                class="mx-auto w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-secondary"
                            >
                                <p
                                    class="text-sm font-semibold text-slate-900 dark:text-white"
                                >
                                    Included in your current subscription
                                </p>

                                <p
                                    class="mt-1 text-sm leading-6 text-muted dark:text-gray-400"
                                >
                                    This is branch
                                    {{ (capacity?.used ?? 0) + 1 }} of
                                    {{ capacity?.capacity }} — no payment is
                                    needed. It will be sent to AMUMA for review
                                    before it goes live.
                                </p>

                                <button
                                    type="button"
                                    :disabled="processing"
                                    class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    @click="addFromCapacity"
                                >
                                    <LoaderCircle
                                        v-if="processing"
                                        class="h-4 w-4 animate-spin"
                                    />
                                    {{
                                        processing
                                            ? "Adding branch..."
                                            : "Confirm & add branch"
                                    }}
                                </button>
                            </div>

                            <PaymentForm
                                v-else
                                v-model:card="card"
                                :total-amount="total"
                                :processing="processing || loadingTotal"
                                :onCardPay="payCard"
                                :onGCashPay="payGCash"
                                :enableGCash="true"
                                title="Branch payment"
                                description="Choose your payment method to activate this branch."
                                submit-label="Confirm & add branch"
                                terms-context="subscription"
                            />
                        </div>
                    </div>

                    <div
                        v-if="currentStep < STEPS.length"
                        class="flex shrink-0 items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <button
                            v-if="currentStep > 1 || (addMode && optionCount > 1)"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:border-white/20 dark:hover:bg-white/10"
                            @click="previousStep"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            Previous
                        </button>

                        <div v-else />

                        <button
                            type="button"
                            :disabled="continueDisabled"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="nextStep"
                        >
                            <LoaderCircle
                                v-if="validating"
                                class="h-4 w-4 animate-spin"
                            />
                            {{ validating ? "Validating..." : "Continue" }}
                            <ChevronRight v-if="!validating" class="h-4 w-4" />
                        </button>
                    </div>

                    <div
                        v-else
                        class="flex shrink-0 items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <button
                            type="button"
                            :disabled="processing"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:border-white/20 dark:hover:bg-white/10 disabled:opacity-40"
                            @click="previousStep"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            Back to review
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref, watch } from "vue";
import {
    Building2,
    Check,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Home,
    Link2,
    LoaderCircle,
    X,
} from "lucide-vue-next";

import BranchForm from "~/components/forms/BranchForm.vue";
import { formatAmount } from "~/utils/currency";
import SubcriptionConfigure from "~/components/forms/SubcriptionConfigure.vue";
import PaymentForm from "~/components/forms/PaymentForm.vue";

import { planService } from "~/api/plan/PlanService";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import { useSubscriptionCheckout } from "~/stores/subscription";
import { useToast } from "~/composables/useToast";
import { branchSchema } from "~/schema/branch-schema";
import type { Branch, BranchSettings } from "~/types/branch";
import type { CardDetails } from "~/types/payment";
import PlanTypeToggle from "~/components/ui/PlanTypeToggle.vue";
import SummaryRow from "~/components/ui/SummaryRow.vue";
import { planIcon } from "~/utils/planIcon";
import {
    DEFAULT_PLAN_TYPE,
    branchLimitText,
    findPlan,
    planPrice,
    plansOfType,
    planTypeBranchLimit,
    planTypeLabel,
    type PlanType,
} from "~/utils/planType";

interface AvailableSubscription {
    uuid: string;
    plan_name: string | null;
    plan_code: string | null;
    plan_type: PlanType | null;
    end_date: string | null;
    branches_used: number;
    branch_limit: number;
    slots_left: number;
}

interface AdditionalOption {
    uuid: string;
    plan_name: string | null;
    plan_code: string | null;
    plan_type: PlanType | null;
    end_date: string | null;
    additional_branch_price: number;
    months: number;
    amount: number;
}

interface BranchCapacity {
    used: number;
    capacity: number;
    remaining: number;
    has_room: boolean;
    available_subscriptions?: AvailableSubscription[];
    additional_options?: AdditionalOption[];
}

const props = defineProps<{
    agencyId: number | string;
    agencyName: string;
    branchUuid: string;
    capacity?: BranchCapacity | null;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "created", result: any): void;
}>();

const { success, error } = useToast();

const checkout = useSubscriptionCheckout();

const currentStep = ref(1);
const stepError = ref<string | null>(null);
const errors = ref<Record<string, string>>({});

const plans = ref<any[]>([]);
const loadingPlans = ref(true);

const capacity = computed(() => props.capacity ?? null);

const addMode = ref<"capacity" | "purchase" | "additional" | null>(null);

const additionalOptions = computed<AdditionalOption[]>(
    () => capacity.value?.additional_options ?? [],
);

const additionalOption = computed(
    () =>
        additionalOptions.value.find(
            (option) => option.uuid === selectedSubscriptionUuid.value,
        ) ?? null,
);

const usesAdditional = computed(
    () => addMode.value === "additional" && !!additionalOption.value,
);

function chooseAdditional(uuid: string) {
    selectedSubscriptionUuid.value = uuid;
    addMode.value = "additional";
    total.value =
        additionalOptions.value.find((option) => option.uuid === uuid)
            ?.amount ?? 0;
}

const selectedSubscriptionUuid = ref<string | null>(null);

const availableSubscriptions = computed<AvailableSubscription[]>(
    () => capacity.value?.available_subscriptions ?? [],
);

const availableSubscription = computed(
    () =>
        availableSubscriptions.value.find(
            (subscription) =>
                subscription.uuid === selectedSubscriptionUuid.value,
        ) ?? null,
);

const canUseCapacity = computed(() => availableSubscriptions.value.length > 0);

function chooseSubscription(uuid: string) {
    selectedSubscriptionUuid.value = uuid;
    addMode.value = "capacity";
}

const optionCount = computed(
    () => availableSubscriptions.value.length + additionalOptions.value.length,
);

function selectSingleOption() {
    if (addMode.value || optionCount.value !== 1) return;

    if (availableSubscriptions.value.length) {
        chooseSubscription(availableSubscriptions.value[0].uuid);
    } else {
        chooseAdditional(additionalOptions.value[0].uuid);
    }
}

watch(optionCount, selectSingleOption);

function resetChoice() {
    selectedSubscriptionUuid.value = null;
    addMode.value = null;
}

const planKey = (code?: string | null, type?: string | null) =>
    `${type}:${code}`;

const openSlotsByPlan = computed(() => {
    const map = new Map<string, { slots: number; uuid: string }>();

    for (const option of availableSubscriptions.value) {
        if (!option.plan_code || option.slots_left < 1) continue;

        const key = planKey(option.plan_code, option.plan_type);
        const existing = map.get(key);

        map.set(key, {
            slots: (existing?.slots ?? 0) + option.slots_left,
            uuid: existing?.uuid ?? option.uuid,
        });
    }

    return map;
});

const slotsForPlan = (plan: any) =>
    openSlotsByPlan.value.get(planKey(plan?.plan_code, plan?.type)) ?? null;

const planType = ref<PlanType>(DEFAULT_PLAN_TYPE);

const typedPlans = computed(() => plansOfType(plans.value, planType.value));

const selectablePlans = computed(() =>
    typedPlans.value.filter((plan) => !slotsForPlan(plan)),
);

const buyingMoreCapacity = computed(() => addMode.value === "purchase");

const usesExistingCapacity = computed(
    () => canUseCapacity.value && addMode.value === "capacity",
);

const effectivePlan = computed(() => {
    if (usesAdditional.value) {
        return findPlan(
            plans.value,
            additionalOption.value?.plan_code,
            additionalOption.value?.plan_type,
        );
    }

    if (!usesExistingCapacity.value) return form.plan;

    return findPlan(
        plans.value,
        availableSubscription.value?.plan_code,
        availableSubscription.value?.plan_type,
    );
});

const enterpriseLimit = planTypeBranchLimit("enterprise");

const effectivePlanType = computed<PlanType>(() =>
    usesAdditional.value
        ? (additionalOption.value?.plan_type ?? DEFAULT_PLAN_TYPE)
        : usesExistingCapacity.value
          ? (availableSubscription.value?.plan_type ?? DEFAULT_PLAN_TYPE)
          : planType.value,
);

const STEPS = computed(() => [
    "Plan",
    "Branch Details",
    "Configuration",
    "Review",
    usesExistingCapacity.value ? "Confirm" : "Payment",
]);

const validating = ref(false);
const processing = ref(false);
const loadingTotal = ref(false);
const total = ref(0);
const purchaseWithTrial = ref(false);

const branchAddress = computed(
    () =>
        form.branch.location.full_address ||
        [
            form.branch.location.street,
            form.branch.location.city,
            form.branch.location.province,
            form.branch.location.country,
        ]
            .filter(Boolean)
            .join(", "),
);

const onOff = (value?: boolean | null) => (value ? "On" : "Off");

const emptyBranch = (): Branch =>
    ({
        name: "",
        contact_number: "",
        description: "",
        tin: "",
        image: null,
        email: "",
        document: "",
        status: "active",
        location: {
            street: "",
            city: "",
            province: "",
            country: "",
            latitude: 0,
            longitude: 0,
        },
    }) as unknown as Branch;

const form = reactive({
    plan: null as any,
    branch: emptyBranch(),
    settings: {
        opening: "00:00",
        closing: "23:59",
        currency: "PHP",
        time_zone: "Asia/Manila",
        reserved_walkin_slots: 3,
        enable_booking_pre_admission: true,
        enable_booking_complete_admission: true,
        minimum_adl_hours: 8,
        is_open: true,
    } as BranchSettings,
});

const card = ref<CardDetails>({
    number: "4000000000001000",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const continueDisabled = computed(() => {
    if (validating.value) return true;

    if (currentStep.value === 1) {
        if (!addMode.value) return true;
        if (addMode.value === "purchase") {
            return loadingPlans.value || !form.plan;
        }
    }

    return false;
});

const buildPayload = () => ({
    plan_code: effectivePlan.value?.plan_code,
    plan_type: effectivePlanType.value,
    payment_method: checkout.payment_method,

    // BRANCH DATA
    branch_name: form.branch.name,
    branch_contact_number: form.branch.contact_number,
    branch_description: form.branch.description,
    branch_email: form.branch.email,
    branch_image: form.branch.image,
    branch_document: (form.branch as any).document,
    branch_settings: { ...form.settings, tin: form.branch.tin || null },
    branch_street: form.branch.location.street,
    branch_city: form.branch.location.city,
    branch_province: form.branch.location.province,
    branch_country: form.branch.location.country,
    branch_full_address: form.branch.location.full_address ?? "",
    branch_latitude: form.branch.location.latitude,
    branch_longitude: form.branch.location.longitude,

    // AGENCY
    agency_id: props.agencyId,
});

const validateBranch = (): boolean => {
    const result = branchSchema.safeParse(form.branch);

    if (result.success) return true;

    const keyMap: Record<string, string> = {
        name: "branch_name",
        description: "branch_description",
        contact_number: "branch_contact_number",
        image: "branch_image",
        email: "branch_email",
        document: "branch_document",
        tin: "branch_tin",
    };

    const mapped: Record<string, string> = {};

    result.error.issues.forEach((issue) => {
        const path = issue.path.join(".");
        mapped[keyMap[path] ?? path] ??= issue.message;
    });

    errors.value = mapped;

    return false;
};

const stepForField = (field: string): number => {
    if (field.startsWith("branch_settings")) return 3;
    if (field.startsWith("branch_")) return 2;
    if (field.startsWith("plan") || field.startsWith("billing")) return 1;

    return 2;
};

const scrollToFirstError = async () => {
    await nextTick();

    const firstKey = Object.keys(errors.value ?? {})[0];

    if (!firstKey) return;

    document
        .querySelector(`[data-field~="${firstKey}"]`)
        ?.scrollIntoView({ behavior: "smooth", block: "center" });
};

const checkUnique = async (): Promise<boolean> => {
    validating.value = true;

    try {
        await subscriptionService.checkUnique({
            agency_id: props.agencyId,
            branch_name: form.branch.name,
            branch_email: form.branch.email,
        });

        return true;
    } catch (err: any) {
        const raw = err?.errors ?? {};

        if (!Object.keys(raw).length) {
            stepError.value =
                err?.message ?? "Could not verify the branch details.";

            return false;
        }

        errors.value = Object.fromEntries(
            Object.entries(raw).map(([key, value]: any) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ]),
        );

        return false;
    } finally {
        validating.value = false;
    }
};

const nextStep = async () => {
    stepError.value = null;

    if (currentStep.value === 1) {
        if (!addMode.value) {
            stepError.value = "Please choose how to add this branch.";
            return;
        }

        if (addMode.value === "purchase" && !form.plan) {
            stepError.value = "Please select a plan.";
            return;
        }
    }

    if (currentStep.value === 2) {
        errors.value = {};

        if (!validateBranch() || !(await checkUnique())) {
            await scrollToFirstError();
            return;
        }
    }

    if (currentStep.value === 3) {
        if (
            // Equal times mean the branch is open 24 hours, not that it's
            // closed before it opens.
            form.settings.opening &&
            form.settings.closing &&
            form.settings.closing < form.settings.opening
        ) {
            errors.value = {
                ...errors.value,
                closing: "Closing time must be later than opening time.",
            };
            return;
        }

        const passed = await validateOnServer();
        if (!passed) return;

        if (!usesExistingCapacity.value && !usesAdditional.value) {
            void loadTotal();
        }
    }

    currentStep.value++;
};

const previousStep = () => {
    stepError.value = null;

    if (currentStep.value > 1) {
        currentStep.value--;
        return;
    }

    resetChoice();
};

const validateOnServer = async (): Promise<boolean> => {
    validating.value = true;
    errors.value = {};

    try {
        await subscriptionService.validateSubscription(buildPayload());
        return true;
    } catch (err: any) {
        const raw = err?.errors ?? {};

        const mapped = Object.fromEntries(
            Object.entries(raw).map(([key, value]: any) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ]),
        );

        errors.value = mapped;

        const first = Object.keys(mapped)[0];

        if (first) {
            currentStep.value = stepForField(first);
            stepError.value = mapped[first];
            await scrollToFirstError();
        } else {
            stepError.value = err?.message ?? "Validation failed.";
        }

        return false;
    } finally {
        validating.value = false;
    }
};

const loadTotal = async () => {
    loadingTotal.value = true;

    try {
        const payload: Record<string, any> = { ...buildPayload() };

        delete payload.branch_image;
        delete payload.branch_document;
        delete payload.branch_settings;

        const res = await subscriptionService.retrieveSubscriptionDetail(
            payload as any,
        );

        total.value = Number(res.total_amount) || 0;
        purchaseWithTrial.value = res.mode === "test";
    } catch (err: any) {
        error(err?.message ?? "Failed to load the branch total.");
    } finally {
        loadingTotal.value = false;
    }
};

const onCreated = async (result: any) => {
    success(result?.message ?? "Branch added successfully.");
    emit("created", {
        ...result,
        used_existing_capacity:
            usesExistingCapacity.value || usesAdditional.value,
        plan_type: effectivePlanType.value,
    });
};

const createPurchase = (payload: Record<string, any>) =>
    usesAdditional.value
        ? subscriptionService.createAdditionalBranch({
              ...payload,
              branch_uuid: props.branchUuid,
              subscription_uuid: additionalOption.value?.uuid,
          })
        : subscriptionService.createSubscription(payload);

const payCard = async () => {
    if (processing.value || loadingTotal.value) return;

    processing.value = true;

    try {
        const payload = buildPayload();

        await cardPayment({
            card: card.value,
            amount: total.value,

            onClose: () => {
                processing.value = false;
            },

            createPayment: ({ token_id, authentication_id }) =>
                createPurchase({
                    ...payload,
                    token_id,
                    authentication_id,
                    payment_method: "CREDIT-CARD",
                    payment_type: "SUBSCRIPTION",
                }),

            onSuccess: onCreated,
        });
    } catch (err: any) {
        error(err?.message ?? "Payment failed.");
    } finally {
        processing.value = false;
    }
};

const payGCash = async () => {
    if (processing.value || loadingTotal.value) return;

    processing.value = true;

    try {
        const payload = buildPayload();

        await gcashPayment({
            createPayment: () =>
                createPurchase({
                    ...payload,
                    payment_method: "GCASH",
                    payment_type: "SUBSCRIPTION",
                }),

            onClose: () => {
                processing.value = false;
            },

            onSuccess: onCreated,
        });
    } catch (err: any) {
        error(err?.message ?? "Payment failed.");
    } finally {
        processing.value = false;
    }
};

const requestClose = () => {
    if (processing.value) return;
    emit("close");
};

const formatMoney = (value: number) => formatAmount(value);

const formatDate = (date?: string | null) => {
    if (!date) return "—";

    try {
        return new Date(date).toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
        });
    } catch {
        return String(date);
    }
};
watch(planType, (type) => {
    form.plan =
        selectablePlans.value.find(
            (plan) => plan.plan_code === form.plan?.plan_code,
        ) ??
        selectablePlans.value[0] ??
        null;
});

watch(
    () => form.plan?.plan_id,
    () => {
        if (currentStep.value >= 4) loadTotal();
    },
);

onMounted(async () => {
    selectSingleOption();

    try {
        const res = await planService.list();
        plans.value = res ?? [];
        form.plan = selectablePlans.value[0] ?? null;
    } catch (err: any) {
        stepError.value = err?.message ?? "Failed to load plans.";
    } finally {
        loadingPlans.value = false;
    }
});

const addFromCapacity = async () => {
    if (processing.value) return;

    processing.value = true;
    stepError.value = null;

    try {
        const result = await subscriptionService.createBranchFromCapacity({
            ...buildPayload(),
            branch_uuid: props.branchUuid,
            subscription_uuid: availableSubscription.value?.uuid,
        });

        await onCreated(result);
    } catch (err: any) {
        stepError.value =
            err?.message ?? "Failed to add the branch. Please try again.";
    } finally {
        processing.value = false;
    }
};
</script>
