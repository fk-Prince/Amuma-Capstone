<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { X, Building2, Mail, Phone } from "lucide-vue-next";

import MessageAvatar from "~/components/messaging/MessageAvatar.vue";

import type { CareTeamMember, PortalContact } from "~/types/message";

const props = defineProps<{
    open: boolean;
    contacts: PortalContact[];
    loading?: boolean;
}>();

const emit = defineEmits<{
    close: [];
    select: [contact: PortalContact, member: CareTeamMember];
}>();

const PAGE_SIZE = 10;

const onlyAssigned = ref(true);
const visibleCount = ref(PAGE_SIZE);

watch(
    () => props.open,
    (open) => {
        if (open) {
            onlyAssigned.value = true;
            visibleCount.value = PAGE_SIZE;
        }
    },
);

watch(onlyAssigned, () => {
    visibleCount.value = PAGE_SIZE;
});

const groups = computed(() => {
    let remaining = visibleCount.value;

    return props.contacts.map((contact) => {
        const members = onlyAssigned.value
            ? contact.care_team.filter((member) => member.assigned)
            : contact.care_team;

        const visible = members.slice(0, Math.max(0, remaining));

        remaining -= visible.length;

        return { contact, members, visible };
    });
});

const shownCount = computed(() =>
    groups.value.reduce((total, group) => total + group.visible.length, 0),
);

const matchingCount = computed(() =>
    groups.value.reduce((total, group) => total + group.members.length, 0),
);

const hasMore = computed(() => shownCount.value < matchingCount.value);

function loadMore() {
    visibleCount.value += PAGE_SIZE;
}

const assignedCount = computed(() =>
    props.contacts.reduce(
        (total, contact) =>
            total + contact.care_team.filter((member) => member.assigned).length,
        0,
    ),
);

