<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from "vue";
import { FileText, X } from "lucide-vue-next";
import {
    BOOKING_TERMS_TYPES,
    type BookingTermsType,
    type BranchTermsSection,
} from "~/utils/paymentTerms";

const props = defineProps<{
    docName: string;
    typed: boolean;
    section: BranchTermsSection | null;
    position: number;
    positions: { value: number; label: string }[];
    defaultType?: BookingTermsType;
}>();

const emit = defineEmits<{
    close: [];
    save: [section: BranchTermsSection, position: number];
}>();

const draft = reactive({
    title: props.section?.title ?? "",
    body: props.section?.body ?? "",
    show_on: [...(props.section?.show_on ?? (props.defaultType ? [props.defaultType] : []))] as BookingTermsType[],
    position: props.position,
});

const touched = ref(false);
const titleRef = ref<HTMLInputElement | null>(null);

const isNew = computed(() => !props.section);

const error = computed(() => {
    if (!draft.title.trim()) return "Add a title.";
    if (!draft.body.trim()) return "Add the text families will read.";
    if (props.typed && !draft.show_on.length) return "Pick at least one booking to show this on.";
    return "";
});

function toggleType(type: BookingTermsType, on: boolean) {
    draft.show_on = BOOKING_TERMS_TYPES.map((item) => item.value).filter((value) =>
        value === type ? on : draft.show_on.includes(value),
    );
    touched.value = true;
}

function submit() {
    touched.value = true;
    if (error.value) return;

    emit(
        "save",
        {
            after: props.section?.after ?? "",
            title: draft.title.trim(),
            body: draft.body.trim(),
            ...(props.typed ? { show_on: draft.show_on } : {}),
        },
        Math.max(2, Number(draft.position)),
    );
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === "Escape") emit("close");
}

onMounted(async () => {
    document.addEventListener("keydown", onKeydown);
    document.body.style.overflow = "hidden";
    await nextTick();
    titleRef.value?.focus();
});

onBeforeUnmount(() => {
    document.removeEventListener("keydown", onKeydown);
    document.body.style.overflow = "";
});

const fieldClass =
    "w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20 dark:border-white/10 dark:bg-secondary dark:text-white";
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-[60] overflow-y-auto bg-gray-900/60 backdrop-blur-sm">
            <div class="flex min-h-full items-center justify-center p-4">
                <form
                    class="w-full max-w-lg overflow-hidden rounded-2xl md:max-w-4xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="terms-section-title"
                    novalidate
                    @submit.prevent="submit"
                >
                    <div
                        class="flex items-center justify-between gap-4 border-b border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <FileText class="h-5 w-5" />
                            </div>
                            <div>
                                <h2
                                    id="terms-section-title"
                                    class="text-lg font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ isNew ? "Add a section" : "Edit section" }}
                                </h2>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    Section {{ draft.position }} of your {{ docName }}
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            aria-label="Close"
                            class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                            @click="emit('close')"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="grid gap-x-8 gap-y-4 px-6 py-5 md:grid-cols-2">
                    <div class="min-w-0 space-y-4">
                        <label class="flex flex-col gap-1.5">
                            <span class="text-sm font-medium text-slate-700 dark:text-gray-300">
                                Title <span class="text-rose-500">*</span>
                            </span>
                            <input
                                ref="titleRef"
                                v-model="draft.title"
                                maxlength="80"
                                placeholder="e.g. What to bring on admission day"
                                :class="[fieldClass, 'py-2']"
                                @input="touched = true"
                            />
                        </label>

                        <div>
                            <label
                                for="terms-section-body"
                                class="text-sm font-medium text-slate-700 dark:text-gray-300"
                            >
                                Text <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                id="terms-section-body"
                                v-model="draft.body"
                                rows="6"
                                maxlength="1500"
                                placeholder="Write the policy the way you would explain it to a family."
                                :class="[fieldClass, 'mt-1.5 resize-y py-2 leading-6 md:min-h-[268px]']"
                                @input="touched = true"
                            />
                            <p class="mt-1 text-right text-xs text-slate-400 dark:text-gray-500">
                                {{ draft.body.length }}/1500
                            </p>
                        </div>
                    </div>

                    <div class="min-w-0 space-y-4">
                        <fieldset v-if="typed">
                            <legend class="text-sm font-medium text-slate-700 dark:text-gray-300">
                                Show on <span class="text-rose-500">*</span>
                            </legend>
                            <p class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">
                                The section only appears in the terms for the bookings you tick.
                            </p>
                            <div class="mt-2 grid gap-2">
                                <label
                                    v-for="type in BOOKING_TERMS_TYPES"
                                    :key="type.value"
                                    class="flex h-11 cursor-pointer items-center gap-3 rounded-lg border px-3 transition"
                                    :class="
                                        draft.show_on.includes(type.value)
                                            ? 'border-primary bg-primary-50/60 dark:bg-primary-500/10'
                                            : 'border-slate-200 hover:border-primary-200 dark:border-white/10'
                                    "
                                >
                                    <input
                                        type="checkbox"
                                        :checked="draft.show_on.includes(type.value)"
                                        class="h-4 w-4 shrink-0 rounded border-slate-300 text-primary dark:border-white/20"
                                        @change="toggleType(type.value, ($event.target as HTMLInputElement).checked)"
                                    />
                                    <span class="min-w-0 flex-1 truncate text-sm font-medium text-slate-800 dark:text-white">
                                        {{ type.label }}
                                    </span>
                                    <span class="hidden shrink-0 text-xs text-slate-400 sm:inline dark:text-gray-500">
                                        {{ type.hint }}
                                    </span>
                                </label>
                            </div>
                        </fieldset>

                        <label class="flex flex-col gap-1.5">
                            <span class="text-sm font-medium text-slate-700 dark:text-gray-300">
                                Position <span class="text-rose-500">*</span>
                                <span class="font-normal text-slate-400 dark:text-gray-500">
                                    (2–{{ positions.length + 1 }})
                                </span>
                            </span>
                            <select v-model.number="draft.position" :class="[fieldClass, 'h-11']">
                                <option v-for="option in positions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </select>
                            <span class="text-xs text-slate-400 dark:text-gray-500">
                                Section 1 always stays first. The sections from your pick onwards move down by one.
                            </span>
                        </label>

                        <p v-if="touched && error" class="text-xs text-rose-500">
                            {{ error }}
                        </p>
                    </div>
                    </div>

                    <div
                        class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 dark:border-white/10"
                    >
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                            @click="emit('close')"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="!!error"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ isNew ? "Add section" : "Done" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
