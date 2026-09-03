<script setup lang="ts">
import { onBeforeUnmount, onMounted } from "vue";
import { X } from "lucide-vue-next";
import BaseButton from "./BaseButton.vue";

const emit = defineEmits<{
    close: [];
    accept: [];
}>();

withDefaults(
    defineProps<{
        showAccept?: boolean;
    }>(),
    { showAccept: false },
);

const LAST_UPDATED = "September 2, 2026";

function onKeydown(event: KeyboardEvent) {
    if (event.key === "Escape") emit("close");
}

onMounted(() => {
    document.addEventListener("keydown", onKeydown);
    document.body.style.overflow = "hidden";
});

onBeforeUnmount(() => {
    document.removeEventListener("keydown", onKeydown);
    document.body.style.overflow = "";
});
</script>

<template>
    <Teleport to="body">
        <div
            class="fixed inset-0 z-[60] flex items-center justify-center bg-secondary/50 px-4 py-6 backdrop-blur-sm dark:bg-white/10"
            @click.self="emit('close')"
        >
            <div
                class="flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5 dark:bg-secondary dark:ring-white/10"
                role="dialog"
                aria-modal="true"
                aria-labelledby="terms-title"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-6 py-5 dark:border-white/10"
                >
                    <div class="min-w-0">
                        <h2
                            id="terms-title"
                            class="text-lg font-bold text-slate-900 dark:text-white"
                        >
                            Terms and Conditions
                        </h2>
                        <p
                            class="mt-0.5 text-xs text-slate-500 dark:text-gray-400"
                        >
                            AMUMA An Online Portal for Homecare Management
                            System · Last updated
                            {{ LAST_UPDATED }}
                        </p>
                    </div>

                    <button
                        type="button"
                        aria-label="Close terms and conditions"
                        class="shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                        @click="emit('close')"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-5 text-sm leading-6 text-slate-600 dark:text-gray-300"
                >
                    <section>
                        <h3
                            class="mb-1.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            1. About AMUMA
                        </h3>
                        <p>
                            AMUMA is a care management platform for caregiving
                            agencies and the families they serve. Agencies use
                            it to run branches, admit and monitor patients,
                            schedule staff, record medications and vitals, and
                            bill for care. Families use it to search verified
                            providers, book homecare visits or facility stays,
                            follow a loved one's care, and settle payments
                            online. By creating an account you agree to these
                            terms.
                        </p>
                    </section>

                    <section>
                        <h3
                            class="mb-1.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            2. Your account
                        </h3>
                        <p>
                            You must give accurate details when registering and
                            confirm your email through the one-time password we
                            send you. You are responsible for everything done
                            through your account, so keep your password to
                            yourself and tell us at once if you believe someone
                            else has access. Accounts are personal: staff
                            accounts belong to the individual caregiver or
                            administrator, not to the branch, and may not be
                            shared.
                        </p>
                    </section>

                    <section>
                        <h3
                            class="mb-1.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            3. Roles and access
                        </h3>
                        <p>
                            What you can see and do depends on the role granted
                            to you. Agency owners and branch administrators
                            manage branches, employees, and settings; employees
                            receive only the module permissions their branch
                            assigns them; family members and guardians see the
                            records of the patients linked to them. You agree
                            not to attempt to reach data outside the permissions
                            you have been given.
                        </p>
                    </section>

                    <section>
                        <h3
                            class="mb-1.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            4. Patient information
                        </h3>
                        <p>
                            Health information entered into AMUMA — admissions,
                            medication administration records, vitals, care
                            notes, and documents — is confidential. Agencies act
                            as the custodians of their patients' records and are
                            responsible for collecting them lawfully and for
                            obtaining consent where the Data Privacy Act of 2012
                            (Republic Act No. 10173) requires it. You may access
                            patient information only to deliver or arrange care,
                            and never for any other purpose.
                        </p>
                    </section>

                    <section>
                        <h3
                            class="mb-1.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            5. Bookings and care delivery
                        </h3>
                        <p>
                            AMUMA connects families with caregiving agencies; it
                            does not itself provide medical or nursing care. A
                            booking is a request until the branch confirms it,
                            and the agency that accepts it is solely responsible
                            for the care given, for the conduct and credentials
                            of its staff, and for its own licensing. AMUMA is
                            not a substitute for emergency services — in an
                            emergency, contact emergency responders directly.
                        </p>
                    </section>

                    <section>
                        <h3
                            class="mb-1.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            6. Subscriptions and payments
                        </h3>
                        <p>
                            Agencies subscribe to a plan — Homecare Services,
                            In-house Facility, or Hybrid — billed monthly or
                            yearly. One subscription covers up to five branches
                            under the same agency; additional branches beyond
                            that require another subscription. Every branch,
                            paid or included, is reviewed by AMUMA before it
                            goes live, and a branch may be rejected. Renewing
                            early adds time on top of what remains. If you
                            upgrade mid-term you may choose to start the new
                            plan immediately, giving up the days left on the
                            current one, or to have it begin when the current
                            period ends.
                        </p>
                        <p class="mt-2">
                            Payments are processed by our payment provider; we
                            do not store your full card number. Fees already
                            paid are non-refundable except where these terms or
                            the law say otherwise, and a rejected branch's
                            payment is refunded to the original payment method.
                        </p>
                    </section>

                    <section>
                        <h3
                            class="mb-1.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            7. QR check-in codes and CCTV access
                        </h3>
                        <p>
                            A QR code generated for a homecare visit proves that
                            a named caregiver was physically present at the
                            patient's home at a given time. It is issued to one
                            caregiver for one visit and must be scanned by the
                            person named on it. Sharing, forwarding,
                            photographing, or scanning someone else's check-in
                            or check-out code is a serious violation of these
                            terms: it falsifies a care record, misstates the
                            hours an agency bills, and can make it appear that a
                            patient received care they never received.
                        </p>
                        <p class="mt-2">
                            CCTV access in VIP rooms is granted only to the
                            patient's authorised family members and to branch
                            staff whose duties require it, and only for the
                            duration of that patient's stay. You may not record,
                            screenshot, download, re-stream, or otherwise share
                            any footage or still image with anyone outside that
                            group, and you may not pass your account or viewing
                            link to another person. Cameras may capture other
                            patients, visitors, and staff, so sharing footage
                            exposes people who never consented to it.
                        </p>
                        <p class="mt-2">
                            We may suspend or permanently close any account
                            involved in either practice, void the affected visit
                            records, and notify the agency and the patient's
                            family. Depending on what was shared, these acts may
                            also carry liability under the Data Privacy Act of
                            2012 and the Anti-Photo and Video Voyeurism Act of
                            2009 (Republic Act No. 9995), and we will cooperate
                            with lawful investigations.
                        </p>
                    </section>

                    <section>
                        <h3
                            class="mb-1.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            8. Acceptable use
                        </h3>
                        <p>
                            Do not misrepresent an agency or a caregiver, upload
                            false or misleading records, post reviews you did
                            not earn the right to write, disrupt or probe the
                            platform's security, or use AMUMA to break any law.
                            We may suspend or close accounts that do.
                        </p>
                    </section>

                    <section>
                        <h3
                            class="mb-1.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            9. Availability and liability
                        </h3>
                        <p>
                            We work to keep AMUMA available and accurate, but
                            the platform is provided as is, without warranty
                            that it will be uninterrupted or error free. To the
                            extent the law allows, AMUMA is not liable for
                            indirect or consequential loss, nor for the acts or
                            omissions of the agencies and caregivers who use it.
                        </p>
                    </section>

                    <section>
                        <h3
                            class="mb-1.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            10. Changes and contact
                        </h3>
                        <p>
                            We may update these terms as the platform grows. If
                            a change is significant we will let you know in the
                            app, and continuing to use AMUMA afterwards means
                            you accept the revised terms. Questions about these
                            terms or about your data can be sent to our support
                            team from the Company page.
                        </p>
                    </section>
                </div>

                <div
                    class="flex shrink-0 justify-end gap-2 border-t border-slate-100 px-6 py-4 dark:border-white/10"
                >
                    <BaseButton
                        v-if="showAccept"
                        variant="secondary"
                        size="md"
                        @click="emit('close')"
                    >
                        Close
                    </BaseButton>

                    <BaseButton
                        variant="primary"
                        size="md"
                        @click="showAccept ? emit('accept') : emit('close')"
                    >
                        {{ showAccept ? "I Agree" : "Close" }}
                    </BaseButton>
                </div>
            </div>
        </div>
    </Teleport>
</template>
