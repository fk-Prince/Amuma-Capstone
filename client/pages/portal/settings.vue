<script setup lang="ts">
import { ref } from "vue";
import Icon from "./Icon.vue";
// import AvatarUpload from "./AvatarUpload.vue";
useHead({ title: "Settings" });
definePageMeta({
    layout: "portal",
});
defineProps<{
    familyMember: { name: string; role: string; avatar: string };
    isVip: boolean;
}>();

const emit = defineEmits<{ (e: "update-avatar", url: string): void }>();

const toggles = ref({
    emailNotifications: true,
    smsAlerts: false,
    cameraMotionAlerts: true,
    billingReminders: true,
});

const formData = ref({
    fullName: "Nicollette Libunao",
    relationship: "Daughter",
    email: "bunny.wawa@email.com",
    phone: "+63 917 123 4567",
});

const saveSuccess = ref(false);

function handleSave() {
    saveSuccess.value = true;
    setTimeout(() => {
        saveSuccess.value = false;
    }, 2000);
}
</script>

<template>
    <div class="space-y-5 max-w-2xl p-4 sm:p-6 lg:p-8">
        <div
            v-if="saveSuccess"
            class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center gap-3"
        >
            <Icon
                name="check-circle"
                class="w-5 h-5 text-emerald-600 shrink-0"
            />
            <p class="text-sm text-emerald-700 font-medium">
                Changes saved successfully!
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-sm font-semibold text-gray-800 mb-4">Profile</p>
            <div class="flex items-center gap-4 mb-5">
                <AvatarUpload
                    :src="familyMember.avatar"
                    size-class="w-16 h-16"
                    ring-class="ring-2 ring-brand-500 ring-offset-2"
                    @change="emit('update-avatar', $event)"
                />
                <div>
                    <p class="font-semibold text-gray-900">
                        {{ familyMember.name }}
                    </p>
                    <p class="text-xs text-gray-400">{{ familyMember.role }}</p>
                    <p class="text-[11px] text-gray-300 mt-1">
                        Hover your photo to change it
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-xs font-medium text-gray-500"
                        >Full Name</label
                    >
                    <input
                        v-model="formData.fullName"
                        type="text"
                        class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200"
                    />
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500"
                        >Relationship to Resident</label
                    >
                    <input
                        v-model="formData.relationship"
                        type="text"
                        class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200"
                    />
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500"
                        >Email</label
                    >
                    <input
                        v-model="formData.email"
                        type="email"
                        class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200"
                    />
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-500"
                        >Contact Number</label
                    >
                    <input
                        v-model="formData.phone"
                        type="tel"
                        class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-200"
                    />
                </div>
            </div>
            <button
                @click="handleSave"
                class="mt-5 bg-brand-500 text-white text-sm font-medium px-5 py-2 rounded-full hover:bg-brand-600 transition-colors"
            >
                Save Changes
            </button>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-sm font-semibold text-gray-800 mb-4">
                Notification Preferences
            </p>
            <div class="divide-y divide-gray-50">
                <label
                    class="flex items-center justify-between py-3 cursor-pointer hover:bg-gray-50 -mx-5 px-5 transition-colors"
                >
                    <div>
                        <p class="text-sm text-gray-800">Email Notifications</p>
                        <p class="text-xs text-gray-400">
                            Daily care updates sent to your email
                        </p>
                    </div>
                    <input
                        type="checkbox"
                        v-model="toggles.emailNotifications"
                        class="w-4 h-4 accent-brand-500"
                    />
                </label>
                <label
                    class="flex items-center justify-between py-3 cursor-pointer hover:bg-gray-50 -mx-5 px-5 transition-colors"
                >
                    <div>
                        <p class="text-sm text-gray-800">SMS Alerts</p>
                        <p class="text-xs text-gray-400">
                            Urgent alerts sent via text message
                        </p>
                    </div>
                    <input
                        type="checkbox"
                        v-model="toggles.smsAlerts"
                        class="w-4 h-4 accent-brand-500"
                    />
                </label>
                <label
                    v-if="isVip"
                    class="flex items-center justify-between py-3 cursor-pointer hover:bg-gray-50 -mx-5 px-5 transition-colors"
                >
                    <div>
                        <p class="text-sm text-gray-800">
                            Camera Motion Alerts
                        </p>
                        <p class="text-xs text-gray-400">
                            Notify me of unusual activity in the room
                        </p>
                    </div>
                    <input
                        type="checkbox"
                        v-model="toggles.cameraMotionAlerts"
                        class="w-4 h-4 accent-brand-500"
                    />
                </label>
                <label
                    class="flex items-center justify-between py-3 cursor-pointer hover:bg-gray-50 -mx-5 px-5 transition-colors"
                >
                    <div>
                        <p class="text-sm text-gray-800">Billing Reminders</p>
                        <p class="text-xs text-gray-400">
                            Reminders before your payment due date
                        </p>
                    </div>
                    <input
                        type="checkbox"
                        v-model="toggles.billingReminders"
                        class="w-4 h-4 accent-brand-500"
                    />
                </label>
            </div>
        </div>

        <div
            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center justify-between"
        >
            <div class="flex items-center gap-3">
                <span
                    class="w-9 h-9 rounded-lg bg-gray-50 text-gray-500 flex items-center justify-center"
                >
                    <Icon name="lock" class="w-4 h-4" />
                </span>
                <div>
                    <p class="text-sm font-medium text-gray-800">Password</p>
                    <p class="text-xs text-gray-400">
                        Last changed 3 months ago
                    </p>
                </div>
            </div>
            <button
                class="border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-full hover:bg-gray-50 transition-colors"
            >
                Change Password
            </button>
        </div>
    </div>
</template>
