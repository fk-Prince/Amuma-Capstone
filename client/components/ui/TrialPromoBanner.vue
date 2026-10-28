<template>
    <div
        class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary via-primary-600 to-emerald-500 p-[1.5px] shadow-lg shadow-primary/20"
    >
        <div
            class="relative flex flex-col items-center gap-3 rounded-[15px] bg-gradient-to-r from-primary to-emerald-500 px-5 py-4 text-white sm:flex-row sm:justify-between sm:px-6"
        >
            <div
                class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10 blur-2xl"
            />

            <div class="flex items-center gap-3 text-center sm:text-left">
                <span
                    class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/20 sm:flex"
                >
                    <Sparkles class="h-5 w-5" />
                </span>

                <div>
                    <p class="text-base font-extrabold tracking-tight sm:text-lg">
                        Try it free for 2 weeks.
                    </p>
                    <p class="text-xs text-white/85 sm:text-sm">
                        Add a card to start. Nothing is charged until your
                        trial ends.
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 flex-wrap justify-center gap-2">
                <span
                    v-for="perk in perks"
                    :key="perk"
                    class="inline-flex items-center gap-1 rounded-full bg-white/20 px-2.5 py-1 text-[11px] font-semibold"
                >
                    <Check class="h-3 w-3" />
                    {{ perk }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Check, Sparkles } from "lucide-vue-next";

const perks = ["₱0 due today", "Full features", "Cancel anytime"];
</script>
