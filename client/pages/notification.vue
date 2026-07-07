<script setup lang="ts">
useHead({
    title: "AMUMA",
});

const { $echo } = useNuxtApp();

const uuid = "019ef9a7-9851-7189-8b57-8f71b35e0128";

const notification = ref<string | null>(null);

onMounted(async () => {
    $echo
        .private(`Notification.${uuid}`)
        .listen(".NotificationEvent", (e: any) => {
            console.log(
                "YAWA LFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF NOTIFICATION:",
                e,
            );
            alert(e);
            notification.value = e.message;
        });
});
</script>

<template>
    <div class="min-h-screen w-full overflow-x-hidden">YWAW</div>
</template>
