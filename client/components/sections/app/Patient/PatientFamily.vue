<template>
    <section>
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold text-secondary dark:text-white">
                    Family
                </h2>

                <p class="mt-1 text-xs text-muted dark:text-gray-400">
                    The people who can view and act for this patient.
                </p>
            </div>

            <span
                class="rounded-full bg-primary-50 px-3 py-1 text-xs font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
            >
                {{ family.length }}
            </span>
        </div>

        <div v-if="family.length" class="grid gap-3 sm:grid-cols-2">
            <article
                v-for="member in family"
                :key="member.patient_access_id"
                class="rounded-xl border border-primary-100 bg-white p-4 transition dark:border-primary-500/20 dark:bg-secondary"
                :class="
                    member.have_access
                        ? ''
                        : 'opacity-70'
                "
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-primary/10 text-sm font-semibold text-primary"
                    >
                        <img
                            v-if="member.client?.avatar"
                            :src="member.client.avatar"
                            alt=""
                            class="h-full w-full object-cover"
                        />

                        <template v-else>
                            {{ initials(member.client?.full_name) }}
                        </template>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p
                                class="truncate text-sm font-semibold text-secondary dark:text-white"
                            >
                                {{ member.client?.full_name ?? "Unnamed" }}
                            </p>

                            <span
                                v-if="member.is_primary"
                                class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary"
                            >
                                Primary
                            </span>

                            <span
                                v-if="!member.have_access"
                                class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-white/10 dark:text-gray-400"
                            >
                                No access
                            </span>
                        </div>

                        <p
                            class="mt-0.5 text-xs font-medium capitalize text-primary-700 dark:text-primary-300"
                        >
                            {{ member.relationship_type || "Relationship not set" }}
                        </p>

                        <dl class="mt-3 space-y-1.5">
                            <div
                                v-for="detail in details(member)"
                                :key="detail.label"
                                class="flex items-start gap-2"
                            >
                                <component
                                    :is="detail.icon"
                                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-gray-500"
                                />

                                <dd
                                    class="min-w-0 truncate text-xs text-slate-600 dark:text-gray-300"
                                    :title="detail.value"
                                >
                                    {{ detail.value }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </article>
        </div>

        <div
            v-else
            class="rounded-xl border border-dashed border-primary-100 px-6 py-10 text-center dark:border-primary-500/20"
        >
            <p class="text-sm font-semibold text-secondary dark:text-white">
                No family recorded
            </p>

            <p class="mt-1 text-xs text-muted dark:text-gray-400">
                Nobody has been given access to this patient yet.
            </p>
        </div>
    </section>
</template>

<script setup lang="ts">
import { Briefcase, Mail, Phone } from "lucide-vue-next";

export interface FamilyMember {
    patient_access_id: number;
    relationship_type: string | null;
    have_access: boolean;
    is_primary: boolean;
    client: {
        client_id: number;
        full_name: string | null;
        phone_number: string | null;
        email: string | null;
        occupation: string | null;
        avatar: string | null;
    } | null;
}

defineProps<{
    family: FamilyMember[];
}>();

function initials(name?: string | null) {
    const parts = (name ?? "").trim().split(/\s+/).filter(Boolean);

    if (!parts.length) return "—";

    return (
        (parts[0]?.[0] ?? "") + (parts.length > 1 ? parts[parts.length - 1][0] : "")
    ).toUpperCase();
}

// Contact rows are dropped when empty rather than shown as a blank line.
function details(member: FamilyMember) {
    return [
        { label: "phone", value: member.client?.phone_number, icon: Phone },
        { label: "email", value: member.client?.email, icon: Mail },
        {
            label: "occupation",
            value: member.client?.occupation,
            icon: Briefcase,
        },
    ].filter((detail) => !!detail.value);
}
</script>
