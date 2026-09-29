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
    navVariant: 5,
    footer: false,
});

useHead({
    title: "Forgot password - AMUMA",
});
</script>
