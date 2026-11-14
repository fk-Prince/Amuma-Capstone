<template>
    <article class="print-doc">
        <header class="print-doc-header">
            <div class="print-doc-branch">
                <img
                    v-if="branch.image"
                    :src="branch.image"
                    :alt="branch.name ?? ''"
                    class="print-doc-logo"
                />
                <div v-else class="print-doc-logo print-doc-initials">
                    {{ initials(branch.name ?? "Amuma Care") }}
                </div>

                <div class="print-doc-branch-info">
                    <p class="print-doc-brand">
                        {{ branch.name || "Amuma Care" }}
                    </p>
                    <p v-if="branch.agency_name" class="print-doc-agency">
                        {{ branch.agency_name }}
                    </p>
                    <p v-if="branch.address" class="print-doc-branch-line">
                        <MapPin class="print-doc-icon" />
                        <span>{{ branch.address }}</span>
                    </p>
                    <p
                        v-if="branch.contact_number || branch.email"
                        class="print-doc-branch-line"
                    >
                        <template v-if="branch.contact_number">
                            <Phone class="print-doc-icon" />
                            <span>{{ formatPhone(branch.contact_number) }}</span>
                        </template>
                        <template v-if="branch.email">
                            <Mail
                                class="print-doc-icon"
                                :class="{ 'print-doc-icon-gap': branch.contact_number }"
                            />
                            <span>{{ branch.email }}</span>
                        </template>
                    </p>
                    <p v-if="branch.tin" class="print-doc-branch-line">
                        <Hash class="print-doc-icon" />
                        <span>TIN {{ branch.tin }}</span>
                    </p>
                </div>
            </div>

            <div class="print-doc-heading">
                <div class="print-doc-title-row">
                    <component :is="icon" class="print-doc-icon print-doc-title-icon" />
                    <h1 class="print-doc-title">{{ title }}</h1>
                </div>
                <p class="print-doc-type">{{ docType }}</p>
                <p v-if="reference" class="print-doc-ref">{{ reference }}</p>
            </div>
        </header>

        <dl v-if="visibleLines.length" class="print-doc-lines">
            <template v-for="line in visibleLines" :key="line.label">
                <dt>{{ line.label }}</dt>
                <dd :class="{ 'print-doc-strong': line.strong }">
                    {{ line.value }}
                </dd>
            </template>
        </dl>

        <slot />

        <footer class="print-doc-footer">
            <span>{{ footerLabel }}</span>
            <span class="print-doc-footer-right">
                <ShieldCheck class="print-doc-icon" />
                Generated {{ generatedAt }} · Confidential
            </span>
        </footer>
    </article>
</template>

<script setup lang="ts">
import { computed, type Component } from "vue";
import { Hash, Mail, MapPin, Phone, ShieldCheck } from "lucide-vue-next";
import { formatPhone } from "~/utils/phone";
import { initials } from "~/utils/user";
import type { PrintBranch, PrintLine } from "~/types/print";

const props = defineProps<{
    branch: PrintBranch;
    title: string;
    icon: Component;
    docType: string;
    reference?: string | null;
    lines?: PrintLine[];
    footerLabel: string;
    generatedAt: string;
}>();

const visibleLines = computed(() =>
    (props.lines ?? []).filter((line) => !!line.value),
);
</script>

<style>
.print-doc {
    display: flex;
    flex-direction: column;
    background: #ffffff;
    color: #000000;
    font-family: ui-sans-serif, system-ui, sans-serif;
    font-size: 8pt;
    line-height: 1.45;
}

.print-doc * {
    color: #000000;
}

.print-doc + .print-doc {
    margin-top: 10mm;
    break-before: page;
    page-break-before: always;
}

.print-doc-icon {
    width: 3mm;
    height: 3mm;
    flex-shrink: 0;
}

.print-doc-icon-gap {
    margin-left: 2.5mm;
}

.print-doc-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-start;
    gap: 4mm 10mm;
    padding-bottom: 4mm;
    border-bottom: 1pt solid #000000;
}

