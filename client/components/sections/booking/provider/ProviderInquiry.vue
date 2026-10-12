<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { z } from "zod";
import {
    CircleCheck,
    LoaderCircle,
    MessageCircleQuestion,
    Send,
    X,
} from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import PhoneInput from "~/components/ui/PhoneInput.vue";
import type { BranchRetrieve } from "~/types/branch";
import { branchService } from "~/api/branch/BranchService";
import { useAuthUser } from "~/composables/useAuthUser";
import { useToast } from "~/composables/useToast";
import { phoneNumber } from "~/schema/phone-schema";

const props = defineProps<{
    branch: BranchRetrieve | null;
}>();

const inquirySchema = z.object({
    first_name: z.string().trim().min(1, "First name is required"),
    last_name: z.string().trim().min(1, "Last name is required"),
    email: z
        .string()
        .trim()
        .min(1, "Email is required")
        .email("Enter a valid email address"),
    phone_number: phoneNumber(),
    city: z.string().trim().max(255).optional(),
    message: z
        .string()
        .trim()
        .min(1, "Tell us how we can help")
        .max(2000, "Message must be 2,000 characters or less"),
});

const user = useAuthUser();
const { error: toastError } = useToast();

const open = ref(false);
const sending = ref(false);
const sent = ref(false);
const rootRef = ref<HTMLElement | null>(null);
const errors = ref<Record<string, string>>({});

const form = reactive({
    first_name: "",
    last_name: "",
    email: "",
    phone_number: "",
    city: "",
    message: "",
});

const fillFromUser = () => {
    if (!user.value) return;
    form.first_name ||= user.value.first_name ?? "";
    form.last_name ||= user.value.last_name ?? "";
    form.email ||= user.value.email ?? "";
};

const toggle = () => {
    open.value = !open.value;
    if (open.value) {
        sent.value = false;
        fillFromUser();
    }
};

const clearError = (field: string) => {
    if (errors.value[field]) {
        const { [field]: _, ...rest } = errors.value;
        errors.value = rest;
    }
};

const resetForm = () => {
    form.phone_number = "";
    form.city = "";
    form.message = "";
    if (!user.value) {
        form.first_name = "";
        form.last_name = "";
        form.email = "";
    }
    errors.value = {};
};

const submit = async () => {
    const result = inquirySchema.safeParse(form);

    if (!result.success) {
        const fieldErrors: Record<string, string> = {};
        result.error.issues.forEach((issue) => {
            const key = String(issue.path[0]);
            fieldErrors[key] ||= issue.message;
        });
        errors.value = fieldErrors;
        return;
    }

    if (!props.branch?.uuid) return;

    sending.value = true;

    try {
        await branchService.sendInquiry(props.branch.uuid, result.data);
        sent.value = true;
        resetForm();
    } catch (err: any) {
        const validationErrors = err?.data?.errors;

        if (validationErrors) {
            errors.value = Object.fromEntries(
                Object.entries(validationErrors).map(([key, value]: any) => [
                    key,
                    Array.isArray(value) ? value[0] : value,
                ]),
            );
        } else {
            toastError(
                err?.data?.message ||
                    err?.message ||
                    "Unable to send your inquiry.",
            );
        }
    } finally {
        sending.value = false;
    }
};

const onDocumentClick = (event: MouseEvent) => {
    if (open.value && !rootRef.value?.contains(event.target as Node)) {
        open.value = false;
    }
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === "Escape") open.value = false;
};

