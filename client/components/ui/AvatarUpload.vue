<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Camera, Trash2 } from "lucide-vue-next";
import PatientAvatar from "~/components/ui/PatientAvatar.vue";

const MAX_SIZE = 5 * 1024 * 1024;

const props = withDefaults(
    defineProps<{
        modelValue?: File | string | null;
        name?: string | null;
        current?: string | null;
        label?: string;
        removable?: boolean;
    }>(),
    {
        modelValue: null,
        name: null,
        current: null,
        label: "Photo",
        removable: false,
    },
);

const emit = defineEmits<{
    (e: "update:modelValue", value: File | null): void;
    (e: "remove"): void;
}>();

const input = ref<HTMLInputElement | null>(null);
const error = ref("");
const preview = ref<string | null>(null);

function clearPreview() {
    if (preview.value) URL.revokeObjectURL(preview.value);
    preview.value = null;
}

watch(
    () => props.modelValue,
    (value) => {
        clearPreview();

        if (value instanceof File) {
            preview.value = URL.createObjectURL(value);
        }
    },
    { immediate: true },
);

onBeforeUnmount(clearPreview);

const existing = computed(() =>
    typeof props.modelValue === "string" && props.modelValue
        ? props.modelValue
        : props.current,
);
const shown = computed(() => preview.value ?? existing.value);
const hasPhoto = computed(
    () => props.modelValue instanceof File || !!existing.value,
);

function onPick(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    target.value = "";

    if (!file) return;

    if (!file.type.startsWith("image/")) {
        error.value = "Choose an image file.";
        return;
    }

    if (file.size > MAX_SIZE) {
        error.value = "The photo must be 5 MB or smaller.";
        return;
    }

    error.value = "";
    emit("update:modelValue", file);
}

function remove() {
    error.value = "";
    emit("update:modelValue", null);
    emit("remove");
}
</script>

<template>
    <div class="flex items-center gap-4">
        <PatientAvatar
            :src="shown"
            :name="name"
            size-class="h-16 w-16 text-lg"
            rounded-class="rounded-2xl"
        />

        <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-700 dark:text-gray-300">
                {{ label }}
            </p>

            <div class="mt-1.5 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-primary/40 hover:text-primary dark:border-white/10 dark:text-gray-300"
                    @click="input?.click()"
                >
                    <Camera class="h-3.5 w-3.5" />
                    {{ hasPhoto ? "Change photo" : "Upload photo" }}
                </button>

                <button
                    v-if="removable && hasPhoto"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10"
                    @click="remove"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                    Remove
                </button>
            </div>

            <p
                v-if="error"
                class="mt-1.5 text-xs text-rose-600 dark:text-rose-300"
            >
                {{ error }}
            </p>
            <p v-else class="mt-1.5 text-[11px] text-muted dark:text-gray-400">
                Optional. JPG or PNG, up to 5 MB.
            </p>

            <input
                ref="input"
                type="file"
                accept="image/*"
                class="hidden"
                @change="onPick"
            />
        </div>
    </div>
</template>
