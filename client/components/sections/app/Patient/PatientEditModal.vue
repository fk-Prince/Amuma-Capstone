<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        >
            <div
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
                @click="close"
            />

            <form
                class="relative z-50 flex max-h-[90dvh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-secondary"
                @submit.prevent="submit"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <p
                            class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500"
                        >
                            Edit patient
                        </p>

                        <h2
                            class="mt-1 truncate text-lg font-semibold text-slate-800 dark:text-white"
                        >
                            {{ patient.full_name }}
                        </h2>
                    </div>

                    <button
                        type="button"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300"
                        @click="close"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-6">
                    <AvatarUpload
                        v-model="avatarFile"
                        :name="`${form.first_name} ${form.last_name}`"
                        :current="removeAvatar ? null : customAvatar"
                        removable
                        @remove="removeAvatar = true"
                    />

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        <BaseInput
                            label="First Name"
                            :model-value="form.first_name"
                            :error="errors.first_name"
                            required
                            @update:model-value="set('first_name', $event)"
                        />
                        <BaseInput
                            label="Middle Name"
                            :model-value="form.middle_name"
                            :error="errors.middle_name"
                            @update:model-value="set('middle_name', $event)"
                        />
                        <BaseInput
                            label="Last Name"
                            :model-value="form.last_name"
                            :error="errors.last_name"
                            required
                            @update:model-value="set('last_name', $event)"
                        />
                    </div>

                    <div class="grid grid-cols-1 items-start gap-5 md:grid-cols-3">
                        <div class="flex flex-col gap-1.5">
                            <label
                                class="text-sm font-semibold text-slate-700 dark:text-gray-300"
                            >
                                Gender <span class="text-danger">*</span>
                            </label>
                            <div
                                class="inline-flex h-11 items-center rounded-lg border border-slate-200 bg-slate-50 p-1 dark:border-white/10 dark:bg-secondary"
                            >
                                <button
                                    v-for="option in ['Female', 'Male']"
                                    :key="option"
                                    type="button"
                                    class="h-full flex-1 rounded-md px-4 text-sm font-medium transition-colors"
                                    :class="
                                        form.gender === option
                                            ? 'bg-white text-primary shadow-sm dark:bg-white/10'
                                            : 'text-muted hover:text-slate-700 dark:text-gray-400 dark:hover:text-gray-200'
                                    "
                                    @click="set('gender', option)"
                                >
                                    {{ option }}
                                </button>
                            </div>
                            <p v-if="errors.gender" class="text-xs text-red-500">
                                {{ errors.gender }}
                            </p>
                        </div>

                        <DatePickerField
                            label="Date of Birth"
                            :model-value="form.date_of_birth"
                            :max="todayStr"
                            :default-to-today="false"
                            placeholder="Select date of birth"
                            :error="errors.date_of_birth"
                            @update:model-value="set('date_of_birth', $event)"
                        />

                        <PhoneInput
                            label="Phone Number"
                            :model-value="form.phone_number"
                            :error="errors.phone_number"
                            @update:model-value="set('phone_number', $event)"
                        />
                    </div>

                    <BaseInput
                        label="Address"
                        :model-value="form.address"
                        :error="errors.address"
                        @update:model-value="set('address', $event)"
                    />

                    <div class="grid grid-cols-2 gap-5 md:grid-cols-4">
                        <BaseInput
                            label="Citizenship"
                            :model-value="form.citizenship"
                            :error="errors.citizenship"
                            @update:model-value="set('citizenship', $event)"
                        />
                        <BaseInput
                            label="Height (cm)"
                            mode="number"
                            input-class="text-center"
                            :model-value="form.height"
                            :error="errors.height"
                            @update:model-value="set('height', $event)"
                        />
                        <BaseInput
                            label="Weight (kg)"
                            mode="number"
                            input-class="text-center"
                            :model-value="form.weight"
                            :error="errors.weight"
                            @update:model-value="set('weight', $event)"
                        />
                        <Combobox
                            label="Blood Type"
                            placeholder="Select"
                            :model-value="form.blood_type"
                            :error="errors.blood_type"
                            :items="bloodTypes"
                            @update:model-value="set('blood_type', $event)"
                        />
                    </div>

                    <BaseInput
                        label="Allergies"
                        placeholder="e.g. Penicillin, Peanuts, Latex"
                        :model-value="form.allergies"
                        :error="errors.allergies"
                        @update:model-value="set('allergies', $event)"
                    />
                </div>

                <div
                    class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-white/10"
                        @click="close"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        :disabled="saving"
                        class="flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                        {{ saving ? "Saving..." : "Save changes" }}
                    </button>
                </div>
            </form>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { Loader2, X } from "lucide-vue-next";
