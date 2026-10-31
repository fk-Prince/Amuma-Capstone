<script setup lang="ts">
import { onMounted, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    ArrowLeft,
    CheckCircle2,
    ChevronRight,
    ImagePlus,
    LoaderCircle,
    RotateCcw,
    Send,
    X,
} from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import SupportThread from "~/components/sections/support/SupportThread.vue";
import { supportService } from "~/api/support/SupportService";
import {
    applySupportUpdate,
    useSupportTicketLive,
    type SupportTicketUpdate,
} from "~/composables/useSupportTicketLive";
import { useToast } from "~/composables/useToast";
import {
    SUPPORT_CATEGORY_LABELS,
    SUPPORT_CATEGORY_OPTIONS,
    SUPPORT_STATUS_CLASSES,
    SUPPORT_STATUS_LABELS,
    type SupportCategory,
    type SupportReply,
    type SupportStatus,
    type SupportTicket,
} from "~/types/support";
import { stringToDateTime } from "~/utils/time";

const MAX_ATTACHMENT_BYTES = 5 * 1024 * 1024;
const PAGE_SIZE = 10;

const route = useRoute();
const router = useRouter();
const { success, error } = useToast();

const tab = ref<"new" | "tickets">("new");

const form = reactive({
    category: "" as SupportCategory | "",
    subject: "",
    message: "",
    attachment: null as File | null,
});

const errors = reactive<Record<string, string>>({});
const fileInput = ref<HTMLInputElement | null>(null);
const submitting = ref(false);

const tickets = ref<SupportTicket[]>([]);
const loading = ref(false);
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);

const active = ref<SupportTicket | null>(null);
const threadLoading = ref(false);
const sending = ref(false);
const savingStatus = ref(false);
const thread = ref<InstanceType<typeof SupportThread> | null>(null);

const fetchTickets = async (nextPage = 1) => {
    loading.value = true;

    try {
        const res: any = await supportService.list({
            page: nextPage,
            per_page: PAGE_SIZE,
        });

        const rows = (res.data ?? []) as SupportTicket[];
        tickets.value = nextPage === 1 ? rows : [...tickets.value, ...rows];
        page.value = res.meta?.current_page ?? nextPage;
        lastPage.value = res.meta?.last_page ?? 1;
        total.value = res.meta?.total ?? rows.length;
    } catch (err: any) {
        error(err?.message || "Unable to load your tickets.");
    } finally {
        loading.value = false;
    }
};

const openTicket = async (uuid: string) => {
    tab.value = "tickets";
    active.value = tickets.value.find((ticket) => ticket.uuid === uuid) ?? null;
    threadLoading.value = true;

    try {
        const res: any = await supportService.show(uuid);
        active.value = res.data ?? null;
    } catch (err: any) {
        active.value = null;
        error(err?.message || "Unable to open this ticket.");
    } finally {
        threadLoading.value = false;
    }
};

const closeTicket = () => {
    active.value = null;

    if (route.query.ticket) {
        router.replace({ query: { ...route.query, ticket: undefined } });
    }
};

const applyUpdate = (update: SupportTicketUpdate) => {
    tickets.value = tickets.value.map((ticket) =>
        ticket.uuid === update.uuid
            ? {
                  ...ticket,
                  status: update.status,
                  last_reply_at: update.reply?.created_at ?? ticket.last_reply_at,
              }
            : ticket,
    );

    if (active.value?.uuid === update.uuid) {
        active.value = applySupportUpdate(active.value, update);
    }
};

useSupportTicketLive(() => active.value?.uuid, applyUpdate);

const pickFile = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    (event.target as HTMLInputElement).value = "";

    if (!file) return;

    if (!file.type.startsWith("image/")) {
        errors.attachment = "Only images can be attached.";
        return;
    }

    if (file.size > MAX_ATTACHMENT_BYTES) {
        errors.attachment = "The screenshot must be 5 MB or smaller.";
        return;
    }

    errors.attachment = "";
    form.attachment = file;
};

const submit = async () => {
    errors.category = form.category ? "" : "Choose what this is about.";
    errors.subject = form.subject.trim() ? "" : "Add a short subject.";
    errors.message = form.message.trim() ? "" : "Describe your concern.";

    if (errors.category || errors.subject || errors.message) return;

    submitting.value = true;

    try {
        const res: any = await supportService.create({
            category: form.category as SupportCategory,
            subject: form.subject.trim(),
            message: form.message.trim(),
            attachment: form.attachment,
        });

        const ticket = res.data as SupportTicket;

        tickets.value = [ticket, ...tickets.value];
        total.value += 1;
        active.value = ticket;
        tab.value = "tickets";

        Object.assign(form, {
            category: "",
            subject: "",
            message: "",
            attachment: null,
        });

        success(res.message ?? "Your ticket has been sent.");
    } catch (err: any) {
        Object.entries(err?.errors ?? {}).forEach(([field, messages]) => {
            errors[field] = (messages as string[])[0] ?? "";
        });
        error(err?.message || "Unable to send your message.");
    } finally {
        submitting.value = false;
    }
};

