<template>
    <div class="space-y-5">
        <section
            v-if="agency"
            class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div>
                <h3
                    class="text-base font-semibold text-slate-900 dark:text-white"
                >
                    Agency Information
                </h3>
                <p class="text-sm text-slate-500 dark:text-gray-400">
                    Registered agency details.
                </p>
            </div>

            <div class="flex flex-col gap-5 sm:flex-row">
                <ImageBox :src="agencyImage" />

                <div class="flex-1 space-y-3">
                    <SummaryRow label="Agency Name" :value="agency.name" />
                    <SummaryRow label="Email Address" :value="agency.email" />
                    <SummaryRow
                        label="Description"
                        expandable
                        :value="agency.description"
                    />
                    <SummaryRow
                        label="Address"
                        :value="addressOf(agency.location)"
                    />
                    <DocumentsRow :documents="agencyDocuments" />
                </div>
            </div>
        </section>

        <section
            class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-secondary"
        >
            <div>
                <h3
                    class="text-base font-semibold text-slate-900 dark:text-white"
                >
                    Branch Information
                </h3>
                <p class="text-sm text-slate-500 dark:text-gray-400">
                    Branch details and location.
                </p>
            </div>

            <div class="flex flex-col gap-5 sm:flex-row">
                <ImageBox :src="branchImage" />

                <div class="flex-1 space-y-3">
                    <SummaryRow label="Branch Name" :value="branch.name" />
                    <SummaryRow label="Email Address" :value="branch.email" />
                    <SummaryRow
                        label="Contact Number"
                        :value="branch.contact_number"
                    />
                    <SummaryRow label="TIN" :value="branch.tin" />
                    <SummaryRow
                        label="Description"
                        expandable
                        :value="branch.description"
                    />
                    <SummaryRow
                        label="Address"
                        :value="addressOf(branch.location)"
                    />
                    <DocumentsRow :documents="branchDocuments" />
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, type PropType } from "vue";
import SummaryRow from "~/components/ui/SummaryRow.vue";
import DocumentLink from "~/components/ui/DocumentLink.vue";
import type { Agency } from "~/types/agency";
import type { Branch } from "~/types/branch";

const props = defineProps<{
    agency: Agency | null;
    branch: Branch;
}>();

interface ReviewDocument {
    label: string;
    url: string;
    pdf: boolean;
}

const fileUrls = new WeakMap<File, string>();

const toUrl = (value: unknown): string | null => {
    if (value instanceof File) {
        if (!fileUrls.has(value)) fileUrls.set(value, URL.createObjectURL(value));

        return fileUrls.get(value)!;
    }

    return typeof value === "string" && value ? value : null;
};

const toDocument = (label: string, value: unknown): ReviewDocument | null => {
    const url = toUrl(value);

    if (!url) return null;

    return {
        label,
        url,
        pdf:
            value instanceof File
                ? value.type === "application/pdf"
                : /\.pdf($|\?)/i.test(url),
    };
};

const agencyImage = computed(() => toUrl(props.agency?.image));
const branchImage = computed(() => toUrl(props.branch.image));

const agencyDocuments = computed(() =>
    [
        toDocument("ID (Front)", props.agency?.id_front),
        toDocument("ID (Back)", props.agency?.id_back),
        toDocument("Agency Document", props.agency?.document),
    ].filter((doc): doc is ReviewDocument => doc !== null),
);

const branchDocuments = computed(() =>
    [toDocument("Branch Document", props.branch.document)].filter(
        (doc): doc is ReviewDocument => doc !== null,
    ),
);

const addressOf = (location?: {
    full_address?: string | null;
    street?: string | null;
    city?: string | null;
    province?: string | null;
    country?: string | null;
}) =>
    location?.full_address ||
    [location?.street, location?.city, location?.province, location?.country]
        .filter(Boolean)
        .join(", ");

const ImageBox = defineComponent({
    props: { src: { type: String as PropType<string | null>, default: null } },
    setup: (boxProps) => () =>
        h(
            "div",
            {
                class: "h-28 w-28 shrink-0 overflow-hidden rounded-2xl bg-slate-100 dark:bg-white/10",
            },
            boxProps.src
                ? h(
                      "a",
                      {
                          href: boxProps.src,
                          target: "_blank",
                          rel: "noopener noreferrer",
                      },
                      h("img", {
                          src: boxProps.src,
                          class: "h-full w-full object-cover",
                      }),
                  )
                : h(
                      "div",
                      {
                          class: "flex h-full items-center justify-center text-xs text-slate-400 dark:text-gray-500",
                      },
                      "No Image",
                  ),
        ),
});

const DocumentsRow = defineComponent({
    props: {
        documents: {
            type: Array as PropType<ReviewDocument[]>,
            required: true,
        },
    },
    setup: (rowProps) => () =>
        h(
            "div",
            {
                class: "flex flex-col items-start gap-1 border-t border-slate-100 py-2.5 sm:flex-row sm:justify-between sm:gap-4 dark:border-white/10",
            },
            [
                h(
                    "span",
                    {
                        class: "shrink-0 pt-0.5 text-[13px] font-semibold tracking-wide text-slate-500 dark:text-gray-400",
                    },
                    "Documents",
                ),
                rowProps.documents.length
                    ? h(
                          "div",
                          { class: "flex flex-wrap gap-2 sm:justify-end" },
                          rowProps.documents.map((doc) =>
                              h(DocumentLink, {
                                  key: doc.label,
                                  url: doc.url,
                                  label: doc.label,
                                  pdf: doc.pdf,
                              }),
                          ),
                      )
                    : h(
                          "span",
                          { class: "text-sm text-slate-400 dark:text-gray-500" },
                          "Not provided",
                      ),
            ],
        ),
});
</script>