.print-doc-branch {
    display: flex;
    align-items: flex-start;
    gap: 3.5mm;
    min-width: 0;
}

.print-doc-logo {
    width: 12mm;
    height: 12mm;
    flex-shrink: 0;
    object-fit: cover;
}

.print-doc-initials {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 0.75pt solid #000000;
    font-size: 9pt;
    font-weight: 700;
}

.print-doc-branch-info {
    min-width: 0;
}

.print-doc-brand {
    margin: 0;
    font-size: 11pt;
    font-weight: 700;
}

.print-doc-agency {
    margin: 0.4mm 0 1mm;
    font-size: 6.5pt;
    font-weight: 600;
    letter-spacing: 0.14em;
    text-transform: uppercase;
}

.print-doc-branch-line {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1.2mm;
    margin: 0.7mm 0 0;
    font-size: 7pt;
}

.print-doc-heading {
    margin-left: auto;
    text-align: right;
}

.print-doc-title-row {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 1.8mm;
}

.print-doc-title-icon {
    width: 3.8mm;
    height: 3.8mm;
}

.print-doc-title {
    margin: 0;
    font-size: 12pt;
    font-weight: 700;
}

.print-doc-type {
    margin: 1.5mm 0 0;
    font-size: 6.5pt;
    font-weight: 600;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.print-doc-ref {
    margin: 0.5mm 0 0;
    font-size: 7pt;
}

.print-doc-lines {
    display: grid;
    grid-template-columns: 26mm minmax(0, 1fr) 26mm minmax(0, 1fr);
    column-gap: 3mm;
    row-gap: 1.8mm;
    margin: 5mm 0 1mm;
    font-size: 8pt;
}

.print-doc-lines dt {
    font-weight: 600;
}

.print-doc-lines dd {
    margin: 0;
    overflow-wrap: anywhere;
}

.print-doc-strong {
    font-weight: 600;
}

.print-doc-subhead {
    display: flex;
    align-items: center;
    gap: 1.8mm;
    margin: 5mm 0 2mm;
    font-size: 8.5pt;
    font-weight: 700;
    break-after: avoid;
    page-break-after: avoid;
}

.print-doc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 7.5pt;
}

.print-doc-table thead {
    display: table-header-group;
}

.print-doc-table tr {
    break-inside: avoid;
    page-break-inside: avoid;
}

.print-doc-table th,
.print-doc-table td {
    padding: 1.6mm 2mm 1.6mm 0;
    text-align: left;
    vertical-align: top;
}

.print-doc-table th {
    font-size: 6.5pt;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    white-space: nowrap;
    border-bottom: 0.75pt solid #000000;
}

.print-doc-table .print-doc-right {
    padding-right: 0;
    text-align: right;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.print-doc-table .print-doc-key {
    width: 38mm;
    font-weight: 600;
}

.print-doc-table .print-doc-total td {
    border-top: 0.75pt solid #000000;
    padding-top: 2mm;
    font-size: 8.5pt;
    font-weight: 700;
}

.print-doc-mono {
    font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
    font-weight: 600;
}

.print-doc-subtle {
    display: block;
    margin-top: 0.5mm;
    font-size: 6.5pt;
}

.print-doc-note {
    margin: 3.5mm 0 0;
    font-size: 7pt;
    font-style: italic;
}

.print-doc-empty {
    margin: 0;
    padding: 3mm 0;
    font-size: 7.5pt;
    font-style: italic;
}

.print-doc-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 2mm 6mm;
    margin-top: 6mm;
    padding-top: 2mm;
    border-top: 0.5pt solid #000000;
    font-size: 6.5pt;
}

.print-doc-footer-right {
    display: inline-flex;
    align-items: center;
    gap: 1mm;
}

.print-doc-footer-right .print-doc-icon {
    width: 2.6mm;
    height: 2.6mm;
}

@media screen and (max-width: 640px) {
    .print-doc-lines {
        grid-template-columns: 24mm minmax(0, 1fr);
    }

    .print-doc-heading {
        margin-left: 0;
        text-align: left;
    }

    .print-doc-title-row {
        justify-content: flex-start;
    }
}
</style>
