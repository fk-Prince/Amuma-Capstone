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
    navVariant: 5,
    footer: false,
});

useHead({
    title: "Reset password - AMUMA",
});
</script>
