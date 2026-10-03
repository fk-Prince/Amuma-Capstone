<template>
    <ClientOnly>
        <AuthCardShell>
            <ResetPasswordForm />
        </AuthCardShell>
    </ClientOnly>
</template>

<script setup lang="ts">
import AuthCardShell from "~/components/sections/auth/AuthCardShell.vue";
import ResetPasswordForm from "~/components/forms/ResetPasswordForm.vue";

definePageMeta({
    layout: "auth",
});

useHead({
    title: "Reset password - AMUMA",
});
</script>