const sendReply = async (payload: { message: string; attachment: File | null }) => {
    const ticket = active.value;
    if (!ticket) return;

    sending.value = true;

    try {
        const res: any = await supportService.reply(ticket.uuid, payload);
        applyUpdate({
            uuid: ticket.uuid,
            status: res.data.status as SupportStatus,
            reply: res.data.reply as SupportReply,
        });

        thread.value?.reset();
    } catch (err: any) {
        error(err?.message || "Unable to send your message.");
    } finally {
        sending.value = false;
    }
};

const changeStatus = async (status: "open" | "resolved") => {
    const ticket = active.value;
    if (!ticket) return;

    savingStatus.value = true;

    try {
        const res: any = await supportService.updateStatus(ticket.uuid, { status });
        applyUpdate({ uuid: ticket.uuid, status: res.data.status });
        success(res.message);
    } catch (err: any) {
        error(err?.message || "Unable to update this ticket.");
    } finally {
        savingStatus.value = false;
    }
};

watch(
    () => route.query.ticket,
    (uuid) => {
        if (typeof uuid === "string" && uuid) openTicket(uuid);
    },
);

onMounted(async () => {
    await fetchTickets();

    const uuid = route.query.ticket;

    if (typeof uuid === "string" && uuid) {
        openTicket(uuid);
    } else if (tickets.value.length) {
        tab.value = "tickets";
    }
});
</script>

