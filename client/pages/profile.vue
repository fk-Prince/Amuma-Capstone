<template>
    <div class="min-h-screen bg-slate-50/70 pt-[100px] dark:bg-surface">
        <div class="mx-auto max-w-[100rem] px-6 pb-16">
            <div
                class="flex flex-col gap-4 py-6 sm:flex-row sm:items-center sm:justify-between"
            >
                <h1
                    class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
                >
                    My Profile
                </h1>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        :disabled="saving || !isDirty"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                        @click="reset"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        :disabled="saving || !isDirty"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-white dark:text-secondary dark:hover:bg-gray-200"
                        @click="save"
                    >
                        <LoaderCircle
                            v-if="saving"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        {{ saving ? "Saving..." : "Save changes" }}
                    </button>
                </div>
            </div>

            <div
                class="inline-flex flex-wrap gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    class="rounded-lg px-4 py-1.5 text-sm font-medium transition"
                    :class="
                        activeTab === tab.value
                            ? 'bg-primary text-white shadow-sm'
                            : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200'
                    "
                    @click="activeTab = tab.value"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div v-if="loading" class="space-y-10 py-10">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="grid gap-6 lg:grid-cols-[240px_1fr]"
                >
                    <div
                        class="h-10 animate-pulse rounded bg-slate-100 dark:bg-white/10"
                    />
                    <div
                        class="h-24 animate-pulse rounded bg-slate-100 dark:bg-white/10"
                    />
                </div>
            </div>

            <template v-else>
                <!-- PROFILE -->
                <div v-show="activeTab === 'profile'" class="mt-5 space-y-5">
                    <!-- Profile photo -->
                    <section
                        class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:grid-cols-[260px_1fr] dark:border-white/10 dark:bg-secondary"
                    >
                        <div>
                            <h2
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                Profile photo
                            </h2>
                            <p
                                class="mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400"
                            >
                                This photo appears on your profile and anywhere
                                you're shown across AMUMA.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-4">
                            <img
                                :src="avatarPreview || fallbackAvatar"
                                alt="Profile photo"
                                class="h-14 w-14 rounded-full object-cover ring-1 ring-slate-200 dark:ring-white/10"
                            />

                            <button
                                type="button"
                                class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                                @click="avatarInput?.click()"
                            >
                                Change photo
                            </button>

                            <button
                                v-if="avatarPreview"
                                type="button"
                                class="text-sm font-medium text-slate-500 transition hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200"
                                @click="removeAvatar"
                            >
                                Remove
                            </button>

                            <input
                                ref="avatarInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleAvatar"
                            />

                            <p
                                v-if="errors.avatar"
                                class="w-full text-xs text-red-600"
                            >
                                {{ errors.avatar }}
                            </p>
                        </div>
                    </section>

                    <!-- Personal info -->
                    <section
                        class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:grid-cols-[260px_1fr] dark:border-white/10 dark:bg-secondary"
                    >
                        <div>
                            <h2
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                Personal info
                            </h2>
                            <p
                                class="mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400"
                            >
                                Your name and contact details.
                            </p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-6">
                            <BaseInput
                                v-model="form.first_name"
                                label="First name"
                                class-name="sm:col-span-2"
                                :error="errors.first_name"
                                @update:modelValue="clearError('first_name')"
                            />

                            <BaseInput
                                v-model="form.middle_name"
                                label="Middle name"
                                class-name="sm:col-span-2"
                                :error="errors.middle_name"
                                @update:modelValue="clearError('middle_name')"
                            />

                            <BaseInput
                                v-model="form.last_name"
                                label="Last name"
                                class-name="sm:col-span-2"
                                :error="errors.last_name"
                                @update:modelValue="clearError('last_name')"
                            />

                            <BaseInput
                                v-model="form.email"
                                label="Email"
                                mode="email"
                                class-name="sm:col-span-6"
                                :error="errors.email"
                                @update:modelValue="clearError('email')"
                            />

                            <PhoneInput
                                v-model="form.phone_number"
                                label="Contact number"
                                class-name="sm:col-span-3"
                                :error="errors.phone_number"
                                @update:modelValue="clearError('phone_number')"
                            />

                            <BaseInput
                                v-if="roles.is_employee"
                                v-model="form.birth_date"
                                label="Birth date"
                                mode="date"
                                class-name="sm:col-span-3"
                                :max="today"
                                :error="errors.birth_date"
                                @update:modelValue="clearError('birth_date')"
                            />

                            <BaseInput
                                v-if="roles.is_client"
                                v-model="form.occupation"
                                label="Occupation"
                                class-name="sm:col-span-3"
                                :error="errors.occupation"
                                @update:modelValue="clearError('occupation')"
                            />
                        </div>
                    </section>

                    <!-- Address -->
                    <section
                        v-if="canEditLocation"
                        class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:grid-cols-[260px_1fr] dark:border-white/10 dark:bg-secondary"
                    >
                        <div>
                            <h2
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                Address
                            </h2>
                            <p
                                class="mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400"
                            >
                                Where you're based.
                            </p>

                            <button
                                type="button"
                                class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-primary transition hover:text-primary-700"
                                @click="useMap = !useMap"
                            >
                                <MapPin class="h-3.5 w-3.5" />
                                {{ useMap ? "Enter manually" : "Pick on map" }}
                            </button>
                        </div>

                        <div>
                            <ClientOnly v-if="useMap">
                                <LocationSelector
                                    :initial-lat="form.latitude || undefined"
                                    :initial-lng="form.longitude || undefined"
                                    :initial-street="form.street || undefined"
                                    :initial-city="form.city || undefined"
                                    :initial-province="
                                        form.province || undefined
                                    "
                                    :initial-country="form.country || undefined"
                                    @location-selected="handleLocation"
                                />

                                <template #fallback>
                                    <div
                                        class="flex h-64 items-center justify-center rounded-lg bg-slate-50 text-sm text-slate-400 dark:bg-white/5 dark:text-gray-500"
                                    >
                                        Loading map...
                                    </div>
                                </template>
                            </ClientOnly>

                            <div v-else class="grid gap-5 sm:grid-cols-6">
                                <BaseInput
                                    v-model="form.street"
                                    label="Street"
                                    class-name="sm:col-span-6"
                                    :error="errors.street"
                                    @update:modelValue="clearError('street')"
                                />

                                <BaseInput
                                    v-model="form.city"
                                    label="City"
                                    class-name="sm:col-span-3"
                                    :error="errors.city"
                                    @update:modelValue="clearError('city')"
                                />

                                <BaseInput
                                    v-model="form.province"
                                    label="Province"
                                    class-name="sm:col-span-3"
                                    :error="errors.province"
                                    @update:modelValue="clearError('province')"
                                />

                                <BaseInput
                                    v-model="form.country"
                                    label="Country"
                                    class-name="sm:col-span-3"
                                    :error="errors.country"
                                    @update:modelValue="clearError('country')"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Password -->
                    <section
                        class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:grid-cols-[260px_1fr] dark:border-white/10 dark:bg-secondary"
                    >
                        <div>
                            <h2
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                Password
                            </h2>
                            <p
                                class="mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400"
                            >
                                {{
                                    meta.has_password
                                        ? "Set a new password for your account."
                                        : "Add a password so you can sign in without Google."
                                }}
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <BaseInput
                                    v-if="meta.has_password"
                                    v-model="form.current_password"
                                    label="Current password"
                                    mode="password"
                                    :error="errors.current_password"
                                    @update:modelValue="
                                        clearError('current_password')
                                    "
                                />

                                <div>
                                    <BaseInput
                                        v-model="form.password"
                                        label="New password"
                                        mode="password"
                                        :error="errors.password"
                                        @update:modelValue="
                                            clearError('password')
                                        "
                                    />

                                    <p
                                        v-if="!errors.password"
                                        class="mt-1.5 text-xs text-slate-400 dark:text-gray-500"
                                    >
                                        Minimum 8 characters
                                    </p>
                                </div>
                            </div>

                            <BaseInput
                                v-if="form.password"
                                v-model="form.password_confirmation"
                                label="Confirm new password"
                                mode="password"
                                class="sm:max-w-[calc(50%-0.625rem)]"
                                :error="errors.password_confirmation"
                                @update:modelValue="
                                    clearError('password_confirmation')
                                "
                            />

                            <button
                                type="button"
                                :disabled="saving || !form.password"
                                class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                                @click="save"
                            >
                                Update password
                            </button>
                        </div>
                    </section>

                    <!-- Account -->
                    <section class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:grid-cols-[260px_1fr] dark:border-white/10 dark:bg-secondary">
                        <div>
                            <h2
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                Account
                            </h2>
                            <p
                                class="mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400"
                            >
                                Details here are read-only.
                            </p>
                        </div>

                        <dl
                            class="divide-y divide-slate-100 dark:divide-white/10"
                        >
                            <div
                                class="flex items-center justify-between gap-4 py-2.5"
                            >
                                <dt
                                    class="text-sm text-slate-500 dark:text-gray-400"
                                >
                                    Sign-in method
                                </dt>
                                <dd
                                    class="truncate text-sm font-medium text-slate-800 dark:text-white"
                                >
                                    {{ signInMethods }}
                                </dd>
                            </div>

                            <div
                                v-for="row in accountRows"
                                :key="row.label"
                                class="flex items-center justify-between gap-4 py-2.5"
                            >
                                <dt
                                    class="text-sm text-slate-500 dark:text-gray-400"
                                >
                                    {{ row.label }}
                                </dt>
                                <dd
                                    class="truncate text-sm font-medium text-slate-800 dark:text-white"
                                >
                                    {{ row.value }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <!-- NOTIFICATIONS -->
                <div v-show="activeTab === 'notifications'" class="mt-5 space-y-5">
                    <section class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:grid-cols-[260px_1fr] dark:border-white/10 dark:bg-secondary">
                        <div>
                            <h2
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                Email preferences
                            </h2>
                            <p
                                class="mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400"
                            >
                                What we send to your inbox.
                            </p>
                        </div>

                        <div class="space-y-5">
                            <div
                                v-for="pref in preferences"
                                :key="pref.key"
                                class="flex items-start gap-3"
                            >
                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="pref.enabled"
                                    class="relative mt-0.5 h-5 w-9 shrink-0 rounded-full transition-colors"
                                    :class="
                                        pref.enabled
                                            ? 'bg-primary'
                                            : 'bg-slate-200 dark:bg-white/10'
                                    "
                                    @click="pref.enabled = !pref.enabled"
                                >
                                    <span
                                        class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform"
                                        :class="
                                            pref.enabled
                                                ? 'translate-x-4'
                                                : 'translate-x-0'
                                        "
                                    />
                                </button>

                                <div>
                                    <p
                                        class="text-sm font-medium text-slate-800 dark:text-white"
                                    >
                                        {{ pref.label }}
                                    </p>
                                    <p
                                        class="text-sm text-slate-500 dark:text-gray-400"
                                    >
                                        {{ pref.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- <p class="text-xs text-slate-400">
                                Email preferences aren't saved yet — this
                                section is a placeholder.
                            </p> -->
                        </div>
                    </section>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from "vue";
import { LoaderCircle, MapPin } from "lucide-vue-next";

import BaseInput from "~/components/ui/BaseInput.vue";
import PhoneInput from "~/components/ui/PhoneInput.vue";
import LocationSelector from "~/components/ui/LocationSelector.vue";
import { userService } from "~/api/user/UserService";
import { useToast } from "~/composables/useToast";
import { fetchAuthUser } from "~/composables/useAuthUser";

definePageMeta({
    middleware: "auth-client",
    navVariant: 1,
    theme: "light",
});

useHead({ title: "My Profile" });

const { success, error } = useToast();

const loading = ref(true);
const saving = ref(false);
const useMap = ref(false);
const activeTab = ref("profile");

const tabs = [
    { label: "General", value: "profile" },
    { label: "Notifications", value: "notifications" },
];

const preferences = reactive([
    {
        key: "schedule",
        label: "Schedule updates",
        description: "Changes to visits and shifts assigned to you.",
        enabled: true,
    },
    {
        key: "billing",
        label: "Billing notices",
        description: "Invoices, payments and subscription renewals.",
        enabled: true,
    },
    {
        key: "product",
        label: "Product news",
        description: "Feature announcements and tips from AMUMA.",
        enabled: false,
    },
]);

const avatarInput = ref<HTMLInputElement | null>(null);
const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);
const avatarCleared = ref(false);

const errors = ref<Record<string, string>>({});

const roles = reactive({
    is_employee: false,
    is_client: false,
    is_system_owner: false,
});

const form = reactive({
    first_name: "",
    middle_name: "",
    last_name: "",
    email: "",
    phone_number: "",
    birth_date: "",
    occupation: "",

    street: "",
    city: "",
    province: "",
    country: "",
    latitude: 0,
    longitude: 0,

    current_password: "",
    password: "",
    password_confirmation: "",
});

const meta = reactive({
    provider: "local",
    uuid: "",
    created_at: "",
    has_password: true,
});

const original = ref<Record<string, any>>({});

const today = new Date().toISOString().slice(0, 10);

// Every account type (employee, client, system owner) has its own
// phone_number column now, so this is no longer role-gated.
const canEditPhone = computed(() => true);
const canEditLocation = computed(
    () => roles.is_employee || roles.is_client || roles.is_system_owner,
);

const trackedKeys = [
    "first_name",
    "middle_name",
    "last_name",
    "email",
    "phone_number",
    "birth_date",
    "occupation",
    "street",
    "city",
    "province",
    "country",
    "latitude",
    "longitude",
] as const;

const isDirty = computed(
    () =>
        Boolean(avatarFile.value) ||
        avatarCleared.value ||
        Boolean(form.password) ||
        trackedKeys.some((key) => original.value[key] !== form[key]),
);

const memberSince = computed(() => {
    if (!meta.created_at) return "—";

    try {
        return new Date(meta.created_at).toLocaleDateString("en-US", {
            month: "long",
            year: "numeric",
        });
    } catch {
        return "—";
    }
});

const signInMethods = computed(() => {
    const methods: string[] = [];

    if (meta.has_password) {
        methods.push("Email & password");
    }

    if (meta.provider && meta.provider !== "local") {
        methods.push(
            meta.provider.charAt(0).toUpperCase() + meta.provider.slice(1),
        );
    }

    return methods.length ? methods.join(", ") : "—";
});

const accountRows = computed(() => [
    { label: "Member since", value: memberSince.value },
    { label: "Account ID", value: meta.uuid ? meta.uuid.split("-")[0] : "—" },
]);

const fallbackAvatar = computed(() => {
    const initials = `${form.first_name?.[0] ?? ""}${form.last_name?.[0] ?? ""}`;

    return `https://ui-avatars.com/api/?name=${encodeURIComponent(
        initials || "U",
    )}`;
});

const applyProfile = (data: any) => {
    form.first_name = data.first_name ?? "";
    form.middle_name = data.middle_name ?? "";
    form.last_name = data.last_name ?? "";
    form.email = data.email ?? "";
    form.phone_number = data.phone_number ?? "";
    form.occupation = data.occupation ?? "";
    form.birth_date = data.birth_date
        ? String(data.birth_date).slice(0, 10)
        : "";

    form.street = data.location?.street ?? "";
    form.city = data.location?.city ?? "";
    form.province = data.location?.province ?? "";
    form.country = data.location?.country ?? "";
    form.latitude = Number(data.location?.latitude) || 0;
    form.longitude = Number(data.location?.longitude) || 0;

    form.current_password = "";
    form.password = "";
    form.password_confirmation = "";

    roles.is_employee = Boolean(data.roles?.is_employee);
    roles.is_client = Boolean(data.roles?.is_client);
    roles.is_system_owner = Boolean(data.roles?.is_system_owner);

    meta.provider = data.provider ?? "local";
    meta.uuid = data.uuid ?? "";
    meta.created_at = data.created_at ?? "";
    meta.has_password = data.has_password !== false;

    avatarPreview.value = data.avatar ?? null;
    avatarCleared.value = false;

    original.value = Object.fromEntries(
        trackedKeys.map((key) => [key, form[key]]),
    );
};

const fetchProfile = async () => {
    loading.value = true;

    try {
        const res: any = await userService.profile();
        applyProfile(res?.data ?? res);
    } catch (err: any) {
        error(err?.message ?? "Failed to load your profile.");
    } finally {
        loading.value = false;
    }
};

const handleLocation = ({
    lat,
    lng,
    street,
    city,
    province,
    country,
}: {
    lat: number;
    lng: number;
    street: string;
    city: string;
    province: string;
    country: string;
}) => {
    form.street = street ?? "";
    form.city = city ?? "";
    form.province = province ?? "";
    form.country = country ?? "";
    form.latitude = lat ?? 0;
    form.longitude = lng ?? 0;

    ["street", "city", "province", "country"].forEach(clearError);
};

const handleAvatar = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    avatarFile.value = file;
    avatarCleared.value = false;
    avatarPreview.value = URL.createObjectURL(file);
    clearError("avatar");
};

const removeAvatar = () => {
    avatarFile.value = null;
    avatarPreview.value = null;
    avatarCleared.value = true;

    if (avatarInput.value) {
        avatarInput.value.value = "";
    }
};

const clearError = (field: string) => {
    if (!errors.value[field]) return;

    const next = { ...errors.value };
    delete next[field];
    errors.value = next;
};

const reset = () => {
    Object.assign(form, original.value);

    form.current_password = "";
    form.password = "";
    form.password_confirmation = "";

    avatarFile.value = null;
    avatarCleared.value = false;
    errors.value = {};
};

const save = async () => {
    saving.value = true;
    errors.value = {};

    try {
        const payload: Record<string, any> = {
            first_name: form.first_name,
            middle_name: form.middle_name,
            last_name: form.last_name,
            email: form.email,
        };

        if (canEditPhone.value && form.phone_number) {
            payload.phone_number = form.phone_number;
        }

        if (roles.is_employee && form.birth_date) {
            payload.birth_date = form.birth_date;
        }

        if (roles.is_client && form.occupation) {
            payload.occupation = form.occupation;
        }

        if (canEditLocation.value) {
            Object.assign(payload, {
                street: form.street || undefined,
                city: form.city || undefined,
                province: form.province || undefined,
                country: form.country || undefined,
                latitude: form.latitude || undefined,
                longitude: form.longitude || undefined,
            });
        }

        if (form.password) {
            payload.password = form.password;
            payload.password_confirmation = form.password_confirmation;

            if (meta.has_password) {
                payload.current_password = form.current_password;
            }
        }

        if (avatarFile.value) {
            payload.avatar = avatarFile.value;
        }

        const res: any = await userService.updateProfile(payload);

        applyProfile(res?.data ?? res);
        avatarFile.value = null;
        await fetchAuthUser();
        success("Profile updated.");
    } catch (err: any) {
        const raw = err?.errors ?? {};

        errors.value = Object.fromEntries(
            Object.entries(raw).map(([key, value]: any) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ]),
        );

        error(err?.message ?? "Failed to update your profile.");
    } finally {
        saving.value = false;
    }
};

onMounted(fetchProfile);
</script>
