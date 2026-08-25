<script setup lang="ts">
import { roomService } from "~/api/room/RoomService";
import RoomList from "~/components/sections/app/Room/RoomList.vue";
import RoomSearch from "~/components/sections/app/Room/RoomSearch.vue";
import type { Room, RoomForm, Overview } from "~/types/room";
import { useRoute } from "vue-router";
import RoomDashboard from "~/components/sections/app/Room/RoomDashboard.vue";
import RoomModal from "~/components/sections/app/Room/RoomModal.vue";
import { createRoomForm, roomSchema } from "~/types/room";
import { useToast } from "~/composables/useToast";
import type { Bed, BedForm } from "~/types/bed";
import { bedService } from "~/api/bed/BedService";
import { usePagination } from "~/composables/usePagination";

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

const roomData = ref<Room[]>([]);
const isLoading = ref(false);
const isFetching = ref(false);

const pagination = usePagination({ pageSize: 20 });

const roomTypeParam = computed(() => {
    if (activeTab.value === "VIP Rooms") return "VIP";
    if (activeTab.value === "Common Rooms") return "Common";
    return undefined;
});

let requestId = 0;

async function fetchRoom() {
    const thisRequest = ++requestId;
    isFetching.value = true;

    try {
        const res: any = await roomService.list({
            branch_uuid: uuid,
            page: pagination.currentPage.value,
            per_page: pagination.pageSize.value,
            search: searchData.value.trim() || undefined,
            room_type: roomTypeParam.value,
        });

        if (thisRequest !== requestId) return;

        roomData.value = res.data;

        const total = res.meta?.total ?? res.total ?? res.data.length;
        pagination.setTotal(total);
    } catch (err: any) {
        console.error(err);
    } finally {
        isFetching.value = false;
        isLoading.value = false;
    }
}

const overview = ref<Overview>();

async function fetchOverview() {
    try {
        const res: any = await roomService.overview({
            branch_uuid: uuid,
        });
        overview.value = res;
    } catch (err: any) {
        console.error(err);
    }
}

function goToPage(page: number) {
    if (page < 1 || page > pagination.totalPages.value) return;
    pagination.currentPage.value = page;
    fetchRoom();
}

const handleClicked = () => {
    pagination.reset();
    fetchRoom();
};

