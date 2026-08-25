<script setup lang="ts">
import { ref, computed } from "vue";
import Icon from "./Icon.vue";
useHead({ title: "Settings" });
definePageMeta({
    layout: "portal",
});
interface ActivityEntry {
    id: number;
    time: string;
    title: string;
    subtitle: string;
    icon: string;
    iconBg: string;
    iconText: string;
    status: string;
    statusBg: string;
    statusText: string;
}

const props = withDefaults(
    defineProps<{
        isVip?: boolean;
        residentName?: string;
        residentPhoto?: string;
        roomFeed?: string;
        roomLabel?: string;
    }>(),
    {
        isVip: false,
        residentName: "Resident",
        residentPhoto: "",
        roomFeed: "",
        roomLabel: "Room",
    },
);

const ROOM_FEED = computed(() => props.roomFeed);

const cameraPlaying = ref(true);
const cameraMuted = ref(true);
const zoomLevel = ref(1);
const isSpeaking = ref(false);
const snapshotMessage = ref("");
const videoWrapRef = ref<HTMLElement | null>(null);

function zoomIn() {
    zoomLevel.value = Math.min(2, +(zoomLevel.value + 0.25).toFixed(2));
}

function zoomOut() {
    zoomLevel.value = Math.max(1, +(zoomLevel.value - 0.25).toFixed(2));
}

interface Snapshot {
    id: number;
    url: string;
    time: string;
}

const snapshots = ref<Snapshot[]>([
    { id: 1, url: ROOM_FEED.value, time: "Yesterday · 6:40 PM" },
    { id: 2, url: ROOM_FEED.value, time: "Yesterday · 1:15 PM" },
    { id: 3, url: ROOM_FEED.value, time: "Mon · 9:02 AM" },
]);

function takeSnapshot() {
    snapshotMessage.value = "Snapshot saved to your gallery.";
    snapshots.value.unshift({
        id: Date.now(),
        url: ROOM_FEED.value,
        time: "Just now",
    });
    setTimeout(() => (snapshotMessage.value = ""), 2200);
}

function toggleSpeak() {
    isSpeaking.value = !isSpeaking.value;
}

function expandView() {
    const el = videoWrapRef.value;
    if (!el) return;
    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else {
        el.requestFullscreen?.();
    }
}

const cameraInfo = {
    name: "Room Camera",
    status: "Online",
    quality: "HD",
    connection: "Stable",
};

const roomInfo = computed(() => ({
    type: "VIP Room",
    number: "101",
    floor: "1st Floor",
    caregiver: "Anna Cruz",
    nextCare: "2:00 PM",
}));

const todaysActivity: ActivityEntry[] = [
    {
        id: 1,
        time: "1:00 PM",
        title: "Next Appointment",
        subtitle: "Doctor consultation with Dr. Gem Manolo",
        icon: "calendar-clock",
        iconBg: "bg-violet-50",
        iconText: "text-violet-600",
        status: "Upcoming",
        statusBg: "bg-violet-50",
        statusText: "text-violet-600",
    },
    {
        id: 2,
        time: "9:45 AM",
        title: "Physical Therapy",
        subtitle: "Completed session",
        icon: "activity",
        iconBg: "bg-emerald-50",
        iconText: "text-emerald-600",
        status: "Completed",
        statusBg: "bg-emerald-50",
        statusText: "text-emerald-600",
    },
    {
        id: 3,
        time: "8:30 AM",
        title: "Breakfast",
        subtitle: "Breakfast completed",
        icon: "utensils",
        iconBg: "bg-brand-50",
        iconText: "text-brand-600",
        status: "Completed",
        statusBg: "bg-emerald-50",
        statusText: "text-emerald-600",
    },
    {
        id: 4,
        time: "8:00 AM",
        title: "Medication",
        subtitle: "Amlodipine 5mg",
        icon: "pill",
        iconBg: "bg-amber-50",
        iconText: "text-amber-600",
        status: "Given",
        statusBg: "bg-emerald-50",
        statusText: "text-emerald-600",
    },
    {
        id: 5,
        time: "7:30 AM",
        title: "Wake Up",
        subtitle: "Resident woke up",
        icon: "clock",
        iconBg: "bg-gray-100",
        iconText: "text-gray-500",
        status: "Completed",
        statusBg: "bg-emerald-50",
        statusText: "text-emerald-600",
    },
];
</script>

