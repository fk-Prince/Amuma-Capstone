<template>
    <ClientOnly>
        <AuthCardShell>
            <ForgotPasswordForm />
        </AuthCardShell>
    </ClientOnly>
</template>

<script setup lang="ts">
import AuthCardShell from "~/components/sections/auth/AuthCardShell.vue";
import ForgotPasswordForm from "~/components/forms/ForgotPasswordForm.vue";

definePageMeta({
    middleware: "auth-client",
    layout: "auth",
});

useHead({
    title: "Forgot password - AMUMA",
});
</script>