const allCount = computed(() =>
    props.contacts.reduce(
        (total, contact) => total + contact.care_team.length,
        0,
    ),
);
</script>

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
                v-if="open"
                class="fixed inset-0 z-[60] flex items-end justify-center bg-slate-900/50 p-0 backdrop-blur-sm sm:items-center sm:p-4"
                @click.self="emit('close')"
            >
                <Transition
                    appear
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="translate-y-4 opacity-0 sm:scale-95"
                    enter-to-class="translate-y-0 opacity-100 sm:scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="translate-y-0 opacity-100 sm:scale-100"
                    leave-to-class="translate-y-4 opacity-0 sm:scale-95"
                >
                    <div
                        v-if="open"
                        class="flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:max-w-lg sm:rounded-2xl dark:bg-secondary"
                    >
                        <div
                            class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-200 px-4 py-4 sm:px-5 dark:border-white/10"
                        >
                            <div class="min-w-0">
                                <p
                                    class="text-sm font-bold text-slate-800 sm:text-base dark:text-white"
                                >
                                    New message
                                </p>

                                <p
                                    class="mt-0.5 text-xs leading-4 text-slate-400 dark:text-gray-500"
                                >
                                    Pick the staff member you want to talk to.
                                </p>
                            </div>

                            <button
                                type="button"
                                aria-label="Close"
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-400"
                                @click="emit('close')"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div
                            v-if="contacts.length"
                            class="flex shrink-0 gap-1 border-b border-slate-100 p-2.5 dark:border-white/10"
                        >
                            <button
                                type="button"
                                class="flex-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                :class="
                                    onlyAssigned
                                        ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-200'
                                        : 'text-slate-500 hover:bg-slate-50 dark:text-gray-400 dark:hover:bg-white/5'
                                "
                                @click="onlyAssigned = true"
                            >
                                Assigned to my loved one ({{ assignedCount }})
                            </button>

                            <button
                                type="button"
                                class="flex-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                :class="
                                    !onlyAssigned
                                        ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-200'
                                        : 'text-slate-500 hover:bg-slate-50 dark:text-gray-400 dark:hover:bg-white/5'
                                "
                                @click="onlyAssigned = false"
                            >
                                All staff ({{ allCount }})
                            </button>
                        </div>

                        <div
                            class="min-h-0 flex-1 overflow-y-auto p-2.5 overscroll-contain"
                        >
                            <div v-if="loading" class="space-y-2 p-1">
                                <div
                                    v-for="n in 3"
                                    :key="n"
                                    class="h-20 animate-pulse rounded-xl bg-slate-100 dark:bg-white/10"
                                />
                            </div>

                            <p
                                v-else-if="!contacts.length"
                                class="px-3 py-10 text-center text-sm text-slate-400 dark:text-gray-500"
                            >
                                You have no loved ones to message about yet.
                            </p>

                            <p
                                v-else-if="!matchingCount"
                                class="px-3 py-10 text-center text-sm text-slate-400 dark:text-gray-500"
                            >
                                No one is assigned to your loved one's schedule
                                yet. Switch to all staff to reach the branch.
                            </p>

                            <div
                                v-for="group in groups"
                                :key="group.contact.patient_id"
                                class="mb-3 last:mb-0"
                            >
                                <div
                                    v-if="contacts.length > 1 && group.visible.length"
                                    class="px-1.5 pb-1.5"
                                >
                                    <p
                                        class="truncate text-xs font-bold text-slate-700 dark:text-gray-200"
                                    >
                                        {{ group.contact.patient_name }}
                                    </p>
                                </div>

                                <p
                                    v-if="
                                        group.contact.branch.name &&
                                        group.visible.length
                                    "
                                    class="flex items-center gap-1.5 px-1.5 pb-2 text-[11px] text-slate-400 dark:text-gray-500"
                                >
                                    <Building2 class="h-3.5 w-3.5 shrink-0" />
                                    <span class="truncate">
                                        {{ group.contact.branch.name }}
                                    </span>
                                </p>

                                <button
                                    v-for="member in group.visible"
                                    :key="member.employee_id"
                                    type="button"
                                    class="mb-1.5 w-full rounded-xl border border-slate-200 px-3.5 py-3 text-left transition hover:border-primary-200 hover:bg-primary-50/50 active:scale-[0.99] dark:border-white/10 dark:hover:border-primary-500/20 dark:hover:bg-primary-500/10"
                                    @click="emit('select', group.contact, member)"
                                >
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        <MessageAvatar
                                            :src="member.avatar"
                                            :name="member.name"
                                            size="md"
                                        />

                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex min-w-0 items-center justify-between gap-2"
                                            >
                                                <p
                                                    class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800 dark:text-white"
                                                >
                                                    {{ member.name }}
                                                </p>

                                                <span
                                                    v-if="member.conversation_id"
                                                    class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500 dark:bg-white/10 dark:text-gray-400"
                                                >
                                                    Existing
                                                </span>

                                                <span
                                                    v-else-if="member.role"
                                                    class="shrink-0 rounded-full bg-primary-50 px-2 py-0.5 text-[10px] font-semibold text-primary-700 dark:bg-primary-500/15 dark:text-primary-200"
                                                >
                                                    {{ member.role }}
                                                </span>
                                            </div>

                                            <p
                                                v-if="
                                                    member.conversation_id &&
                                                    member.role
                                                "
                                                class="mt-0.5 truncate text-[11px] font-semibold text-primary-600 dark:text-primary-300"
                                            >
                                                {{ member.role }}
                                            </p>

                                            <p
                                                v-if="member.email"
                                                class="mt-0.5 flex items-center gap-1.5 text-[11px] text-slate-400 dark:text-gray-500"
                                            >
                                                <Mail class="h-3 w-3 shrink-0" />
                                                <span class="truncate">
                                                    {{ member.email }}
                                                </span>
                                            </p>

                                            <p
                                                v-if="member.phone"
                                                class="mt-0.5 flex items-center gap-1.5 text-[11px] text-slate-400 dark:text-gray-500"
                                            >
                                                <Phone class="h-3 w-3 shrink-0" />
                                                <span class="truncate">
                                                    {{ member.phone }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </button>
                            </div>

                            <button
                                v-if="hasMore"
                                type="button"
                                class="mt-1 w-full rounded-xl border border-dashed border-slate-200 px-3 py-2.5 text-xs font-semibold text-slate-500 transition hover:border-primary-200 hover:text-primary-600 dark:border-white/10 dark:text-gray-400 dark:hover:border-primary-500/20 dark:hover:text-primary-300"
                                @click="loadMore"
                            >
                                Load more ({{ matchingCount - shownCount }} left)
                            </button>
                        </div>

                        <div
                            class="flex shrink-0 justify-end border-t border-slate-200 px-4 py-3 pb-[env(safe-area-inset-bottom)] sm:px-5 dark:border-white/10"
                        >
                            <button
                                type="button"
                                class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 dark:text-gray-400 dark:hover:bg-white/5"
                                @click="emit('close')"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
