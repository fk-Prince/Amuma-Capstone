<template>
    <div>
        <Qr :show="show" :token="token" @close="show = false" />
    </div>
</template>

<script lang="ts" setup>
import Qr from "./qr.vue";
import { onlineScheduleService } from "~/api/online-schedule/OnlineScheduleService";

const token = ref("");
const show = ref(false);

onMounted(async () => {
    try {
        const res = await onlineScheduleService.generateQr({});

        token.value = res.data?.token ?? res.token ?? res ?? "";

        if (token.value) {
            show.value = true;
        }
    } catch (err: any) {
        console.error(err);
    }
});
</script>
