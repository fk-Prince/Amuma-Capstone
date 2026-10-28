<script setup lang="ts">
import { reactive, ref } from "vue";
import { ImagePlus, LoaderCircle, Send, X } from "lucide-vue-next";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import {
    SUPPORT_CATEGORY_OPTIONS,
    type SupportCategory,
} from "~/types/support";

defineProps<{
    submitting: boolean;
    serverErrors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
    submit: [
        payload: {
            category: SupportCategory;
            subject: string;
            message: string;
            attachment: File | null;
        },
    ];
    cancel: [];
}>();

const MAX_ATTACHMENT_BYTES = 5 * 1024 * 1024;

const form = reactive({
    category: "" as SupportCategory | "",
    subject: "",
    message: "",
    attachment: null as File | null,
});

const errors = reactive<Record<string, string>>({});
const fileInput = ref<HTMLInputElement | null>(null);

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

const submit = () => {
    errors.category = form.category ? "" : "Choose what this is about.";
    errors.subject = form.subject.trim() ? "" : "Add a short subject.";
    errors.message = form.message.trim() ? "" : "Describe your concern.";

    if (errors.category || errors.subject || errors.message) return;

    emit("submit", {
        category: form.category as SupportCategory,
        subject: form.subject.trim(),
        message: form.message.trim(),
        attachment: form.attachment,
    });
};
</script>

<template>
    <div class="flex min-h-0 flex-1 flex-col">
        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5">
            <Combobox
                v-model="form.category"
                label="Topic"
                placeholder="What is this about?"
                required
                :items="SUPPORT_CATEGORY_OPTIONS"
                :error="errors.category || serverErrors?.category?.[0]"
                @update:model-value="errors.category = ''"
            />

            <BaseInput
                v-model="form.subject"
                label="Subject"
                placeholder="e.g. My payment went through but the branch is still pending"
                required
                :text-max="150"
                :error="errors.subject || serverErrors?.subject?.[0]"
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
                :error="errors.message || serverErrors?.message?.[0]"
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
                    <span class="truncate">{{ form.attachment.name }}</span>
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
                    v-if="errors.attachment || serverErrors?.attachment?.[0]"
                    class="mt-1 text-xs text-red-500"
                >
                    {{ errors.attachment || serverErrors?.attachment?.[0] }}
                </p>
            </div>
        </div>

        <div
            class="flex shrink-0 items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-6 py-4 dark:border-white/10 dark:bg-white/5"
        >
            <button
                type="button"
                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
                @click="emit('cancel')"
            >
                Cancel
            </button>

            <button
                type="button"
                :disabled="submitting"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/30 transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                @click="submit"
            >
                <LoaderCircle v-if="submitting" class="h-4 w-4 animate-spin" />
                <Send v-else class="h-4 w-4" />
                {{ submitting ? "Sending..." : "Send ticket" }}
            </button>
        </div>
    </div>
</template>
