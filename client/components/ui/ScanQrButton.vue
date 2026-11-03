<script setup lang="ts">
import { ref } from "vue";
import { ScanLine } from "lucide-vue-next";
import ActionButton from "./ActionButton.vue";
import QrScanner from "./QrScanner.vue";

const emit = defineEmits<{ scanned: [] }>();

const showScanner = ref(false);
</script>

<template>
    <div class="flex justify-end">
        <ActionButton variant="primary" @click="showScanner = true">
            <span class="inline-flex items-center gap-2">
                <ScanLine class="h-4 w-4" />
                Scan QR
            </span>
        </ActionButton>

        <QrScanner
            v-if="showScanner"
            @close="showScanner = false"
            @scanned="emit('scanned')"
        />
    </div>
</template>
