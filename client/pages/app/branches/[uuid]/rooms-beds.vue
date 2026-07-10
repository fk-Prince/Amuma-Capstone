<script setup lang="ts">
import { roomService } from "~/api/room/RoomService";
import RoomList from "~/components/sections/app/Room/RoomList.vue";
import RoomSearch from "~/components/sections/app/Room/RoomSearch.vue";
import type { Room, RoomForm } from "~/types/room";
import { useRoute } from "vue-router";
import RoomDashboard from "~/components/sections/app/Room/RoomDashboard.vue";
import RoomModal from "~/components/sections/app/Room/RoomModal.vue";
import { createRoomForm } from "~/types/room";
import { useToast } from "~/composables/useToast";
const { success, error } = useToast();

definePageMeta({
    layout: "dashboard",
    middleware: "auth-client",
});

useHead({ title: "Room & Beds" });
const searchData = ref("");
const activeTab = ref("All Rooms");
const expandedRooms = ref<number[]>([]);
const toggleRoom = (id: number) => {
    expandedRooms.value.includes(id)
        ? (expandedRooms.value = expandedRooms.value.filter((x) => x !== id))
        : expandedRooms.value.push(id);
};

const route = useRoute();
const uuid = route.params.uuid as string;
const loading = ref(true);
const roomData = ref<Room[]>([]);

onMounted(async () => fetchRoom());

const fetchRoom = async () => {
    loading.value = true;
    try {
        const res = await roomService.list({
            per_page: 10,
            branch_uuid: uuid,
        });
        roomData.value = res.data;
    } catch (err: any) {
        console.error(err);
    } finally {
        loading.value = false;
    }
};

const filteredRooms = computed(() => {
    let rooms = roomData.value;

    if (activeTab.value === "VIP Rooms") {
        rooms = rooms.filter((room: Room) => room.room_type === "VIP");
    }

    if (activeTab.value === "Common Rooms") {
        rooms = rooms.filter((room: Room) => room.room_type === "Common");
    }

    if (searchData.value.trim()) {
        const keyword = searchData.value.toLowerCase();

        rooms = rooms.filter((room: Room) => {
            const roomMatch = room.room_no.toLowerCase().includes(keyword);

            const patientMatch = room.beds.some((bed) => {
                const patientName = `${bed.patient?.first_name ?? ""} ${
                    bed.patient?.last_name ?? ""
                }`.toLowerCase();

                return patientName.includes(keyword);
            });

            return roomMatch || patientMatch;
        });
    }

    return rooms;
});

const modalOpen = ref(false);
const roomForm = reactive(createRoomForm());
const title = ref("");
const subtitle = ref("");
const buttonTitle = ref("");
const submitLoading = ref(false);
const errors = ref<Record<string, string>>({});

const editingRoomId = ref<number | null>(null);

const addRoomClicked = () => {
    editingRoomId.value = null;
    Object.assign(roomForm, createRoomForm());
    errors.value = {};
    modalOpen.value = true;
    title.value = "Add Room";
    subtitle.value = "Fill in the details below to create a new room.";
    buttonTitle.value = "Add Room";
};

const editRoomClicked = (room: Room) => {
    editingRoomId.value = room.room_id;
    errors.value = {};
    Object.assign(roomForm, {
        room_no: room.room_no,
        floor: room.floor,
        branch_uuid: uuid,
        room_type: room.room_type,
        capacity: room.capacity,
        status: room.status,
        room_id: room.room_id,
    });
    modalOpen.value = true;
    title.value = "Update Room";
    subtitle.value = "Edit the details below to update this room.";
    buttonTitle.value = "Update Room";
};

const submitRoom = async () => {
    submitLoading.value = true;
    roomForm.branch_uuid = uuid;
    try {
        const res = editingRoomId.value
            ? await roomService.update(editingRoomId.value, roomForm)
            : await roomService.create(roomForm);

        success(
            res.message ??
                (editingRoomId.value
                    ? "Room updated successfully!"
                    : "Room added successfully!"),
        );
        closeModal();
        fetchRoom();
    } catch (err: any) {
        const error = err?.data?.errors;
        console.error(err);
        if (error && Object.keys(err).length > 0) {
            errors.value = Object.fromEntries(
                Object.entries(error).map(([key, value]: any) => [
                    key,
                    Array.isArray(value) ? value[0] : value,
                ]),
            );
        } else {
            error(err?.data?.message ?? "Something went wrong.");
        }
    } finally {
        submitLoading.value = false;
    }
};

const closeModal = () => {
    modalOpen.value = false;
    editingRoomId.value = null;
    errors.value = {};
    submitLoading.value = false;
    Object.assign(roomForm, createRoomForm());
};
</script>

<template>
    <div class="w-full max-w-8xl mx-auto p-4 md:p-6 space-y-5">
        <RoomDashboard @addRoom="addRoomClicked" />

        <RoomSearch v-model="searchData" v-model:activeTab="activeTab" />

        <RoomList
            :loading="loading"
            :rooms="filteredRooms"
            :expandedRooms="expandedRooms"
            @toggle="toggleRoom"
            @edit="editRoomClicked"
        />

        <RoomModal
            v-if="modalOpen"
            :form="roomForm"
            @close="closeModal"
            :errors="errors"
            @submit="submitRoom"
            :title="title"
            :subtitle="subtitle"
            :button-title="buttonTitle"
            :submitLoading="submitLoading"
        />
    </div>
</template>
