<template>
    <div
        class="overflow-hidden rounded-2xl border border-[#E4EFED] bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
    >
        <!-- Cover photo -->
        <div
            class="relative h-40 w-full bg-gradient-to-br from-secondary-800 via-primary-900 to-primary-700 sm:h-56"
        >
            <img
                v-if="cover"
                :src="cover"
                alt="Cover photo"
                class="absolute inset-0 h-full w-full object-cover"
            />

            <div
                v-else-if="!loading"
                class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-white/70"
            >
                <ImageIcon class="h-8 w-8" />
                <p class="text-sm">No cover photo yet</p>
            </div>

            <div v-if="loading" class="absolute inset-0 animate-pulse bg-white/10" />

            <button
                v-if="canEdit"
                type="button"
                :disabled="uploading"
                class="absolute right-4 top-4 inline-flex items-center gap-2 rounded-full bg-black/45 px-4 py-2 text-xs font-medium text-white ring-1 ring-white/25 backdrop-blur-md transition hover:bg-black/60 disabled:opacity-60"
                @click="fileInput?.click()"
            >
                <LoaderCircle v-if="uploading" class="h-4 w-4 animate-spin" />
                <Camera v-else class="h-4 w-4" />
                {{
                    uploading
                        ? "Uploading..."
                        : cover
                          ? "Change cover photo"
                          : "Upload cover photo"
                }}
            </button>

            <input
                ref="fileInput"
                type="file"
                accept="image/png,image/jpeg,image/webp"
                class="hidden"
                @change="handleFile"
            />
        </div>

        <!-- Profile photo overlapping the cover -->
        <div class="flex flex-wrap items-end gap-4 px-5 pb-5 sm:px-6">
            <button
                type="button"
                class="group relative -mt-12 h-24 w-24 shrink-0 overflow-hidden rounded-2xl border-4 border-white bg-gray-100 shadow-md dark:border-secondary dark:bg-white/10"
                title="Change profile photo"
                @click="goToProfileField"
            >
                <img
                    v-if="profileSrc"
                    :src="profileSrc"
                    alt="Profile photo"
                    class="h-full w-full object-cover"
                />
                <span
                    v-else
                    class="flex h-full w-full items-center justify-center text-gray-400"
                >
                    <Building2 class="h-9 w-9" />
                </span>

                <span
                    class="absolute inset-0 flex items-center justify-center bg-black/45 opacity-0 transition group-hover:opacity-100"
                >
                    <Camera class="h-5 w-5 text-white" />
                </span>
            </button>

            <div class="min-w-0 flex-1 pt-3">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    Profile & cover photos
                </p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    The cover photo is the banner on your public provider page.
                    Your profile photo is the square logo on top of it. JPG, PNG
                    or WebP, up to 5MB.
                </p>
            </div>
        </div>

        <p
            v-if="uploadError"
            class="border-t border-red-100 bg-red-50 px-6 py-2.5 text-xs text-red-600 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300"
        >
            {{ uploadError }}
        </p>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import {
    Building2,
    Camera,
    Image as ImageIcon,
    LoaderCircle,
} from "lucide-vue-next";
import { usePermissions } from "~/composables/usePermission";
import { useToast } from "~/composables/useToast";
import { Modules } from "~/types/module";
import { getBranchImage } from "~/types/branch";
import { branchSettingService } from "~/api/branch-setting/BranchSettingService";

const props = defineProps<{
    uuid?: string;
    // The branch's current profile photo (URL, or a File that is still unsaved).
    profile?: File | string | null;
}>();

const { success, error } = useToast();
const { canCreate } = usePermissions();
const canEdit = computed(() => canCreate(Modules.BranchSettings));

const cover = ref<string | null>(null);
const loading = ref(false);
const uploading = ref(false);
const uploadError = ref("");
const fileInput = ref<HTMLInputElement | null>(null);

const profileSrc = computed(() => getBranchImage(props.profile));

const MAX_SIZE = 5 * 1024 * 1024;
const ALLOWED = ["image/png", "image/jpeg", "image/webp"];

const fetchCover = async () => {
    if (!props.uuid) return;

    loading.value = true;

    try {
        const res = await branchSettingService.list({
            action: "image",
            branch_uuid: props.uuid,
            type: "cover",
            per_page: 1,
        });

        cover.value = res?.data?.[0]?.image_url ?? null;
    } catch {
        cover.value = null;
    } finally {
        loading.value = false;
    }
};

const handleFile = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    // Let the same file be picked again later.
    input.value = "";

    if (!file || !props.uuid) return;

    uploadError.value = "";

    if (!ALLOWED.includes(file.type)) {
        uploadError.value = "Only JPG, PNG or WebP images are allowed.";
        return;
    }

    if (file.size > MAX_SIZE) {
        uploadError.value = "The image must be 5MB or smaller.";
        return;
    }

    uploading.value = true;

    try {
        const res = await branchSettingService.create({
            action: "image",
            branch_uuid: props.uuid,
            type: "cover",
            description: "",
            image: file,
        });

        cover.value = res?.data?.image_url ?? cover.value;
        success(res?.message ?? "Cover photo updated.");
    } catch (err: any) {
        uploadError.value =
            err?.response?.data?.message ?? err?.message ?? "Upload failed.";
        error(uploadError.value);
    } finally {
        uploading.value = false;
    }
};

// The profile photo is edited in the "Branch Image" field of the form below,
// which is saved together with the rest of the branch information.
const goToProfileField = () => {
    document
        .querySelector('[data-field="branch_image"]')
        ?.scrollIntoView({ behavior: "smooth", block: "center" });
};

watch(() => props.uuid, fetchCover);
onMounted(fetchCover);
</script>