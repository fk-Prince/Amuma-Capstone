<template>
    <div class="space-y-6">
        <div
            v-if="loading"
            class="animate-pulse space-y-6 rounded-2xl border border-slate-200 p-6 dark:border-white/10"
        >
            <div class="space-y-2">
                <div class="h-5 w-52 rounded bg-slate-200 dark:bg-white/10" />
                <div
                    class="h-3.5 w-80 max-w-full rounded bg-slate-100 dark:bg-white/5"
                />
            </div>

            <div class="flex items-center gap-3">
                <div
                    class="h-11 w-11 rounded-xl bg-slate-200 dark:bg-white/10"
                />
                <div class="space-y-2">
                    <div
                        class="h-4 w-36 rounded bg-slate-200 dark:bg-white/10"
                    />
                    <div
                        class="h-3 w-24 rounded bg-slate-100 dark:bg-white/5"
                    />
                </div>
            </div>

            <div
                class="grid grid-cols-1 gap-4 border-t border-slate-100 pt-4 sm:grid-cols-3 dark:border-white/10"
            >
                <div v-for="i in 3" :key="i" class="space-y-2">
                    <div
                        class="h-2.5 w-16 rounded bg-slate-100 dark:bg-white/5"
                    />
                    <div
                        class="h-4 w-28 rounded bg-slate-200 dark:bg-white/10"
                    />
                </div>
            </div>

            <div
                class="space-y-3 border-t border-slate-100 pt-4 dark:border-white/10"
            >
                <div class="h-12 rounded-xl bg-slate-100 dark:bg-white/5" />
                <div class="h-12 rounded-xl bg-slate-100 dark:bg-white/5" />
            </div>
        </div>

        <div v-if="!loading">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Subscription &amp; Renewal
            </h2>

            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                Review this branch's plan and extend it before it lapses.
            </p>
        </div>

        <div
            v-if="!loading && !subscription"
            class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-slate-50/60 px-6 py-12 text-center dark:border-white/10 dark:bg-white/5"
        >
            <div
                class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-gray-500"
            >
                <CalendarX class="h-5 w-5" />
            </div>

            <p
                class="mt-2 text-sm font-semibold text-slate-800 dark:text-white"
            >
                No subscription found
            </p>
            <p
                class="mt-0.5 max-w-sm text-xs text-slate-500 dark:text-gray-400"
            >
                This branch has no subscription record to renew yet.
            </p>
        </div>

        <template v-else-if="!loading">
            <div
                class="overflow-hidden rounded-2xl border shadow-sm dark:border-white/10"
                :class="statusTone.border"
            >
                <div class="p-5" :class="statusTone.bg">
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-secondary"
                                :class="statusTone.icon"
                            >
                                <component
                                    :is="statusTone.glyph"
                                    class="h-5 w-5"
                                />
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-base font-bold text-slate-900 dark:text-white"
                                >
                                    {{ subscription.plan?.name ?? "—" }}
                                </p>

                                <p
                                    class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    {{ planTypeLabel(currentType) }} ·
                                    {{ branchLimitText(currentType) }}
                                    <template v-if="isCancelled">
                                        · Subscription cancelled
                                    </template>
                                    <template v-else-if="isTest">
                                        · Free testing
                                    </template>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-5 grid grid-cols-1 gap-4 border-t pt-4 sm:grid-cols-3"
                        :class="statusTone.divider"
                    >
                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                            >
                                Started
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-white"
                            >
                                {{ formatDate(subscription.start_date) }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                            >
                                {{
                                    isCancelled
                                        ? "Cancelled on"
                                        : isExpired
                                          ? "Expired on"
                                          : isTest
                                            ? "Test ends on"
                                            : "Renews on"
                                }}
                            </p>
                            <p
                                class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-white"
                            >
                                {{ formatDate(subscription.end_date) }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                            >
                                {{
                                    isCancelled
                                        ? "Refunded"
                                        : isExpired
                                          ? "Overdue by"
                                          : "Time left"
                                }}
                            </p>
                            <p
                                v-if="isCancelled"
                                class="mt-0.5 text-sm font-semibold"
                                :class="statusTone.text"
                            >
                                ₱{{ formatMoney(refundedAmount) }}
                            </p>
                            <p
                                v-else
                                class="mt-0.5 text-sm font-semibold"
                                :class="statusTone.text"
                            >
                                {{ Math.abs(daysRemaining) }}
                                {{
                                    Math.abs(daysRemaining) === 1
                                        ? "day"
                                        : "days"
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="canRenew && !hasPendingUpgrade && !isCancelled"
                class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50/70 px-4 py-3 text-xs text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300"
            >
                <Clock class="mt-0.5 h-4 w-4 shrink-0" />
                <span>
                    <template v-if="isExpired">
                        This subscription expired on
                        {{ formatDate(subscription.end_date) }}. Renew now to
                        keep your branch running.
                    </template>
                    <template v-else>
                        {{ Math.max(daysRemaining, 0) }}
                        {{ daysRemaining === 1 ? "day" : "days" }} left — renew
                        before {{ formatDate(subscription.end_date) }} to avoid
                        interruption. You'll be reminded every day until you
                        renew.
                    </template>
                </span>
            </div>

            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div>
                    <h3
                        class="text-sm font-bold text-slate-900 dark:text-white"
                    >
                        {{
                            isCancelled
                                ? "Subscription cancelled"
                                : isTest
                                ? "Free testing"
                                : hasPendingUpgrade
                                ? "Pending plan"
                                : coveredBranches.length > 1
                                  ? "Renew this subscription"
                                  : "Renew this branch"
                        }}
                    </h3>

                    <p
                        class="mt-1 max-w-lg text-xs leading-5 text-slate-500 dark:text-gray-400"
                    >
                        <template v-if="isCancelled">
                            You cancelled this subscription on
                            {{ formatDate(subscription.end_date) }} and your
                            payment was refunded. Want to keep using AMUMA?
                            Pick any plan type and plan and subscribe now — your
                            paid year starts today, with no free testing.
                        </template>
                        <template v-else-if="isTest">
                            Your paid year is already queued and starts
                            automatically when free testing ends. Not for you?
                            Cancel before then for a full refund.
                            <template v-if="canUpgrade">
                                You can still upgrade to Hybrid anytime.
                            </template>
                        </template>
                        <template v-else-if="hasPendingUpgrade">
                            {{ pendingPlan.name }} is already paid for and takes
                            over when the current period ends. You can renew
                            again once it does.
                        </template>
                        <template v-else-if="canRenew">
                            Renewal is open. The new year is added after
                            the current end date, so
                            you never lose the days you've already paid for.
                        </template>
                        <template v-else>
                            Renewal opens on {{ formatDate(renewalOpensAt) }},
                            {{ windowDays }} days before this subscription ends.
                            <template v-if="canUpgrade">
                                Upgrading to Hybrid can start today anytime.
                            </template>
                        </template>
                    </p>
                </div>

                <!-- Only worth showing when the renewal reaches beyond the
                     branch whose settings page this already is. -->
                <div
                    v-if="coveredBranches.length > 1"
                    class="mt-4 rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-white/10 dark:bg-white/5"
                >
                    <p
                        class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400"
                    >
                        Branches on this subscription
                    </p>

                    <ul class="mt-3 flex flex-col gap-2">
                        <li
                            v-for="covered in coveredBranches"
                            :key="covered.uuid"
                            class="flex items-center gap-2 text-sm text-slate-700 dark:text-gray-300"
                        >
                            <span
                                class="h-1.5 w-1.5 shrink-0 rounded-full"
                                :class="
                                    covered.status === 'approved'
                                        ? 'bg-emerald-500'
                                        : 'bg-amber-500'
                                "
                            />
                            {{ covered.name }}
                            <span
                                v-if="covered.status !== 'approved'"
                                class="text-[10px] uppercase text-amber-600 dark:text-amber-400"
                            >
                                pending
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Already paid for, just waiting its turn -->
                <div
                    v-if="pendingPlan && !pendingPlan.is_due"
                    class="mt-4 rounded-xl border border-primary/20 bg-primary/5 p-4"
                >
                    <div class="flex items-start gap-3 text-xs text-primary">
                        <RefreshCw class="mt-0.5 h-4 w-4 shrink-0" />
                        <span v-if="isTest">
                            Your paid year of {{ pendingPlan.name }}
                            {{ planTypeLabel(pendingPlan.type) }} starts on
                            {{ formatDate(pendingPlan.starts_at) }}, when free
                            testing ends.
                        </span>
                        <span v-else>
                            Change to {{ pendingPlan.name }}
                            {{ planTypeLabel(pendingPlan.type) }} starts on
                            {{ formatDate(pendingPlan.starts_at) }}, when the
                            current period ends.
                        </span>
                    </div>

                    <div
                        v-if="!isTest && confirmCancelPending"
                        class="mt-3 rounded-lg border border-rose-200 bg-white p-3 dark:border-rose-500/30 dark:bg-white/5"
                    >
                        <p class="text-xs text-slate-600 dark:text-gray-300">
                            Cancel the change to {{ pendingPlan.name }}
                            {{ planTypeLabel(pendingPlan.type) }}? You stay on
                            {{ currentPlan?.name ?? "your current plan" }} until
                            {{ formatDate(subscription?.end_date) }}, and
                            ₱{{ formatMoney(pendingPlanPaid) }} is refunded.
                        </p>

                        <div class="mt-3 flex flex-wrap justify-end gap-2">
                            <button
                                type="button"
                                :disabled="cancellingPending"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/10"
                                @click="confirmCancelPending = false"
                            >
                                Keep the change
                            </button>

                            <button
                                type="button"
                                :disabled="cancellingPending"
                                class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-700 disabled:opacity-50"
                                @click="cancelPendingPlan"
                            >
                                {{
                                    cancellingPending
                                        ? "Cancelling…"
                                        : "Yes, cancel & refund"
                                }}
                            </button>
                        </div>
                    </div>

                    <div
                        v-else-if="!isTest"
                        class="mt-3 flex flex-wrap items-center justify-between gap-3 pl-7"
                    >
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            Changed your mind?
                        </p>
                        <button
                            type="button"
                            class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/30 dark:bg-secondary dark:text-rose-300 dark:hover:bg-rose-500/10"
                            @click="confirmCancelPending = true"
                        >
                            Cancel change
                        </button>
                    </div>
                </div>

                <div
                    v-if="canCancel"
                    class="mt-4 rounded-xl border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-500/30 dark:bg-rose-500/10"
                >
                    <div
                        v-if="!confirmCancel"
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <p class="text-xs text-rose-700 dark:text-rose-300">
                            Not what you need? Cancel before
                            {{ formatDate(pendingPlan?.starts_at) }} and we'll
                            refund your payment in full.
                        </p>

                        <button
                            type="button"
                            class="rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/40 dark:bg-secondary dark:text-rose-300 dark:hover:bg-rose-500/10"
                            @click="confirmCancel = true"
                        >
                            Cancel subscription
                        </button>
                    </div>

                    <div v-else>
                        <p class="text-xs text-rose-700 dark:text-rose-300">
                            Cancelling ends free testing now and refunds
                            ₱{{ formatMoney(refundAmount) }}.
                            {{
                                coveredBranches.length > 1
                                    ? `All ${coveredBranches.length} branches on this subscription`
                                    : "This branch"
                            }}
                            will stop running. This can't be undone.
                        </p>

                        <div class="mt-3 flex flex-wrap justify-end gap-2">
                            <button
                                type="button"
                                :disabled="cancelling"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 disabled:opacity-50 dark:text-gray-400 dark:hover:bg-white/10"
                                @click="confirmCancel = false"
                            >
                                Keep subscription
                            </button>

                            <button
                                type="button"
                                :disabled="cancelling"
                                class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-700 disabled:opacity-50"
                                @click="cancelTest"
                            >
                                {{
                                    cancelling
                                        ? "Cancelling…"
                                        : "Yes, cancel & refund"
                                }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="showPlanPicker" class="mt-5">
                    <p
                        class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        Plan
                        <template v-if="!isCancelled">
                            · {{ planTypeLabel(planType) }}
                        </template>
                    </p>

                    <PlanTypeToggle
                        v-if="isCancelled"
                        v-model="planType"
                        class="mb-3"
                    />

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <button
                            v-for="plan in selectablePlans"
                            :key="plan.plan_id"
                            type="button"
                            class="flex h-full flex-col gap-2 rounded-xl border p-4 text-left transition dark:border-white/10"
                            :class="[
                                chosenPlanCode === plan.plan_code
                                    ? 'border-primary bg-primary-50/60 ring-1 ring-primary/20 dark:bg-primary-500/10'
                                    : 'border-slate-200 hover:border-primary-200 dark:hover:border-primary-500/40',
                                showUpgradeMath &&
                                isUpgrading &&
                                chosenPlanCode === plan.plan_code
                                    ? 'sm:col-span-3'
                                    : '',
                            ]"
                            @click="chosenPlanCode = plan.plan_code"
                        >
                            <span class="flex items-center gap-2">
                                <component
                                    :is="planIcon(plan.plan_code)"
                                    class="h-4 w-4 text-primary"
                                />
                                <span
                                    class="text-sm font-semibold text-slate-900 dark:text-white"
                                >
                                    {{ plan.name }}
                                </span>
                                <span
                                    v-if="!isCancelled && plan.plan_id === currentPlan?.plan_id"
                                    class="ml-auto rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-white/10 dark:text-gray-300"
                                >
                                    Current
                                </span>
                            </span>

                            <span
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                {{ branchLimitText(plan.type) }}
                            </span>

                            <span
                                class="mt-auto text-base font-bold text-primary"
                            >
                                ₱{{ formatMoney(planTotal(plan)) }}
                                <span
                                    class="text-xs font-medium text-slate-500 dark:text-gray-400"
                                >
                                    / year
                                </span>
                            </span>

                            <template
                                v-if="
                                    !isCancelled &&
                                    canUpgrade &&
                                    plan.plan_code === HYBRID_PLAN_CODE &&
                                    plan.plan_id !== currentPlan?.plan_id
                                "
                            >
                                <span
                                    class="flex items-center gap-1.5 text-[11px] font-semibold text-primary"
                                >
                                    Upgrade today · ₱{{ formatMoney(upgradeCost(plan)) }}
                                    for the {{ upgradeMonths }} remaining
                                    {{ upgradeMonths === 1 ? "month" : "months" }}

                                    <span
                                        role="button"
                                        tabindex="0"
                                        aria-label="How is this calculated?"
                                        class="rounded-full p-0.5 text-primary/70 transition hover:bg-primary/10 hover:text-primary"
                                        @click.stop="showUpgradeMath = !showUpgradeMath"
                                        @keydown.enter.stop.prevent="showUpgradeMath = !showUpgradeMath"
                                    >
                                        <Info class="h-3.5 w-3.5" />
                                    </span>
                                </span>

                                <span
                                    v-if="showUpgradeMath"
                                    class="flex flex-col gap-1 rounded-lg bg-white/70 p-3 text-[11px] text-slate-600 dark:bg-white/5 dark:text-gray-300"
                                    @click.stop
                                >
                                    <span class="font-semibold text-slate-700 dark:text-white">
                                        New plan · {{ plan.name }}
                                    </span>
                                    <span class="flex justify-between gap-3">
                                        <span>Plan price</span>
                                        <span>₱{{ formatMoney(planPrice(plan)) }} / year</span>
                                    </span>
                                    <span
                                        v-if="additionalBranches"
                                        class="flex justify-between gap-3"
                                    >
                                        <span>
                                            + {{ additionalBranches }} additional
                                            {{ additionalBranches === 1 ? "branch" : "branches" }}
                                            × ₱{{ formatMoney(Number(plan.additional_branch_price) || 0) }}
                                        </span>
                                        <span>
                                            ₱{{ formatMoney(additionalBranches * (Number(plan.additional_branch_price) || 0)) }} / year
                                        </span>
                                    </span>
                                    <span class="flex justify-between gap-3 font-semibold">
                                        <span>Yearly total</span>
                                        <span>₱{{ formatMoney(planTotal(plan)) }} / year</span>
                                    </span>

                                    <span class="mt-2 font-semibold text-slate-700 dark:text-white">
                                        Current plan · {{ currentPlan?.name }}
                                    </span>
                                    <span class="flex justify-between gap-3">
                                        <span>Plan price</span>
                                        <span>₱{{ formatMoney(planPrice(currentPlan)) }} / year</span>
                                    </span>
                                    <span
                                        v-if="additionalBranches"
                                        class="flex justify-between gap-3"
                                    >
                                        <span>
                                            + {{ additionalBranches }} additional
                                            {{ additionalBranches === 1 ? "branch" : "branches" }}
                                            × ₱{{ formatMoney(Number(currentPlan?.additional_branch_price) || 0) }}
                                        </span>
                                        <span>
                                            ₱{{ formatMoney(additionalBranches * (Number(currentPlan?.additional_branch_price) || 0)) }} / year
                                        </span>
                                    </span>
                                    <span class="flex justify-between gap-3 font-semibold">
                                        <span>Yearly total</span>
                                        <span>₱{{ formatMoney(planTotal(currentPlan)) }} / year</span>
                                    </span>

                                    <span
                                        class="mt-2 flex justify-between gap-3 border-t border-slate-200 pt-2 dark:border-white/10"
                                    >
                                        <span>
                                            Difference (₱{{ formatMoney(planTotal(plan)) }} − ₱{{ formatMoney(planTotal(currentPlan)) }})
                                            ÷ {{ TERM_MONTHS }} months
                                        </span>
                                        <span>₱{{ formatMoney(monthlyDifference(plan)) }} / month</span>
                                    </span>
                                    <span class="flex justify-between gap-3">
                                        <span>× {{ upgradeMonths }} remaining {{ upgradeMonths === 1 ? "month" : "months" }}</span>
                                        <span class="font-semibold text-primary">
                                            ₱{{ formatMoney(upgradeCost(plan)) }}
                                        </span>
                                    </span>
                                    <span class="pt-1 text-slate-500 dark:text-gray-400">
                                        {{
                                            isTest
                                                ? "Your paid year hasn't started yet, so all 12 months are counted."
                                                : "The month you're in counts as a full month, even if only one day of it is used."
                                        }}
                                    </span>
                                </span>
                            </template>

                            <span
                                v-else-if="
                                    !isCancelled &&
                                    plan.plan_id !== currentPlan?.plan_id &&
                                    daysRemaining > 0
                                "
                                class="text-[11px] font-semibold text-slate-500 dark:text-gray-400"
                            >
                                Starts {{ formatDate(subscription?.end_date) }}
                            </span>
                        </button>
                    </div>

                    <p
                        v-if="isUpgrading"
                        class="mt-3 text-xs leading-5 text-slate-500 dark:text-gray-400"
                    >
                        The upgrade starts today and keeps your end date. You
                        pay the monthly difference for each month left — the
                        current month counts as a full month.
                    </p>

                    <p
                        v-else-if="changeStartsAt"
                        class="mt-3 text-xs leading-5 text-slate-500 dark:text-gray-400"
                    >
                        {{ currentPlan?.name }} stays active until
                        {{ formatDate(changeStartsAt) }}, then
                        {{ chargedPlan?.name }} takes over for a year.
                    </p>
                </div>

                <template v-if="showPlanPicker && canSubmitRenewal">
                    <div
                        class="mt-5 flex flex-col gap-4 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-white/10"
                    >
                        <div class="min-w-0">
                            <p class="flex items-baseline gap-2">
                                <span
                                    class="text-lg font-bold text-slate-900 dark:text-white"
                                >
                                    ₱{{ formatMoney(renewTotal) }}
                                </span>
                            </p>

                            <p
                                class="mt-0.5 text-xs leading-5 text-slate-500 dark:text-gray-400"
                            >
                                {{ chargedPlan?.name }}
                                {{ planTypeLabel(planType) }} ·
                                {{ branchLimitText(planType).toLowerCase() }} ·
                                covered until
                                {{ formatDate(projectedEndDate) }}
                                <template v-if="additionalBranches">
                                    · includes {{ additionalBranches }}
                                    additional
                                    {{ additionalBranches === 1 ? "branch" : "branches" }}
                                </template>
                                <template v-if="isUpgrading">
                                    · upgrade starts today
                                </template>
                            </p>
                        </div>

                        <button
                            type="button"
                            :disabled="!canSubmitRenewal"
                            class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="openRenew"
                        >
                            <RefreshCw class="h-4 w-4" />
                            {{
                                isCancelled
                                    ? "Subscribe now"
                                    : changesPlan
                                      ? paymentCopy.heading
                                      : "Renew subscription"
                            }}
                        </button>
                    </div>
                </template>
            </div>

            <div
                v-if="payments.length"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-white/10"
                >
                    <h3
                        class="text-sm font-bold text-slate-900 dark:text-white"
                    >
                        Payment history
                    </h3>

                    <button
                        v-if="payments.length > RECENT_PAYMENTS"
                        type="button"
                        class="text-xs font-semibold text-primary hover:underline"
                        @click="showAllPayments = true"
                    >
                        View all
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px]">
                        <thead>
                            <tr class="bg-slate-50/60 dark:bg-white/5">
                                <th
                                    v-for="head in [
                                        'Reference',
                                        'Plan',
                                        'Plan Type',
                                        'Type',
                                        'Method',
                                        'Account',
                                        'Amount',
                                        'Date',
                                        'Status',
                                    ]"
                                    :key="head"
                                    class="px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                                >
                                    {{ head }}
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-slate-100 dark:divide-white/10"
                        >
                            <tr
                                v-for="payment in recentPayments"
                                :key="payment.subscription_payment_id"
                                class="transition hover:bg-slate-50/60 dark:hover:bg-white/5"
                            >
                                <td
                                    class="px-4 py-2.5 text-xs font-medium text-slate-700 dark:text-gray-300"
                                >
                                    <a
                                        v-if="payment.payment_reference_id"
                                        :href="
                                            subscriptionInvoiceLink(
                                                payment.payment_reference_id,
                                                props.uuid,
                                            )
                                        "
                                        target="_blank"
                                        rel="noopener"
                                        class="text-primary hover:underline dark:text-primary-300"
                                    >
                                        {{
                                            payment.xendit_invoice_id ??
                                            payment.payment_reference_id
                                        }}
                                    </a>

                                    <span v-else>
                                        {{ payment.xendit_invoice_id ?? "—" }}
                                    </span>
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    {{ payment.plan_name ?? "—" }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    {{ planTypeLabel(payment.plan_type) || "—" }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-2.5">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                                        :class="
                                            payment.type === 'renewal'
                                                ? 'bg-primary-50 text-primary dark:bg-primary-500/10 dark:text-primary-300'
                                                : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-300'
                                        "
                                    >
                                        {{ paymentTypeLabel(payment.type) }}
                                    </span>

                                    <p
                                        v-if="payment.type === 'additional_branch' && payment.branch_name"
                                        class="mt-1 text-[11px] text-slate-500 dark:text-gray-400"
                                    >
                                        {{ payment.branch_name }}
                                    </p>
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    {{ payment.payment_method ?? "—" }}
                                </td>
                                <td
                                    class="px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    {{ paymentAccount(payment) }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-2.5 text-xs font-semibold text-slate-800 dark:text-white"
                                >
                                    ₱{{ formatMoney(payment.price) }}
                                </td>
                                <td
                                    class="whitespace-nowrap px-4 py-2.5 text-xs text-slate-500 dark:text-gray-400"
                                >
                                    {{ formatDate(payment.created_at) }}
                                </td>
                                <td class="px-4 py-2.5">
                                    <span
                                        class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                                    >
                                        {{ payment.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <SubscriptionPaymentsModal
            :open="showAllPayments"
            :payments="payments"
            :branch-uuid="props.uuid"
            @close="showAllPayments = false"
        />

        <Teleport to="body">
            <div
                v-if="showRenew"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
                @click.self="closeRenew"
            >
                <div
                    class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Renew subscription"
                >
                    <div
                        class="flex shrink-0 items-center justify-between gap-4 border-b border-gray-100 px-6 py-5 dark:border-white/10"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <RefreshCw class="h-5 w-5" />
                            </div>

                            <div>
                                <h2
                                    class="text-lg font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ paymentCopy.heading }}
                                </h2>
                                <p
                                    class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"
                                >
                                    {{ paymentCopy.subheading }}
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            aria-label="Close dialog"
                            :disabled="processing"
                            class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 disabled:opacity-40 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                            @click="closeRenew"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                        <PaymentForm
                            v-model:card="card"
                            :total-amount="renewTotal"
                            :processing="processing"
                            :onCardPay="payCard"
                            :onGCashPay="payGCash"
                            :title="paymentCopy.formTitle"
                            :description="paymentCopy.description"
                            :submit-label="paymentCopy.submitLabel"
                            gcash-processing-label="Waiting for GCash payment..."
                            terms-context="subscription"
                        />
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import {
    CalendarX,
    CheckCircle2,
    CircleX,
    Clock,
    Info,
    RefreshCw,
    TriangleAlert,
    X,
} from "lucide-vue-next";

import PaymentForm from "~/components/forms/PaymentForm.vue";
import SubscriptionPaymentsModal from "~/components/sections/owner/SubscriptionPaymentsModal.vue";
import {
    paymentAccount,
    paymentTypeLabel,
    subscriptionInvoiceLink,
} from "~/utils/subscriptionInvoice";
import { formatAmount } from "~/utils/currency";
import { subscriptionService } from "~/api/subscription/SubscriptionService";
import { planService } from "~/api/plan/PlanService";
import { paymentService } from "~/api/payment/PaymentService";
import { cardPayment, gcashPayment } from "~/composables/usePayment";
import { useToast } from "~/composables/useToast";
import { useBranchStore } from "~/stores/branch";
import type { CardDetails } from "~/types/payment";
import PlanTypeToggle from "~/components/ui/PlanTypeToggle.vue";
import {
    DEFAULT_PLAN_TYPE,
    findPlan,
    branchLimitText,
    planPrice,
    plansOfType,
    planTypeLabel,
    type PlanType,
} from "~/utils/planType";
import { planIcon } from "~/utils/planIcon";

const props = defineProps<{
    uuid: string;
}>();

const { success, error, info } = useToast();

const HYBRID_PLAN_CODE = "C";
const TERM_MONTHS = 12;

const loading = ref(true);
const processing = ref(false);
const showRenew = ref(false);

const subscription = ref<any>(null);
const plans = ref<any[]>([]);

const branchStore = useBranchStore();

const isTest = computed(() => subscription.value?.mode === "test");

const isCancelled = computed(
    () => subscription.value?.subscription?.status === "cancelled",
);

const syncBranchMode = () => {
    const branch = branchStore.activeBranch;

    if (!branch || !subscription.value) return;

    branch.subscription_mode =
        subscription.value.status === "cancelled" ? null : subscription.value.mode;
    branch.subscription_end_date = subscription.value.end_date;
};

const card = ref<CardDetails>({
    number: "4000000000001000",
    expMonth: "04",
    expYear: "29",
    cvc: "123",
    firstName: "prince",
    lastName: "sestoso",
    email: "prince.sestoso@gmail.com",
});

const RECENT_PAYMENTS = 5;

const payments = computed(() => subscription.value?.payments ?? []);

const recentPayments = computed(() =>
    [...payments.value]
        .sort((a, b) => (b.created_at ?? "").localeCompare(a.created_at ?? ""))
        .slice(0, RECENT_PAYMENTS),
);

const showAllPayments = ref(false);

const coveredBranches = computed<
    { uuid: string; name: string; status: string }[]
>(() => subscription.value?.subscription?.covered_branches ?? []);

const currentPlan = computed(() =>
    plans.value.find(
        (plan) => plan.plan_id === subscription.value?.plan?.plan_id,
    ),
);

const pendingPlan = computed(() => subscription.value?.pending_plan ?? null);

const hasPendingUpgrade = computed(
    () => Boolean(pendingPlan.value) && !pendingPlan.value.is_due,
);

const confirmCancelPending = ref(false);
const cancellingPending = ref(false);

const pendingPlanPaid = computed(() => {
    const latestPaid = [...payments.value]
        .filter((payment: any) => payment.status === "paid")
        .sort((a: any, b: any) => (b.created_at ?? "").localeCompare(a.created_at ?? ""))[0];

    return Number(latestPaid?.price) || 0;
});

const canCancel = computed(
    () => Boolean(subscription.value?.renewal?.can_cancel),
);

const confirmCancel = ref(false);
const cancelling = ref(false);

const refundAmount = computed(() =>
    payments.value
        .filter((payment: any) => payment.status === "paid")
        .reduce((sum: number, payment: any) => sum + Number(payment.price || 0), 0),
);

const refundedAmount = computed(() =>
    payments.value
        .filter((payment: any) => payment.status === "refunded")
        .reduce((sum: number, payment: any) => sum + Number(payment.price || 0), 0),
);

const renewal = computed(() => subscription.value?.renewal ?? null);
const canRenew = computed(() => Boolean(renewal.value?.can_renew));
const canUpgrade = computed(() => Boolean(renewal.value?.can_upgrade));
const upgradeMonths = computed(() => Number(renewal.value?.upgrade_months) || 0);
const windowDays = computed(() => renewal.value?.window_days ?? 7);
const renewalOpensAt = computed(() => renewal.value?.opens_at ?? null);

const currentType = computed<PlanType>(
    () => subscription.value?.plan?.type ?? DEFAULT_PLAN_TYPE,
);

const planType = ref<PlanType>(DEFAULT_PLAN_TYPE);

watch(currentType, (type) => (planType.value = type), { immediate: true });

const hybridPlan = computed(() =>
    findPlan(plans.value, HYBRID_PLAN_CODE, currentType.value),
);

const isOnHybrid = computed(
    () => currentPlan.value?.plan_code === HYBRID_PLAN_CODE,
);

const selectablePlans = computed(() => {
    if (isCancelled.value) return plansOfType(plans.value, planType.value);

    if (isOnHybrid.value) {
        return canRenew.value ? plansOfType(plans.value, currentType.value) : [];
    }

    return [
        canRenew.value ? currentPlan.value : null,
        canRenew.value || canUpgrade.value ? hybridPlan.value : null,
    ].filter(Boolean);
});

const showPlanPicker = computed(() => selectablePlans.value.length > 0);

const chosenPlanCode = ref<string | null>(null);

watch(
    [() => currentPlan.value?.plan_code, canRenew, canUpgrade],
    ([code]) =>
        (chosenPlanCode.value =
            !isCancelled.value && !canRenew.value && canUpgrade.value
                ? HYBRID_PLAN_CODE
                : (code ?? null)),
    { immediate: true },
);

const chargedPlan = computed(() =>
    findPlan(plans.value, chosenPlanCode.value, planType.value),
);

const changesPlan = computed(
    () =>
        !isCancelled.value &&
        Boolean(chargedPlan.value) &&
        chargedPlan.value?.plan_id !== subscription.value?.plan?.plan_id,
);

const isUpgrading = computed(
    () =>
        changesPlan.value &&
        canUpgrade.value &&
        chargedPlan.value?.plan_code === HYBRID_PLAN_CODE,
);

const changeStartsAt = computed(() =>
    changesPlan.value && !isUpgrading.value && daysRemaining.value > 0
        ? subscription.value?.end_date
        : null,
);

const showUpgradeMath = ref(false);

const additionalBranches = computed(
    () => Number(subscription.value?.plan?.additional_branches) || 0,
);

const planTotal = (plan: any) =>
    planPrice(plan) +
    additionalBranches.value * (Number(plan?.additional_branch_price) || 0);

const monthlyDifference = (plan: any) =>
    (planTotal(plan) - planTotal(currentPlan.value)) / TERM_MONTHS;

const upgradeCost = (plan: any) =>
    Math.round(monthlyDifference(plan) * upgradeMonths.value * 100) / 100;

const renewTotal = computed(() =>
    isUpgrading.value
        ? upgradeCost(chargedPlan.value)
        : planTotal(chargedPlan.value),
);

const canSubmitRenewal = computed(() =>
    isCancelled.value
        ? Boolean(chargedPlan.value)
        : isUpgrading.value
          ? canUpgrade.value
          : canRenew.value,
);

const daysRemaining = computed(() => {
    if (!subscription.value?.end_date) return 0;

    const end = new Date(subscription.value.end_date).getTime();
    const diff = end - Date.now();

    return Math.ceil(diff / 86_400_000);
});

const isExpired = computed(() => daysRemaining.value < 0);
const isExpiringSoon = computed(
    () => !isExpired.value && daysRemaining.value <= 14,
);

const statusTone = computed(() => {
    if (isCancelled.value) {
        return {
            border: "border-slate-200 dark:border-white/10",
            bg: "bg-slate-50/60 dark:bg-white/5",
            divider: "border-slate-200/70 dark:border-white/10",
            icon: "text-slate-500 dark:text-gray-400",
            glyph: CircleX,
            badge: "bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300",
            dot: "bg-slate-400",
            text: "text-slate-600 dark:text-gray-300",
        };
    }

    if (isExpired.value) {
        return {
            border: "border-rose-200 dark:border-rose-500/30",
            bg: "bg-rose-50/60 dark:bg-rose-500/10",
            divider: "border-rose-200/70 dark:border-rose-500/20",
            icon: "text-rose-600 dark:text-rose-300",
            glyph: TriangleAlert,
            badge: "bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300",
            dot: "bg-rose-500",
            text: "text-rose-600 dark:text-rose-400",
        };
    }

    if (isExpiringSoon.value) {
        return {
            border: "border-amber-200 dark:border-amber-500/30",
            bg: "bg-amber-50/60 dark:bg-amber-500/10",
            divider: "border-amber-200/70 dark:border-amber-500/20",
            icon: "text-amber-600 dark:text-amber-300",
            glyph: Clock,
            badge: "bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300",
            dot: "bg-amber-500",
            text: "text-amber-600 dark:text-amber-400",
        };
    }

    return {
        border: "border-emerald-200 dark:border-emerald-500/30",
        bg: "bg-emerald-50/60 dark:bg-emerald-500/10",
        divider: "border-emerald-200/70 dark:border-emerald-500/20",
        icon: "text-emerald-600 dark:text-emerald-300",
        glyph: CheckCircle2,
        badge: "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300",
        dot: "bg-emerald-500",
        text: "text-emerald-600 dark:text-emerald-400",
    };
});

const addYear = (date: string | number | Date) => {
    const projected = new Date(date);
    projected.setFullYear(projected.getFullYear() + 1);
    return projected.toISOString();
};

const projectedEndDate = computed(() => {
    const end = subscription.value?.end_date
        ? new Date(subscription.value.end_date)
        : new Date();

    if (isUpgrading.value) {
        return isTest.value && pendingPlan.value?.starts_at
            ? addYear(pendingPlan.value.starts_at)
            : end.toISOString();
    }

    return addYear(!isCancelled.value && end.getTime() > Date.now() ? end : new Date());
});

const paymentCopy = computed(() => {
    const coverage =
        coveredBranches.value.length > 1
            ? `${coveredBranches.value.length} branches`
            : "this branch";
    const until = formatDate(projectedEndDate.value);
    const newPlan = `${chargedPlan.value?.name ?? ""} ${planTypeLabel(chargedPlan.value?.type)}`.trim();

    if (isCancelled.value) {
        return {
            heading: "Subscribe",
            subheading: `${newPlan} covers ${coverage} until ${until}`,
            formTitle: "Subscription payment",
            description: "Choose how to pay for your subscription.",
            submitLabel: "Confirm & subscribe",
        };
    }

    if (isUpgrading.value) {
        const months = `${upgradeMonths.value} ${upgradeMonths.value === 1 ? "month" : "months"}`;

        return {
            heading: "Upgrade to Hybrid",
            subheading: `${newPlan} starts today and runs to ${until} — prorated for ${months}`,
            formTitle: "Upgrade payment",
            description: `Choose how to pay for ${newPlan}.`,
            submitLabel: "Confirm upgrade",
        };
    }

    if (changesPlan.value) {
        const starts = changeStartsAt.value
            ? `starts ${formatDate(changeStartsAt.value)}`
            : "starts today";

        return {
            heading: "Renew & change plan",
            subheading: `${newPlan} ${starts} and runs to ${until}`,
            formTitle: "Renewal payment",
            description: `Choose how to pay for ${newPlan}.`,
            submitLabel: "Confirm renewal",
        };
    }

    return {
        heading: "Renew subscription",
        subheading: `Extends ${coverage} to ${until}`,
        formTitle: "Renewal payment",
        description: "Choose how to pay for this branch's renewal.",
        submitLabel: "Confirm renewal",
    };
});

const fetchSubscription = async (silent = false) => {
    if (silent !== true) loading.value = true;

    try {
        const [subRes, planRes] = await Promise.all([
            subscriptionService.list({
                branch_uuid: props.uuid,
                per_page: 1,
            }),
            planService.list(),
        ]);

        subscription.value = subRes?.data?.[0] ?? null;
        plans.value = planRes ?? [];
    } catch (err: any) {
        error(err?.message ?? "Failed to load subscription.");
    } finally {
        loading.value = false;
    }
};

const openRenew = () => {
    if (!canSubmitRenewal.value) {
        error(`Renewal opens on ${formatDate(renewalOpensAt.value)}.`);
        return;
    }

    if (!renewTotal.value) {
        error("This plan has no price set.");
        return;
    }

    showRenew.value = true;
};

const closeRenew = () => {
    if (processing.value) return;
    showRenew.value = false;
};

const payCard = async () => {
    if (processing.value) return;

    processing.value = true;

    try {
        await cardPayment({
            card: card.value,
            amount: renewTotal.value,

            onClose: () => {
                processing.value = false;
            },

            createPayment: ({ token_id, authentication_id }) =>
                subscriptionService.renew({
                    branch_uuid: props.uuid,
                    payment_method: "CREDIT-CARD",
                    plan_code: chargedPlan.value?.plan_code,
                    plan_type: chargedPlan.value?.type,
                    token_id,
                    authentication_id,
                }),

            onSuccess: async (result: any) => {
                success(result?.message ?? "Subscription renewed.");

                if (result?.subscription) {
                    const { payment, ...changes } = result.subscription;

                    subscription.value = {
                        ...subscription.value,
                        ...changes,
                        subscription: {
                            ...subscription.value?.subscription,
                            status: changes.status,
                        },
                        payments: payment
                            ? [...payments.value, payment]
                            : payments.value,
                    };

                    syncBranchMode();
                }

                showRenew.value = false;
            },
        });
    } catch (err: any) {
        error(err?.message ?? "Renewal payment failed.");
    } finally {
        processing.value = false;
    }
};

const waitForGcashConfirmation = async (reference?: string) => {
    if (!reference) return { status: "unknown" as const, message: null };

    for (let attempt = 0; attempt < 10; attempt++) {
        try {
            const result = await paymentService.checkStatus(reference);

            if (result.status === "submitted" || result.status === "failed") {
                return result;
            }
        } catch {
            break;
        }

        await new Promise((resolve) => setTimeout(resolve, 1500));
    }

    return { status: "pending" as const, message: null };
};

const finishGcashPayment = async (reference?: string) => {
    const outcome = await waitForGcashConfirmation(reference);

    if (outcome.status === "submitted") {
        await fetchSubscription(true);
        syncBranchMode();
        success(outcome.message ?? "Subscription renewed.");
    } else if (outcome.status === "failed") {
        error(
            "We couldn't record your GCash payment. If you were charged, it will be refunded.",
        );
    } else {
        info(
            "GCash payment received. It will appear here as soon as it is confirmed.",
        );
    }
};

const payGCash = async () => {
    if (processing.value) return;

    processing.value = true;

    try {
        await gcashPayment({
            createPayment: () =>
                subscriptionService.renew({
                    branch_uuid: props.uuid,
                    payment_method: "GCASH",
                    plan_code: chargedPlan.value?.plan_code,
                    plan_type: chargedPlan.value?.type,
                }),

            onClose: () => {
                processing.value = false;
            },

            onSuccess: async (invoice: any) => {
                showRenew.value = false;
                await finishGcashPayment(invoice?.external_id);
            },
        });
    } catch (err: any) {
        error(err?.message ?? "Renewal payment failed.");
    } finally {
        processing.value = false;
    }
};

const cancelPendingPlan = async () => {
    if (cancellingPending.value) return;

    cancellingPending.value = true;

    try {
        const result = await subscriptionService.cancelPendingPlan({
            branch_uuid: props.uuid,
        });

        subscription.value = {
            ...subscription.value,
            ...result.subscription,
        };

        confirmCancelPending.value = false;
        success(result?.message ?? "Plan change cancelled.");
    } catch (err: any) {
        error(err?.message ?? "Could not cancel the plan change.");
    } finally {
        cancellingPending.value = false;
    }
};

const cancelTest = async () => {
    if (cancelling.value) return;

    cancelling.value = true;

    try {
        const result = await subscriptionService.cancelTest({
            branch_uuid: props.uuid,
        });

        subscription.value = {
            ...subscription.value,
            ...result.subscription,
            subscription: {
                ...subscription.value?.subscription,
                status: result.subscription?.status,
            },
        };

        confirmCancel.value = false;
        syncBranchMode();
        success(result?.message ?? "Subscription cancelled.");
    } catch (err: any) {
        error(err?.message ?? "Could not cancel the subscription.");
    } finally {
        cancelling.value = false;
    }
};

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

const formatMoney = (value: number | string) =>
    formatAmount(value, { treatMissingAsZero: true });

onMounted(fetchSubscription);
</script>