onMounted(() => {
    document.addEventListener("click", onDocumentClick);
    document.addEventListener("keydown", onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", onDocumentClick);
    document.removeEventListener("keydown", onKeydown);
});
</script>

<template>
    <div
        v-if="branch"
        ref="rootRef"
        class="fixed bottom-24 right-4 z-40 sm:right-6 lg:bottom-6"
    >
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-2 scale-95"
        >
            <div
                v-if="open"
                role="dialog"
                aria-label="Send an inquiry"
                class="absolute bottom-16 right-0 flex max-h-[calc(100vh-9rem)] w-[calc(100vw-2rem)] max-w-md origin-bottom-right flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="relative shrink-0 bg-gradient-to-br from-sky-500 to-blue-600 px-5 py-4 text-white"
                >
                    <button
                        type="button"
                        class="absolute right-3 top-3 flex h-7 w-7 items-center justify-center rounded-full text-white/80 transition hover:bg-white/15 hover:text-white"
                        aria-label="Close"
                        @click="open = false"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <p class="text-base font-bold">Send an Inquiry</p>
                    <p class="mt-0.5 pr-6 text-xs text-white/80">
                        Fill in the form below and {{ branch.name }} will reply
                        to you through email.
                    </p>
                </div>

                <div
                    v-if="sent"
                    class="flex flex-col items-center px-6 py-10 text-center"
                >
                    <span
                        class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-500 dark:bg-emerald-500/10"
                    >
                        <CircleCheck class="h-7 w-7" />
                    </span>
                    <p
                        class="mt-4 text-base font-semibold text-gray-900 dark:text-white"
                    >
                        Inquiry sent
                    </p>
                    <p
                        class="mt-1 max-w-xs text-sm text-gray-500 dark:text-gray-400"
                    >
                        Thanks for reaching out. {{ branch.name }} will send
                        their reply to your email.
                    </p>
                    <button
                        type="button"
                        class="mt-5 text-sm font-semibold text-primary hover:underline"
                        @click="sent = false"
                    >
                        Send another inquiry
                    </button>
                </div>

                <form
                    v-else
                    class="flex min-h-0 flex-col"
                    novalidate
                    @submit.prevent="submit"
                >
                    <div class="space-y-4 overflow-y-auto px-5 py-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <BaseInput
                                v-model="form.first_name"
                                label="First Name"
                                placeholder="Juan"
                                required
                                :error="errors.first_name"
                                @update:modelValue="clearError('first_name')"
                            />
                            <BaseInput
                                v-model="form.last_name"
                                label="Last Name"
                                placeholder="Dela Cruz"
                                required
                                :error="errors.last_name"
                                @update:modelValue="clearError('last_name')"
                            />
                        </div>

                        <BaseInput
                            v-model="form.email"
                            label="Email Address"
                            mode="email"
                            placeholder="juan@example.com"
                            required
                            :error="errors.email"
                            @update:modelValue="clearError('email')"
                        />

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <PhoneInput
                                v-model="form.phone_number"
                                label="Phone Number"
                                required
                                :error="errors.phone_number"
                                @update:modelValue="clearError('phone_number')"
                            />
                            <BaseInput
                                v-model="form.city"
                                label="City / Region"
                                placeholder="e.g. Davao City"
                                :error="errors.city"
                                @update:modelValue="clearError('city')"
                            />
                        </div>

                        <div>
                            <label
                                for="inquiry-message"
                                class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                How can we help you?
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                id="inquiry-message"
                                v-model="form.message"
                                rows="4"
                                maxlength="2000"
                                placeholder="Tell us about the care support you or your family member needs..."
                                class="w-full resize-none rounded-xl border bg-white px-3 py-2.5 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/10 dark:bg-secondary dark:text-white dark:placeholder:text-gray-500"
                                :class="
                                    errors.message
                                        ? 'border-red-400'
                                        : 'border-slate-200 dark:border-white/10'
                                "
                                @input="clearError('message')"
                            />
                            <p
                                v-if="errors.message"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ errors.message }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="shrink-0 border-t border-gray-100 px-5 py-4 dark:border-white/10"
                    >
                        <button
                            type="submit"
                            :disabled="sending"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <LoaderCircle
                                v-if="sending"
                                class="h-4 w-4 animate-spin"
                            />
                            <Send v-else class="h-4 w-4" />
                            {{ sending ? "Sending..." : "Send Inquiry" }}
                        </button>
                    </div>
                </form>
            </div>
        </Transition>

        <button
            type="button"
            class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-lg shadow-blue-500/30 ring-4 ring-white transition hover:scale-105 dark:ring-secondary"
            :aria-expanded="open"
            aria-label="Send an inquiry"
            title="Have a question?"
            @click.stop="toggle"
        >
            <X v-if="open" class="h-6 w-6" />
            <MessageCircleQuestion v-else class="h-6 w-6" />
        </button>
    </div>
</template>
