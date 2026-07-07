<template>
    <section class="relative overflow-visible bg-slate-50 pt-36 pb-40">
        <div
            class="pointer-events-none absolute -top-[120px] -right-[80px] h-[520px] w-[520px] rounded-full bg-blue-300 opacity-35 blur-[70px]"
        ></div>

        <div
            class="pointer-events-none absolute -left-[60px] bottom-0 h-[320px] w-[320px] rounded-full bg-indigo-200 opacity-35 blur-[70px]"
        ></div>

        <div
            class="absolute top-36 left-[7%] h-[10px] w-[10px] animate-pulse rounded-full bg-primary shadow-[0_0_25px_rgba(49,130,237,0.8)]"
        >
            <div
                class="absolute -inset-3 rounded-full bg-primary/25 blur-[9px]"
            ></div>
        </div>

        <div
            class="mx-auto flex w-[94%] max-w-[1600px] items-center gap-16 px-10 max-lg:flex-col"
        >
            <div class="max-w-[600px] flex-[0_0_50%]">
                <h1
                    class="mt-8 mb-4 max-w-[540px] text-[clamp(3rem,3.8vw,3.8rem)] font-black leading-[0.85] tracking-[-0.045em] text-secondary"
                >
                    All-in-One
                    <span
                        class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent"
                    >
                        Caregiving
                    </span>
                    <br />
                    Management System
                </h1>

                <p class="max-w-[580px] text-sm leading-8 text-muted">
                    Streamline home-care bookings, facility management, and
                    patient monitoring — all from one unified platform built for
                    modern care agencies.
                </p>

                <span
                    class="mt-4 mb-7 block h-[3px] w-20 rounded bg-primary"
                ></span>

                <div class="mb-6 flex flex-wrap gap-3">
                    <button
                        class="flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-blue-700"
                    >
                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.35-4.35" />
                        </svg>

                        Search Provider
                    </button>

                    <button
                        class="rounded-xl border border-primary px-6 py-3 text-sm font-bold text-primary transition hover:bg-primary hover:text-white"
                    >
                        Scale Your Agency Today
                    </button>
                </div>

                <div class="mb-8 flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2 text-xs text-muted">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-md"
                        >
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="#3182ED"
                                stroke-width="2"
                            >
                                <polyline
                                    points="22 12 18 12 15 21 9 3 6 12 2 12"
                                />
                            </svg>
                        </span>

                        eMAR & Vitals
                    </div>

                    <div class="flex items-center gap-2 text-xs text-muted">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-md"
                        >
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="#3182ED"
                                stroke-width="2"
                            >
                                <rect x="3" y="3" width="7" height="7" />
                                <rect x="14" y="3" width="7" height="7" />
                                <rect x="14" y="14" width="7" height="7" />
                                <rect x="3" y="14" width="7" height="7" />
                            </svg>
                        </span>

                        QR Check-in
                    </div>

                    <div class="flex items-center gap-2 text-xs text-muted">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-md"
                        >
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="#3182ED"
                                stroke-width="2"
                            >
                                <path
                                    d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
                                />
                            </svg>
                        </span>

                        HIPAA Compliant
                    </div>

                    <div class="flex items-center gap-2 text-xs text-muted">
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-md"
                        >
                            <svg
                                width="13"
                                height="13"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="#3182ED"
                                stroke-width="2"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <polyline points="12 6 12 12 16 14" />
                            </svg>
                        </span>

                        24/7 Support
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex">
                        <div
                            class="ml-0 h-9 w-9 rounded-full border-2 border-white bg-danger"
                        ></div>

                        <div
                            class="-ml-3 h-9 w-9 rounded-full border-2 border-white bg-primary"
                        ></div>

                        <div
                            class="-ml-3 h-9 w-9 rounded-full border-2 border-white bg-accent"
                        ></div>

                        <div
                            class="-ml-3 h-9 w-9 rounded-full border-2 border-white bg-purple-400"
                        ></div>
                    </div>

                    <div>
                        <div class="text-yellow-400">★★★★★</div>

                        <p class="text-sm text-muted-dark">
                            <strong>4.9/5</strong> from 128+ reviews
                        </p>

                        <p class="text-xs text-muted">
                            Loved by caregivers and agencies
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-[-120px] flex flex-1 justify-end max-lg:mt-0">
                <div class="relative w-full max-w-[1050px]">
                    <img
                        :src="dashboardImg"
                        alt="AMUMA Dashboard"
                        class="block w-full rounded-3xl shadow-[0_25px_80px_rgba(49,130,237,0.15)] [transform:perspective(1900px)_rotateY(-15deg)_rotateX(5deg)]"
                    />

                    <div
                        class="absolute right-0 left-0 -bottom-28 flex items-end gap-3 px-2 max-md:static max-md:mt-4 max-md:flex-col"
                    >
                        <div
                            class="relative flex h-[220px] w-[190px] flex-col justify-between overflow-hidden rounded-3xl bg-gradient-to-br from-primary to-blue-600 p-5 shadow-[0_20px_50px_rgba(37,99,235,0.3)] animate-floatA"
                        >
                            <div
                                class="absolute -top-2.5 -right-10 h-[140px] w-[140px] rounded-full bg-white/10"
                            ></div>

                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-md"
                            >
                                <svg
                                    width="18"
                                    height="18"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="white"
                                    stroke-width="2"
                                >
                                    <path
                                        d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"
                                    />
                                </svg>
                            </div>

                            <div>
                                <h3 class="text-lg font-extrabold text-white">
                                    Amuma
                                </h3>

                                <p class="text-xs text-white/70">
                                    To nurture, to care, to love
                                </p>
                            </div>
                        </div>

                        <div
                            class="w-[240px] rounded-2xl border border-slate-100 bg-white p-4 shadow-[0_12px_30px_rgba(15,23,42,0.1)] animate-floatB"
                        >
                            <div class="mb-3 flex items-center gap-3">
                                <div
                                    class="relative h-11 w-11 rounded-full bg-gradient-to-br from-yellow-500 to-amber-700"
                                >
                                    <span
                                        class="absolute right-0 bottom-0 h-3 w-3 rounded-full border-2 border-white bg-accent"
                                    ></span>
                                </div>

                                <div>
                                    <h4
                                        class="text-sm font-bold text-secondary"
                                    >
                                        Bunny Wawa
                                    </h4>

                                    <p class="text-xs text-muted">Caregiver</p>

                                    <span
                                        class="mt-1 inline-flex rounded-full bg-green-50 px-2 py-0.5 text-[11px] font-bold text-green-600"
                                    >
                                        Available
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3 text-xs text-muted-dark">
                                ⭐ 4.9 (128 reviews)
                            </div>

                            <div class="flex gap-2">
                                <button
                                    class="flex-1 rounded-lg bg-primary py-2 text-xs font-bold text-white"
                                >
                                    View Agency
                                </button>

                                <button
                                    class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-primary"
                                >
                                    ♥
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import dashboardImg from "~/assets/images/dashboard-preview.jpg";
</script>