<template>
    <div v-if="isVip" class="space-y-5 p-4 sm:p-6 lg:p-8">
        <div class="grid grid-cols-1 gap-5 items-start lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-5">
                <div
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <img
                                v-if="residentPhoto"
                                :src="residentPhoto"
                                class="w-11 h-11 rounded-full object-cover object-top"
                                alt=""
                            />
                            <div
                                class="w-11 h-11 rounded-full bg-gray-200"
                                v-else
                            ></div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        {{ residentName }}
                                    </p>
                                    <span
                                        class="flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold text-amber-900 bg-gradient-to-b from-amber-300 to-yellow-500 shadow-sm"
                                    >
                                        <Icon name="crown" class="w-3 h-3" />
                                        VIP
                                    </span>
                                    <span
                                        class="flex items-center gap-1 text-emerald-600 text-xs font-medium"
                                    >
                                        <span
                                            class="w-1.5 h-1.5 rounded-full bg-emerald-500"
                                        />
                                        Online
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    {{ roomLabel }} · Last updated just now
                                </p>
                            </div>
                        </div>
                        <span
                            class="flex items-center gap-1 text-xs font-medium text-brand-600 bg-brand-50 px-3 py-1.5 rounded-lg"
                        >
                            <span
                                class="w-1.5 h-1.5 rounded-full bg-brand-500"
                            />
                            Live
                        </span>
                    </div>

                    <div
                        ref="videoWrapRef"
                        class="relative rounded-xl overflow-hidden bg-gray-900 w-full h-[360px]"
                    >
                        <img
                            v-if="ROOM_FEED"
                            :src="ROOM_FEED"
                            class="w-full h-full object-cover transition-transform duration-200"
                            :style="{ transform: `scale(${zoomLevel})` }"
                            alt="Live room feed"
                        />
                        <div
                            class="absolute top-3 left-3 bg-black/50 text-white text-[11px] px-2 py-1 rounded"
                        >
                            Live feed
                        </div>
                        <span
                            class="absolute top-3 right-3 flex items-center gap-1 bg-black/50 text-emerald-400 text-[11px] font-medium px-2 py-1 rounded"
                        >
                            <span
                                class="w-1.5 h-1.5 rounded-full bg-emerald-400"
                            />
                            LIVE
                        </span>
                        <div
                            v-if="isSpeaking"
                            class="absolute top-11 right-3 bg-rose-600/90 text-white text-[11px] font-medium px-2 py-1 rounded flex items-center gap-1"
                        >
                            <Icon name="mic" class="w-3 h-3" /> Speaking…
                        </div>
                        <div
                            v-if="snapshotMessage"
                            class="absolute inset-x-0 top-3 mx-auto w-fit bg-white/90 text-gray-800 text-[11px] font-medium px-3 py-1 rounded-full shadow"
                        >
                            {{ snapshotMessage }}
                        </div>
                        <div
                            class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent px-4 py-3 flex items-center gap-3"
                        >
                            <button
                                @click="cameraPlaying = !cameraPlaying"
                                class="text-white"
                            >
                                <Icon
                                    :name="cameraPlaying ? 'pause' : 'play'"
                                    class="w-4 h-4"
                                />
                            </button>
                            <button
                                @click="cameraMuted = !cameraMuted"
                                class="text-white"
                            >
                                <Icon
                                    :name="
                                        cameraMuted ? 'volume-x' : 'volume-2'
                                    "
                                    class="w-4 h-4"
                                />
                            </button>
                            <button
                                @click="takeSnapshot"
                                class="text-white ml-auto"
                            >
                                <Icon name="camera" class="w-4 h-4" />
                            </button>
                            <button @click="expandView" class="text-white">
                                <Icon name="maximize" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-2 mt-4">
                        <button
                            @click="zoomIn"
                            class="flex flex-col items-center gap-1.5 py-3 rounded-xl border border-gray-100 hover:bg-gray-50"
                        >
                            <Icon
                                name="zoom-in"
                                class="w-4 h-4 text-gray-500"
                            />
                            <span class="text-[11px] text-gray-500"
                                >Zoom In</span
                            >
                        </button>
                        <button
                            @click="zoomOut"
                            class="flex flex-col items-center gap-1.5 py-3 rounded-xl border border-gray-100 hover:bg-gray-50"
                        >
                            <Icon
                                name="zoom-out"
                                class="w-4 h-4 text-gray-500"
                            />
                            <span class="text-[11px] text-gray-500"
                                >Zoom Out</span
                            >
                        </button>
                        <button
                            @click="takeSnapshot"
                            class="flex flex-col items-center gap-1.5 py-3 rounded-xl border border-gray-100 hover:bg-gray-50"
                        >
                            <Icon
                                name="aperture"
                                class="w-4 h-4 text-gray-500"
                            />
                            <span class="text-[11px] text-gray-500"
                                >Snapshot</span
                            >
                        </button>
                        <button
                            @click="toggleSpeak"
                            class="flex flex-col items-center gap-1.5 py-3 rounded-xl border hover:bg-gray-50"
                            :class="
                                isSpeaking
                                    ? 'border-rose-200 bg-rose-50'
                                    : 'border-gray-100'
                            "
                        >
                            <Icon
                                name="mic"
                                class="w-4 h-4"
                                :class="
                                    isSpeaking
                                        ? 'text-rose-600'
                                        : 'text-gray-500'
                                "
                            />
                            <span
                                class="text-[11px]"
                                :class="
                                    isSpeaking
                                        ? 'text-rose-600'
                                        : 'text-gray-500'
                                "
                                >Speak</span
                            >
                        </button>
                    </div>
                    <p
                        class="flex items-center gap-1.5 text-xs text-brand-600 bg-brand-50 rounded-lg px-3 py-2 mt-3"
                    >
                        <Icon
                            name="shield-check"
                            class="w-3.5 h-3.5 shrink-0"
                        />
                        You can speak to your loved one through the speaker.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
                    >
                        <p class="text-sm font-semibold text-gray-800 mb-4">
                            Camera Information
                        </p>
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-400">Camera Name</dt>
                                <dd class="font-medium text-gray-800">
                                    {{ cameraInfo.name }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-400">Status</dt>
                                <dd class="font-medium text-emerald-600">
                                    {{ cameraInfo.status }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-400">Video Quality</dt>
                                <dd class="font-medium text-gray-800">
                                    {{ cameraInfo.quality }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-400">Connection</dt>
                                <dd class="font-medium text-emerald-600">
                                    {{ cameraInfo.connection }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div
                        class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm font-semibold text-gray-800">
                                Recent Snapshots
                            </p>
                            <span class="text-[11px] text-gray-400"
                                >{{ snapshots.length }} saved</span
                            >
                        </div>
                        <div
                            v-if="snapshots.length"
                            class="grid grid-cols-3 gap-2"
                        >
                            <div
                                v-for="s in snapshots.slice(0, 6)"
                                :key="s.id"
                                class="rounded-lg overflow-hidden relative group"
                            >
                                <img
                                    :src="s.url"
                                    class="w-full aspect-square object-cover"
                                    alt=""
                                />
                                <span
                                    class="absolute inset-x-0 bottom-0 bg-black/50 text-white text-[9px] px-1.5 py-1 truncate"
                                    >{{ s.time }}</span
                                >
                            </div>
                        </div>
                        <div v-else class="text-center py-6">
                            <Icon
                                name="aperture"
                                class="w-6 h-6 text-gray-300 mx-auto mb-1.5"
                            />
                            <p class="text-xs text-gray-400">
                                No snapshots yet — use the Snapshot button on
                                the feed.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
                >
                    <p class="text-sm font-semibold text-gray-800 mb-4">
                        Today's Activity
                    </p>
                    <ul class="space-y-3">
                        <li
                            v-for="a in todaysActivity"
                            :key="a.id"
                            class="flex items-start gap-3"
                        >
                            <span
                                class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                :class="[a.iconBg, a.iconText]"
                            >
                                <Icon :name="a.icon" class="w-3.5 h-3.5" />
                            </span>
                            <div class="flex-1 min-w-0">
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <p
                                        class="text-xs font-medium text-gray-800"
                                    >
                                        {{ a.title }}
                                    </p>
                                    <span
                                        class="text-[10px] font-medium px-1.5 py-0.5 rounded shrink-0"
                                        :class="[a.statusBg, a.statusText]"
                                        >{{ a.status }}</span
                                    >
                                </div>
                                <p class="text-[11px] text-gray-400">
                                    {{ a.subtitle }}
                                </p>
                                <p class="text-[10px] text-gray-300 mt-0.5">
                                    {{ a.time }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>

                <div
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5"
                >
                    <p class="text-sm font-semibold text-gray-800 mb-4">
                        Room Information
                    </p>
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-400">Room Type</dt>
                            <dd class="font-medium text-gray-800">
                                {{ roomInfo.type }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-400">Room Number</dt>
                            <dd class="font-medium text-gray-800">
                                {{ roomInfo.number }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-400">Floor</dt>
                            <dd class="font-medium text-gray-800">
                                {{ roomInfo.floor }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-400">Caregiver on Duty</dt>
                            <dd class="font-medium text-gray-800">
                                {{ roomInfo.caregiver }}
                            </dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-gray-400">Next Care Time</dt>
                            <dd class="font-medium text-gray-800">
                                {{ roomInfo.nextCare }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-brand-50 rounded-2xl p-5 flex items-center gap-4">
            <Icon name="shield-check" class="w-6 h-6 text-brand-600 shrink-0" />
            <div class="flex-1">
                <p class="text-sm font-semibold text-brand-700">
                    Your loved one's privacy and safety are our top priority.
                </p>
                <p class="text-xs text-brand-600">
                    This live camera is for family viewing only. Please use it
                    responsibly.
                </p>
            </div>
        </div>
    </div>

    <div v-else class="space-y-5 p-4 sm:p-6 lg:p-8">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div
                class="rounded-xl bg-gray-50 border border-dashed border-gray-200 aspect-video max-w-3xl flex flex-col items-center justify-center text-center px-8 mx-auto"
            >
                <Icon name="lock" class="w-8 h-8 text-gray-300 mb-2" />
                <p class="text-sm font-medium text-gray-700">
                    Live camera isn't included in the Common Room plan
                </p>
                <p class="text-xs text-gray-400 mt-1 max-w-xs">
                    Caregivers post care photos regularly so you can still see
                    how {{ residentName.split(" ")[0] }} is doing.
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-sm font-semibold text-gray-800 mb-4">
                Care Photos from Caregivers
            </p>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <img
                    v-for="n in 4"
                    :key="n"
                    :src="ROOM_FEED"
                    class="w-full rounded-lg aspect-square object-cover"
                    alt=""
                />
            </div>
        </div>
    </div>
</template>
