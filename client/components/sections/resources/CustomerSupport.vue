<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import {
    CheckCircle2,
    ImagePlus,
    LifeBuoy,
    LoaderCircle,
    Mail,
    Send,
    X,
} from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import { supportService } from "~/api/support/SupportService";
import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import {
    SUPPORT_CATEGORY_OPTIONS,
    type SupportCategory,
} from "~/types/support";

const MAX_ATTACHMENT_BYTES = 5 * 1024 * 1024;

const authUser = useAuthUser();
const { error } = useToast();

const signedIn = computed(() => !!authUser.value);

const form = reactive({
    name: "",
    email: "",
    category: "" as SupportCategory | "",
    subject: "",
    message: "",
    attachment: null as File | null,
});

const errors = reactive<Record<string, string>>({});
const fileInput = ref<HTMLInputElement | null>(null);
const submitting = ref(false);
const sent = ref<{ code: string; message: string } | null>(null);

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

const validate = () => {
    errors.name =
        signedIn.value || form.name.trim() ? "" : "Enter your name.";
    errors.email =
        signedIn.value || /^\S+@\S+\.\S+$/.test(form.email.trim())
            ? ""
            : "Enter a valid email so we can reply.";
    errors.category = form.category ? "" : "Choose what this is about.";
    errors.subject = form.subject.trim() ? "" : "Add a short subject.";
    errors.message = form.message.trim() ? "" : "Describe your concern.";

    return !Object.values(errors).some(Boolean);
};

const submit = async () => {
    if (!validate()) return;

    submitting.value = true;

    try {
        const res: any = await supportService.createPublic({
            name: signedIn.value ? undefined : form.name.trim(),
            email: signedIn.value ? undefined : form.email.trim(),
            category: form.category as SupportCategory,
            subject: form.subject.trim(),
            message: form.message.trim(),
            attachment: form.attachment,
        });

        sent.value = { code: res.data?.ticket_code, message: res.message };
    } catch (err: any) {
        Object.entries(err?.errors ?? {}).forEach(([field, messages]) => {
            errors[field] = (messages as string[])[0] ?? "";
        });
        error(err?.message || "Unable to send your message.");
    } finally {
        submitting.value = false;
    }
};

const startOver = () => {
    Object.assign(form, {
        category: "",
        subject: "",
        message: "",
        attachment: null,
    });
    sent.value = null;
};
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
                    site? Send us a message and the AMUMA team will reply by
                    email.
                </p>
            </div>
        </section>

        <section class="relative z-10 px-6 pb-20">
            <div
                class="mx-auto max-w-2xl rounded-3xl border border-gray-200 bg-white p-6 shadow-[0_20px_50px_rgba(15,23,42,0.08)] sm:p-8 dark:border-white/10 dark:bg-secondary-800"
            >
                <div v-if="sent" class="py-8 text-center">
                    <div
                        class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"
                    >
                        <CheckCircle2 class="h-7 w-7" />
                    </div>

                    <h2 class="text-xl font-bold text-secondary dark:text-white">
                        Message sent
                    </h2>

                    <p
                        class="mx-auto mt-2 max-w-md text-sm leading-6 text-muted dark:text-gray-400"
                    >
                        {{ sent.message }}
                    </p>

                    <p
                        v-if="sent.code"
                        class="mt-4 inline-flex rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary dark:bg-primary-500/10"
                    >
                        Ticket {{ sent.code }}
                    </p>

                    <div>
                        <button
                            type="button"
                            class="mt-6 text-sm font-medium text-primary hover:underline"
                            @click="startOver"
                        >
                            Send another message
                        </button>
                    </div>
                </div>

                <form v-else class="space-y-4" @submit.prevent="submit">
                    <div class="mb-2 flex items-center gap-3">
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-primary-50 text-primary dark:bg-primary-500/10"
                        >
                            <LifeBuoy class="h-5 w-5" />
                        </span>
                        <div>
                            <p
                                class="text-sm font-semibold text-secondary dark:text-white"
                            >
                                Send a message
                            </p>
                            <p
                                class="text-xs text-muted dark:text-gray-400"
                            >
                                We usually reply within 1–2 business days.
                            </p>
                        </div>
                    </div>

                    <p
                        v-if="signedIn"
                        class="flex items-center gap-2 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:bg-white/5 dark:text-gray-300"
                    >
                        <Mail class="h-4 w-4 text-primary" />
                        We'll reply to {{ authUser?.email }}
                    </p>

                    <div v-else class="grid gap-4 sm:grid-cols-2">
                        <BaseInput
                            v-model="form.name"
                            label="Your name"
                            placeholder="Juan Dela Cruz"
                            required
                            :text-max="150"
                            :error="errors.name"
                            @update:model-value="errors.name = ''"
                        />
                        <BaseInput
                            v-model="form.email"
                            label="Email"
                            placeholder="you@example.com"
                            required
                            :error="errors.email"
                            @update:model-value="errors.email = ''"
                        />
                    </div>

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
            </div>
        </section>
    </div>
</template>