<template>
    <div
        class="relative min-h-screen w-full overflow-hidden bg-white dark:bg-secondary"
    >
        <div
            class="pointer-events-none absolute -top-24 left-[-100px] h-[380px] w-[380px] rounded-full bg-primary-200/40 blur-[100px] dark:bg-primary/20"
        />
        <div
            class="pointer-events-none absolute right-[-120px] top-[420px] h-[420px] w-[420px] rounded-full bg-accent-200/30 blur-[120px] dark:bg-primary/10"
        />

        <section class="relative z-10 px-6 pb-8 pt-32">
            <div class="mx-auto max-w-3xl text-center">
                <p
                    class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                >
                    Customer Support
                </p>

                <h1
                    class="text-3xl font-black text-secondary md:text-4xl dark:text-white"
                >
                    How can we help?
                </h1>

                <p
                    class="mt-4 text-sm leading-7 text-muted md:text-base dark:text-gray-400"
                >
                    Questions about AMUMA, a booking, or a problem with the
                    site? Send us a message — the AMUMA team replies right here
                    in your tickets.
                </p>
            </div>
        </section>

        <section class="relative z-10 px-6 pb-20">
            <div
                class="mx-auto flex max-w-4xl flex-col overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-[0_20px_50px_rgba(15,23,42,0.08)] dark:border-white/10 dark:bg-secondary-800"
            >
                <div
                    v-if="!active"
                    class="flex gap-1 border-b border-gray-100 p-2 dark:border-white/10"
                >
                    <button
                        v-for="item in [
                            { value: 'new', label: 'New message' },
                            {
                                value: 'tickets',
                                label: total
                                    ? `My tickets (${total})`
                                    : 'My tickets',
                            },
                        ]"
                        :key="item.value"
                        type="button"
                        class="flex-1 rounded-2xl px-4 py-2.5 text-sm font-semibold transition"
                        :class="
                            tab === item.value
                                ? 'bg-primary text-white shadow-sm shadow-primary/30'
                                : 'text-muted hover:bg-light dark:text-gray-400 dark:hover:bg-white/5'
                        "
                        @click="tab = item.value as 'new' | 'tickets'"
                    >
                        {{ item.label }}
                    </button>
                </div>

                <form
                    v-if="!active && tab === 'new'"
                    class="space-y-4 p-6 sm:p-8"
                    @submit.prevent="submit"
                >
                    <Combobox
                        v-model="form.category"
                        label="Topic"
                        placeholder="What is this about?"
                        required
                        :items="SUPPORT_CATEGORY_OPTIONS"
                        :error="errors.category"
                        @update:model-value="errors.category = ''"
                    />

                    <BaseInput
                        v-model="form.subject"
                        label="Subject"
                        placeholder="A short summary of your concern"
                        required
                        :text-max="150"
                        :error="errors.subject"
                        @update:model-value="errors.subject = ''"
                    />

                    <BaseInput
                        v-model="form.message"
                        mode="textarea"
                        label="Message"
                        placeholder="Tell us what happened and what you need help with."
                        required
                        :rows="6"
                        :text-max="5000"
                        :error="errors.message"
                        @update:model-value="errors.message = ''"
                    />

                    <div>
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            class="hidden"
                            @change="pickFile"
                        />

                        <button
                            v-if="!form.attachment"
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-slate-300 px-4 py-3 text-sm text-slate-500 transition hover:border-primary/50 hover:text-primary dark:border-white/15 dark:text-gray-400"
                            @click="fileInput?.click()"
                        >
                            <ImagePlus class="h-4 w-4" />
                            Attach a screenshot (optional)
                        </button>

                        <div
                            v-else
                            class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 dark:border-white/10 dark:text-gray-200"
                        >
                            <span class="truncate">{{
                                form.attachment.name
                            }}</span>
                            <button
                                type="button"
                                aria-label="Remove screenshot"
                                class="shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-gray-200"
                                @click="form.attachment = null"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <p
                            v-if="errors.attachment"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ errors.attachment }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <LoaderCircle
                            v-if="submitting"
                            class="h-4 w-4 animate-spin"
                        />
                        <Send v-else class="h-4 w-4" />
                        {{ submitting ? "Sending..." : "Send message" }}
                    </button>
                </form>

                <div
                    v-else-if="!active"
                    class="min-h-[320px] space-y-2 p-6 sm:p-8"
                >
                    <div v-if="loading && !tickets.length" class="space-y-3">
                        <div
                            v-for="n in 3"
                            :key="n"
                            class="h-16 animate-pulse rounded-xl bg-slate-100 dark:bg-white/5"
                        />
                    </div>

                    <div
                        v-else-if="!tickets.length"
                        class="flex flex-col items-center justify-center py-16 text-center"
                    >
                        <p
                            class="text-sm font-medium text-secondary dark:text-white"
                        >
                            No tickets yet
                        </p>
                        <p class="mt-1 text-xs text-muted dark:text-gray-400">
                            Messages you send to AMUMA Support will show up
                            here.
                        </p>
                    </div>

                    <template v-else>
                        <button
                            v-for="ticket in tickets"
                            :key="ticket.uuid"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-left transition hover:border-primary/40 hover:bg-primary/5 dark:border-white/10 dark:hover:bg-white/5"
                            @click="openTicket(ticket.uuid)"
                        >
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-sm font-semibold text-secondary dark:text-white"
                                >
                                    {{ ticket.subject }}
                                </p>
                                <p
                                    class="mt-0.5 truncate text-xs text-muted dark:text-gray-400"
                                >
                                    {{ ticket.ticket_code }} ·
                                    {{
                                        stringToDateTime(
                                            ticket.last_reply_at ??
                                                ticket.created_at,
                                        )
                                    }}
                                </p>
                            </div>

                            <span
                                class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                :class="SUPPORT_STATUS_CLASSES[ticket.status]"
                            >
                                {{ SUPPORT_STATUS_LABELS[ticket.status] }}
                            </span>
                            <ChevronRight
                                class="h-4 w-4 shrink-0 text-slate-300 dark:text-gray-600"
                            />
                        </button>

                        <button
                            v-if="page < lastPage"
                            type="button"
                            :disabled="loading"
                            class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:opacity-60 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                            @click="fetchTickets(page + 1)"
                        >
                            {{ loading ? "Loading..." : "Load more" }}
                        </button>
                    </template>
                </div>

                <div v-else class="flex h-[640px] max-h-[80vh] flex-col">
                    <div
                        class="flex shrink-0 items-center gap-3 border-b border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <button
                            type="button"
                            aria-label="Back to my tickets"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-white/10 dark:hover:text-gray-300"
                            @click="closeTicket"
                        >
                            <ArrowLeft class="h-4 w-4" />
                        </button>

                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-semibold text-secondary dark:text-white"
                            >
                                {{ active.subject }}
                            </p>
                            <p class="truncate text-xs text-muted dark:text-gray-400">
                                {{ active.ticket_code }} ·
                                {{ SUPPORT_CATEGORY_LABELS[active.category] }}
                            </p>
                        </div>

                        <span
                            class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="SUPPORT_STATUS_CLASSES[active.status]"
                        >
                            {{ SUPPORT_STATUS_LABELS[active.status] }}
                        </span>
                    </div>

                    <SupportThread
                        ref="thread"
                        :ticket="active"
                        :loading="threadLoading"
                        :sending="sending"
                        viewer="requester"
                        @reply="sendReply"
                    />

                    <div
                        v-if="!threadLoading"
                        class="flex shrink-0 justify-end border-t border-gray-100 bg-slate-50/60 px-6 py-3 dark:border-white/10 dark:bg-white/5"
                    >
                        <button
                            v-if="active.status !== 'resolved'"
                            type="button"
                            :disabled="savingStatus"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-60 dark:text-emerald-400 dark:hover:bg-emerald-500/10"
                            @click="changeStatus('resolved')"
                        >
                            <LoaderCircle
                                v-if="savingStatus"
                                class="h-4 w-4 animate-spin"
                            />
                            <CheckCircle2 v-else class="h-4 w-4" />
                            My issue is resolved
                        </button>
                        <button
                            v-else
                            type="button"
                            :disabled="savingStatus"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 disabled:opacity-60 dark:text-gray-300 dark:hover:bg-white/10"
                            @click="changeStatus('open')"
                        >
                            <LoaderCircle
                                v-if="savingStatus"
                                class="h-4 w-4 animate-spin"
                            />
                            <RotateCcw v-else class="h-4 w-4" />
                            Reopen ticket
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
