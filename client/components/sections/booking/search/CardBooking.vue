<template>
    <div
        v-if="variant === 1"
        @mouseenter="$emit('hover', branch.uuid)"
        @mouseleave="$emit('hover', null)"
        class="group rounded-2xl border border-primary-200 bg-white overflow-hidden cursor-pointer shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:bg-secondary dark:border-primary-500/20"
        @click="$emit('select', branch)"
    >
        <div class="relative h-40 overflow-hidden bg-muted-light dark:bg-white/10">
            <img
                v-if="branch?.image && !imageBroken"
                :src="branch.image"
                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                alt="branch image"
                @error="imageBroken = true"
            />

            <div
                v-else
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-primary-50 via-white to-accent-50 dark:from-primary-500/10 dark:via-secondary dark:to-accent-500/10"
            >
                <img
                    :src="Logo"
                    class="h-16 w-16 object-contain opacity-70"
                    alt="default logo"
                />
            </div>

            <div
                v-if="branch?.image && !imageBroken"
                class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/40 to-transparent"
            />

            <span
                class="absolute left-3 top-3 rounded-md bg-white/95 px-2.5 py-1 text-xs font-semibold text-accent-700 shadow-sm backdrop-blur-sm dark:bg-secondary/90 dark:text-accent-400"
            >
                {{ hoursLabel(branch.settings) }}
            </span>

            <div
                class="absolute right-3 top-3 flex items-center gap-1 rounded-md bg-white/95 px-2.5 py-1 shadow-sm backdrop-blur-sm dark:bg-secondary/90"
            >
                <Star class="h-3 w-3 text-amber-400 fill-amber-400" />

                <span class="text-xs font-semibold text-secondary dark:text-white">
                    {{ branch.averageRating ?? "0.0" }}
                </span>

                <span v-if="branch.reviewCount > 0" class="text-xs text-muted dark:text-gray-400">
                    ({{ branch.reviewCount }})
                </span>
            </div>
        </div>

        <div class="p-4">
            <h3
                class="truncate font-semibold text-secondary transition-colors group-hover:text-primary dark:text-white"
            >
                {{ branch.name }}
            </h3>

            <p class="mt-1 flex items-center gap-1 text-xs text-muted dark:text-gray-400">
                <Location class="h-3.5 w-3.5 shrink-0" />
                <span class="truncate"
                    >{{ branch.location.street }},
                    {{ branch.location.city }}</span
                >
            </p>

            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                <template
                    v-for="subscription in branch.subscriptions"
                    :key="subscription.subscription_id"
                >
                    <template v-if="subscription.plans.name === 'Hybrid'">
                        <span
                            class="text-xs px-2 py-1 rounded-full font-semibold bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                        >
                            Homecare Services
                        </span>

                        <span
                            class="text-xs px-2 py-1 rounded-full font-semibold bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                        >
                            In-House Facility
                        </span>
                    </template>

                    <span
                        v-else
                        class="text-xs px-2 py-1 rounded-full font-semibold bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                    >
                        {{ subscription.plans.name }}
                    </span>
                </template>
            </div>
        </div>

        <div
            class="flex items-center justify-between gap-2 border-t border-muted-light bg-light px-4 py-3 dark:bg-white/5 dark:border-white/10"
        >
            <span
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide"
                :class="
                    branch.settings.is_open
                        ? 'bg-accent-50 text-accent-700'
                        : 'bg-danger/10 text-danger'
                "
            >
                <span
                    class="h-1.5 w-1.5 rounded-full"
                    :class="
                        branch.settings.is_open ? 'bg-accent-500' : 'bg-danger'
                    "
                />
                {{ branch.settings.is_open ? "Open" : "Closed" }}
            </span>

            <button
                @click.stop="$emit('select', branch)"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-primary-600"
            >
                Book Now
                <ArrowRight class="h-3 w-3" />
            </button>
        </div>
    </div>

    <div
        v-else-if="variant === 2"
        @mouseenter="$emit('hover', branch.uuid)"
        @mouseleave="$emit('hover', null)"
        class="group flex flex-col overflow-hidden rounded-2xl border border-muted-light bg-white shadow-sm transition-all duration-300 hover:shadow-md hover:border-primary-200 cursor-pointer md:flex-row dark:border-white/5 dark:bg-secondary dark:shadow-none dark:hover:border-primary-500/30"
    >
        <div class="relative w-full shrink-0 md:w-64">
            <div class="relative h-48 w-full overflow-hidden bg-muted-light dark:bg-white/10">
                <img
                    v-if="branch?.image && !imageBroken"
                    :src="branch.image"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                    alt="branch image"
                    @error="imageBroken = true"
                />

                <img
                    v-else
                    :src="Logo"
                    class="h-full w-full object-contain opacity-40 p-8"
                    alt="default logo"
                />

                <div
                    class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/50 to-transparent"
                />

                <span
                    class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold backdrop-blur-sm"
                    :class="
                        branch.settings.is_open
                            ? 'bg-emerald-500 text-white'
                            : 'bg-white/90 text-danger dark:bg-secondary/90 dark:text-danger'
                    "
                >
                    <span
                        v-if="branch.settings.is_open"
                        class="h-1.5 w-1.5 shrink-0 rounded-full bg-white"
                    />
                    {{ branch.settings.is_open ? "Open Now" : "Closed" }}
                </span>

                <button
                    type="button"
                    class="absolute right-3 top-3 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition-colors hover:bg-white dark:bg-secondary/90 dark:hover:bg-secondary"
                    :aria-label="isFavorited ? 'Remove from favorites' : 'Add to favorites'"
                    @click.stop="isFavorited = !isFavorited"
                >
                    <Heart
                        class="h-3.5 w-3.5"
                        :class="
                            isFavorited
                                ? 'fill-danger text-danger'
                                : 'text-muted dark:text-gray-300'
                        "
                    />
                </button>
            </div>

            <div
                v-if="extraImages.length"
                class="flex h-14 gap-1 bg-muted-light p-1 dark:bg-white/5"
            >
                <div
                    v-for="(img, i) in extraImages.slice(0, 3)"
                    :key="img.branch_image_id"
                    class="relative flex-1 overflow-hidden rounded-md bg-white/50 dark:bg-white/10"
                >
                    <img
                        :src="img.image_url"
                        class="h-full w-full object-cover"
                        alt=""
                    />
                    <div
                        v-if="i === 2 && remainingImageCount > 0"
                        class="absolute inset-0 flex items-center justify-center bg-black/55 text-xs font-semibold text-white"
                    >
                        +{{ remainingImageCount }}
                    </div>
                </div>
            </div>
        </div>

        <div class="flex min-w-0 flex-1 flex-col p-6">
            <div>
                <h3
                    class="text-lg font-semibold leading-snug tracking-tight text-secondary transition group-hover:text-primary dark:text-white"
                >
                    <span class="break-words">{{ branch.name }}</span>
                    <BadgeCheck
                        v-if="branch.is_verified"
                        class="ml-1.5 inline h-4 w-4 shrink-0 -translate-y-px fill-primary text-white"
                    />
                </h3>

                <div
                    class="mt-1.5 flex items-center gap-1.5 text-sm text-muted dark:text-gray-400"
                >
                    <Location class="h-4 w-4 shrink-0" />
                    <span class="min-w-0 line-clamp-1">
                        {{ branch.location.street }},
                        {{ branch.location.city }},
                        {{ branch.location.province }}
                    </span>
                </div>

                <div class="mt-1.5 flex items-center gap-1 text-sm">
                    <Star class="h-3.5 w-3.5 text-amber-400 fill-amber-400" />
                    <span class="font-semibold text-secondary dark:text-white">
                        {{ branch.averageRating ?? "0.0" }}
                    </span>
                    <span
                        v-if="branch.reviewCount > 0"
                        class="text-muted dark:text-gray-400"
                    >
                        ({{ branch.reviewCount }})
                    </span>
                </div>

                <p
                    class="mt-3 text-sm leading-6 text-muted line-clamp-2 dark:text-gray-300"
                >
                    {{ branch.description }}
                </p>
            </div>

            <div class="mt-4 flex flex-wrap gap-1.5">
                <template
                    v-for="p in branch.subscriptions"
                    :key="p.plans.plan_code ?? ''"
                >
                    <template v-if="p.plans.name === 'Hybrid'">
                        <span
                            class="rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300"
                        >
                            Homecare Service
                        </span>
                        <span
                            class="rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300"
                        >
                            Inhouse Facility
                        </span>
                    </template>

                    <span
                        v-else
                        class="rounded-full bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300"
                    >
                        {{ p.plans.name }}
                    </span>
                </template>
            </div>

            <div class="mt-4 flex items-center gap-2 text-sm">
                <svg
                    class="h-4 w-4 shrink-0"
                    :class="
                        getTime(branch.settings).is24Hours
                            ? 'text-accent-600 dark:text-accent-300'
                            : 'text-muted dark:text-gray-500'
                    "
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>

                <span
                    v-if="getTime(branch.settings).is24Hours"
                    class="inline-flex items-center gap-1 rounded-md bg-accent-50 px-2 py-0.5 font-semibold text-accent-700 dark:bg-accent-500/15 dark:text-accent-400"
                >
                    Open 24 Hours
                </span>

                <span v-else class="font-medium text-muted dark:text-gray-300">
                    {{ hoursLabel(branch.settings) }}
                </span>
            </div>
        </div>

        <div
            class="flex shrink-0 flex-row items-center justify-between gap-4 border-t border-muted-light p-6 md:w-56 md:flex-col md:items-end md:justify-center md:border-l md:border-t-0 dark:border-white/10"
        >
            <div v-if="priceLabel" class="text-right">
                <p class="text-xs text-muted dark:text-gray-400">Starting at</p>
                <p class="text-xl font-bold text-primary">
                    {{ priceLabel }}
                </p>
            </div>

            <button
                @click="$emit('select', branch)"
                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-600 md:w-full md:justify-center"
            >
                View Provider
                <ArrowRight class="h-3.5 w-3.5" />
            </button>
        </div>
    </div>

    <div
        v-else-if="variant === 3"
        @mouseenter="$emit('hover', branch.uuid)"
        @mouseleave="$emit('hover', null)"
        class="group flex flex-col overflow-hidden rounded-2xl border border-muted-light bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-md hover:border-primary-200 cursor-pointer dark:border-white/5 dark:bg-secondary dark:shadow-none dark:hover:border-primary-500/30"
        @click="$emit('select', branch)"
    >
        <div class="relative h-32 w-full shrink-0 overflow-hidden bg-muted-light dark:bg-white/10">
            <img
                v-if="branch?.image && !imageBroken"
                :src="branch.image"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                alt="branch image"
                @error="imageBroken = true"
            />
            <img
                v-else
                :src="Logo"
                class="h-full w-full object-contain opacity-40 p-8"
                alt="default logo"
            />

            <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/45 to-transparent" />

            <span
                class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold backdrop-blur-sm"
                :class="
                    branch.settings.is_open
                        ? 'bg-emerald-500 text-white'
                        : 'bg-white/90 text-danger dark:bg-secondary/90 dark:text-danger'
                "
            >
                <span
                    v-if="branch.settings.is_open"
                    class="h-1.5 w-1.5 shrink-0 rounded-full bg-white"
                />
                {{ branch.settings.is_open ? "Open Now" : "Closed" }}
            </span>

            <button
                type="button"
                class="absolute right-3 top-3 flex h-7 w-7 items-center justify-center rounded-full bg-white/90 shadow-sm backdrop-blur-sm transition-colors hover:bg-white dark:bg-secondary/90 dark:hover:bg-secondary"
                :aria-label="isFavorited ? 'Remove from favorites' : 'Add to favorites'"
                @click.stop="isFavorited = !isFavorited"
            >
                <Heart
                    class="h-3.5 w-3.5"
                    :class="isFavorited ? 'fill-danger text-danger' : 'text-muted dark:text-gray-300'"
                />
            </button>
        </div>

        <div class="flex flex-1 flex-col p-3.5">
            <h3
                class="flex min-w-0 items-center gap-1.5 line-clamp-1 text-sm font-bold text-secondary transition group-hover:text-primary dark:text-white"
            >
                <span class="truncate">{{ branch.name }}</span>
                <BadgeCheck
                    v-if="branch.is_verified"
                    class="h-3.5 w-3.5 shrink-0 fill-primary text-white"
                />
            </h3>

            <div class="mt-1 flex items-center gap-1 text-xs">
                <Star class="h-3.5 w-3.5 text-amber-400 fill-amber-400" />
                <span class="font-semibold text-secondary dark:text-white">
                    {{ branch.averageRating ?? "0.0" }}
                </span>
                <span v-if="branch.reviewCount > 0" class="text-muted dark:text-gray-400">
                    ({{ branch.reviewCount }})
                </span>
            </div>

            <div class="mt-1.5 flex items-center gap-1.5 text-xs text-muted dark:text-gray-400">
                <Location class="h-3.5 w-3.5 shrink-0" />
                <span class="line-clamp-1">
                    {{ branch.location.street }}, {{ branch.location.city }}
                </span>
            </div>

            <div class="mt-2.5 flex flex-wrap gap-1.5">
                <template v-for="p in branch.subscriptions" :key="p.plans.plan_code ?? ''">
                    <template v-if="p.plans.name === 'Hybrid'">
                        <span class="rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">
                            Homecare Services
                        </span>
                        <span class="rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300">
                            In-House Facility
                        </span>
                    </template>
                    <span
                        v-else
                        class="rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-300"
                    >
                        {{ p.plans.name }}
                    </span>
                </template>
            </div>

            <div class="mt-auto flex items-end justify-between gap-2 pt-4">
                <div v-if="priceLabel">
                    <p class="text-[11px] text-muted dark:text-gray-400">Starting at</p>
                    <p class="text-base font-bold text-primary">
                        {{ priceLabel }}
                    </p>
                </div>
                <div v-else />

                <div class="flex shrink-0 items-center gap-1.5">
                    <button
                        @click.stop="$emit('select', branch)"
                        class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg border border-muted-light px-3 py-1.5 text-xs font-semibold text-secondary transition-colors hover:border-primary hover:text-primary dark:border-white/10 dark:text-white"
                    >
                        View Provider
                    </button>
                    <button
                        @click.stop="$emit('select', branch)"
                        class="inline-flex items-center gap-1 whitespace-nowrap rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-primary-600"
                    >
                        Book Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import type { BranchRetrieve } from "~/types/branch";
import Location from "~/components/icons/location.vue";
import Logo from "~/assets/logo/logo.png";
import { Star, ArrowRight, Heart, BadgeCheck } from "lucide-vue-next";
import { getBranchTimeDisplay } from "~/utils/time";
import { formatCurrency } from "~/utils/currency";

const props = defineProps<{
    branch: BranchRetrieve;
    variant: 1 | 2 | 3;
}>();

defineEmits(["select", "hover"]);

const imageBroken = ref(false);

const isFavorited = ref(false);

const getTime = (settings: BranchRetrieve["settings"]) =>
    getBranchTimeDisplay(settings);

const hoursLabel = (settings: BranchRetrieve["settings"]) => {
    const hours = getBranchTimeDisplay(settings);

    return hours.time ? `Opens at ${hours.label}` : hours.label;
};

const extraImages = computed(() => props.branch.images ?? []);
const remainingImageCount = computed(() =>
    Math.max(extraImages.value.length - 3, 0),
);

const priceLabel = computed(() => {
    const price = props.branch.starting_price;

    return price ? formatCurrency(price.amount) : null;
});
</script>