onMounted(async () => {
    await Promise.all([fetchRoom(), fetchOverview()]);
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
    errors.value = {};
    const result = roomSchema.safeParse(roomForm);

    if (!result.success) {
        errors.value = Object.fromEntries(
            result.error.issues.map((issue) => [
                issue.path[0] as string,
                issue.message,
            ]),
        );

        submitLoading.value = false;
        return;
    }
    try {
        const res = editingRoomId.value
            ? await roomService.update(editingRoomId.value, roomForm)
            : await roomService.create(roomForm);

        const savedRoom = res.data;
        if (savedRoom && roomMatchesCurrentFilter(savedRoom)) {
            const index = roomData.value.findIndex(
                (room) => room.room_id === savedRoom.room_id,
            );

            if (index !== -1) {
                roomData.value[index] = {
                    ...roomData.value[index],
                    ...savedRoom,
                    beds: savedRoom.beds ?? roomData.value[index]?.beds ?? [],
                };
            } else {
                roomData.value.unshift({
                    ...savedRoom,
                    beds: savedRoom.beds ?? [],
                });
            }
        }

        success(
            res.message ??
                (editingRoomId.value
                    ? "Room updated successfully!"
                    : "Room added successfully!"),
        );
        // fetchRoom();
        closeModal();
    } catch (err: any) {
        const validationErrors = err?.data?.errors;
        console.error(err);
        if (validationErrors && Object.keys(validationErrors).length > 0) {
            errors.value = Object.fromEntries(
                Object.entries(validationErrors).map(([key, value]: any) => [
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

const bedAction = async (
    action: "create" | "update",
    room: Room,
    bed: BedForm,
    done: () => void,
) => {
    const payload = {
        branch_uuid: uuid,
        room_id: room.room_id,
        bed_no: bed.bed_no,
        status: bed.status,
        bed_id: bed.bed_id ?? null,
    };

    try {
        let res;

        if (action === "create") {
            res = await bedService.create(payload);
            const createdBed = res.data;
            const targetRoom = roomData.value.find(
                (item) => item.room_id === room.room_id,
            );
            if (targetRoom) {
                targetRoom.beds.push(createdBed);
            }
            success(res.message ?? "Bed created successfully.");
        }

        if (action === "update") {
            res = await bedService.update(Number(bed.bed_id), payload);
            const targetRoom = roomData.value.find(
                (item) => item.room_id === room.room_id,
            );
            const targetBed = targetRoom?.beds.find(
                (item) => item.bed_id === bed.bed_id,
            );
            if (targetBed) {
                Object.assign(targetBed, res.data);
            }
            success(res.message ?? "Bed updated successfully.");
        }
    } catch (err: any) {
        const validationErrors = err?.data?.errors;
        console.error(err);
        if (validationErrors && Object.keys(validationErrors).length > 0) {
            errors.value = Object.fromEntries(
                Object.entries(validationErrors).map(([key, value]: any) => [
                    key,
                    Array.isArray(value) ? value[0] : value,
                ]),
            );
        } else {
            error(err?.data?.message ?? "Something went wrong.");
        }
    } finally {
        done();
    }
};

const roomMatchesCurrentFilter = (room: Room) => {
    const search = searchData.value.trim().toLowerCase();

    const matchesSearch =
        !search ||
        String(room.room_no ?? "")
            .toLowerCase()
            .includes(search) ||
        String(room.floor ?? "")
            .toLowerCase()
            .includes(search) ||
        String(room.room_type ?? "")
            .toLowerCase()
            .includes(search);

    const matchesType =
        !roomTypeParam.value ||
        String(room.room_type ?? "").toLowerCase() ===
            roomTypeParam.value.toLowerCase();

    return matchesSearch && matchesType;
};
</script>

<template>
    <div class="min-h-screen-header bg-slate-50">
        <div class="mx-auto max-w-[1700px] space-y-6 p-4 md:p-6">
            <RoomDashboard @addRoom="addRoomClicked" :overview="overview" />

            <div
                class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-100 p-5">
                    <RoomSearch
                        v-model="searchData"
                        v-model:activeTab="activeTab"
                        @search="handleClicked"
                        @addRoom="addRoomClicked"
                    />
                </div>

                <div class="p-5">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">
                                Room Directory
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Browse and manage all rooms and their assigned
                                beds.
                            </p>
                        </div>

                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-600"
                        >
                            {{ pagination.totalItems }}
                            {{
                                pagination.totalItems.value === 1
                                    ? "Room"
                                    : "Rooms"
                            }}
                        </span>
                    </div>

                    <RoomList
                        :loading="isLoading || isFetching"
                        :rooms="roomData"
                        :expandedRooms="expandedRooms"
                        @toggle="toggleRoom"
                        @edit="editRoomClicked"
                        @bedAction="bedAction"
                        :errors="errors"
                    />

                    <div
                        v-if="!isLoading && roomData && roomData.length > 0"
                        class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 mt-2 border-t border-slate-100"
                    >
                        <p class="text-xs text-slate-400">
                            Showing {{ pagination.rangeStart }}–{{
                                pagination.rangeEnd
                            }}
                            of
                            {{ pagination.totalItems }}
                        </p>

                        <div class="flex items-center gap-1">
                            <button
                                type="button"
                                class="px-3 py-1.5 text-xs font-medium rounded-md border border-slate-200 text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition"
                                :disabled="!pagination.canGoPrev"
                                @click="
                                    goToPage(pagination.currentPage.value - 1)
                                "
                            >
                                Prev
                            </button>

                            <button
                                v-for="p in pagination.pageNumbers.value"
                                :key="p"
                                type="button"
                                class="w-8 h-8 text-xs font-medium rounded-md border transition"
                                :class="
                                    p === pagination.currentPage.value
                                        ? 'bg-primary text-white border-primary/80'
                                        : 'border-slate-200 text-slate-700 hover:bg-slate-50'
                                "
                                @click="goToPage(p)"
                            >
                                {{ p }}
                            </button>

                            <button
                                type="button"
                                class="px-3 py-1.5 text-xs font-medium rounded-md border border-slate-200 text-slate-700 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-slate-50 transition"
                                :disabled="!pagination.canGoNext"
                                @click="
                                    goToPage(pagination.currentPage.value + 1)
                                "
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <RoomModal
                v-if="modalOpen"
                :form="roomForm"
                :errors="errors"
                :title="title"
                :subtitle="subtitle"
                :button-title="buttonTitle"
                :submitLoading="submitLoading"
                @close="closeModal"
                @submit="submitRoom"
            />
        </div>
    </div>
</template>
