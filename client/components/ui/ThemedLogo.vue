<script setup lang="ts">
import logo from "~/assets/logo/logo.png";

defineOptions({ name: "ThemedLogo" });
</script>

<template>
    <span class="themed-logo relative inline-block" role="img" aria-label="AMUMA">
        <img :src="logo" alt="" class="themed-logo__base" draggable="false" />
        <img
            :src="logo"
            alt=""
            aria-hidden="true"
            class="themed-logo__accent"
            draggable="false"
        />
    </span>
</template>

<style scoped>
.themed-logo__base,
.themed-logo__accent {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.themed-logo__base {
    filter: hue-rotate(calc(var(--logo-primary-rot, 0) * 1deg))
        saturate(calc(var(--logo-primary-sat, 100) * 1%));
}

.themed-logo__accent {
    position: absolute;
    inset: 0;
    filter: hue-rotate(calc(var(--logo-accent-rot, 0) * 1deg))
        saturate(calc(var(--logo-accent-sat, 100) * 1%));
    -webkit-mask-image: url("data:image/png;base64,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");
    mask-image: url("data:image/png;base64,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");
    -webkit-mask-size: 100% 100%;
    mask-size: 100% 100%;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
}
</style>