import AvatarUpload from "~/components/ui/AvatarUpload.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import Combobox from "~/components/ui/Combobox.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import PhoneInput from "~/components/ui/PhoneInput.vue";
import { patientService } from "~/api/patient/PatientService";
import { useToast } from "~/composables/useToast";
import { getLocalDateStr } from "~/utils/time";
import type { PatientRetrieve } from "~/types/patient";

const FALLBACK_AVATAR_HOST = "https://ui-avatars.com";

const props = defineProps<{
    open: boolean;
    patient: PatientRetrieve;
    branchUuid: string;
}>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "saved", patient: Record<string, any>): void;
}>();

const { success, error } = useToast();

const todayStr = getLocalDateStr(new Date());
const bloodTypes = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"].map(
    (type) => ({ label: type, value: type }),
);

const saving = ref(false);
const errors = ref<Record<string, string>>({});
const avatarFile = ref<File | null>(null);
const removeAvatar = ref(false);

const form = reactive({
    first_name: "",
    middle_name: "",
    last_name: "",
    gender: "Male",
    date_of_birth: "",
    phone_number: "",
    address: "",
    citizenship: "",
    height: "",
    weight: "",
    blood_type: "",
    allergies: "",
});

const customAvatar = computed(() => {
    const avatar = props.patient.avatar;

    return avatar && !avatar.startsWith(FALLBACK_AVATAR_HOST) ? avatar : null;
});

function localDate(value?: string | null) {
    if (!value) return "";

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? "" : getLocalDateStr(date);
}

function fill() {
    const p = props.patient;

    Object.assign(form, {
        first_name: p.first_name ?? "",
        middle_name: p.middle_name ?? "",
        last_name: p.last_name ?? "",
        gender: p.gender ?? "Male",
        date_of_birth: localDate(p.date_of_birth),
        phone_number: p.phone_number ?? "",
        address: p.location?.full_address ?? "",
        citizenship: p.citizenship ?? "",
        height: p.height ?? "",
        weight: p.weight ?? "",
        blood_type: p.blood_type ?? "",
        allergies: (p.allergies ?? []).join(", "),
    });

    errors.value = {};
    avatarFile.value = null;
    removeAvatar.value = false;
}

function set(key: keyof typeof form, value: string) {
    form[key] = value;
    delete errors.value[key];
}

async function submit() {
    if (saving.value) return;

    saving.value = true;
    errors.value = {};

    try {
        const res: any = await patientService.update(props.patient.uuid, {
            branch_uuid: props.branchUuid,
            ...form,
            ...(avatarFile.value ? { avatar: avatarFile.value } : {}),
            ...(removeAvatar.value && !avatarFile.value
                ? { remove_avatar: true }
                : {}),
        });

        success(res?.message ?? "Patient updated successfully.");
        emit("saved", res.data);
        close();
    } catch (err: any) {
        const fieldErrors = err?.errors ?? {};

        errors.value = Object.fromEntries(
            Object.entries(fieldErrors).map(([key, value]: any) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ]),
        );

        error(err?.message ?? "Unable to update this patient.");
    } finally {
        saving.value = false;
    }
}

function close() {
    emit("close");
}

watch(
    () => props.open,
    (open) => {
        if (open) fill();
    },
    { immediate: true },
);

watch(avatarFile, (file) => {
    if (file) removeAvatar.value = false;
});
</script>
