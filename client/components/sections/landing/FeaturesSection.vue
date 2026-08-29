<template>
    <section class="relative overflow-hidden bg-slate-100 px-[6%] py-24 dark:bg-secondary">
        <!-- Blob -->
        <div
            class="absolute top-0 right-[-150px] h-[500px] w-[500px] rounded-full bg-cyan-200/40 blur-[100px]"
        ></div>

        <div class="relative z-10 flex gap-16 max-lg:flex-col">
            <div class="w-[280px] shrink-0">
                <p
                    class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-primary"
                >
                    FEATURES
                </p>

                <h2
                    class="mb-4 text-[clamp(2.8rem,5vw,5rem)] leading-none font-black tracking-[-0.03em] text-secondary dark:text-white"
                >
                    <span
                        class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent"
                    >
                        Multi-<br />
                        branch
                    </span>
                    <br />
                    oversight
                    <br />
                    made simple.
                </h2>

                <p class="mb-6 text-sm leading-7 text-muted dark:text-gray-400">
                    Coordinate care across all locations from one dashboard.
                </p>

                <button
                    class="group inline-flex items-center gap-2 font-semibold text-primary"
                >
                    Explore

                    <span
                        class="transition-transform duration-200 group-hover:translate-x-1"
                    >
                        →
                    </span>
                </button>
            </div>

            <!-- RIGHT -->
            <div class="grid flex-1 gap-4 lg:grid-cols-2">
                <!-- CARD 1 -->
                <div
                    class="overflow-hidden rounded-3xl border border-gray-200 bg-white dark:bg-secondary dark:border-white/10"
                >
                    <div class="p-6">
                        <div
                            class="mb-3 flex items-center gap-2 text-xs font-semibold tracking-wider text-muted uppercase dark:text-gray-400"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-primary"
                            ></span>

                            Schedule
                        </div>

                        <h4
                            class="mb-2 text-lg font-bold text-secondary dark:text-white"
                        >
                            <span class="text-primary"> Caregiver </span>
                            scheduling that works
                        </h4>

                        <p
                            class="text-sm leading-6 text-muted dark:text-gray-400"
                        >
                            Assign shifts and manage availability seamlessly.
                        </p>
                    </div>

                    <img
                        src="~/assets/images/Rectangle_12.png"
                        alt=""
                        class="h-[220px] w-full object-cover"
                    />
                </div>

                <!-- CARD 2 -->
                <div
                    class="overflow-hidden rounded-3xl border border-gray-200 bg-white dark:bg-secondary dark:border-white/10"
                >
                    <div class="p-6">
                        <div
                            class="mb-3 flex items-center gap-2 text-xs font-semibold tracking-wider text-muted uppercase dark:text-gray-400"
                        >
                            <span
                                class="h-2 w-2 rounded-full bg-primary"
                            ></span>

                            Billing
                        </div>

                        <h4
                            class="mb-2 text-lg font-bold text-secondary dark:text-white"
                        >
                            <span class="text-primary"> Subscriptions </span>
                            and billing plans
                        </h4>

                        <p
                            class="text-sm leading-6 text-muted dark:text-gray-400"
                        >
                            Handle payments and invoicing automatically.
                        </p>
                    </div>

                    <img
                        src="~/assets/images/Rectangle_13.png"
                        alt=""
                        class="h-[220px] w-full object-cover"
                    />
                </div>

                <!-- WIDE CARD -->
                <div
                    class="overflow-hidden rounded-3xl border border-gray-200 bg-white lg:col-span-2 dark:bg-secondary dark:border-white/10"
                >
                    <div class="flex h-full max-md:flex-col">
                        <div
                            class="flex w-[46%] flex-col justify-center p-8 max-md:w-full"
                        >
                            <div
                                class="mb-3 flex items-center gap-2 text-xs font-semibold tracking-wider text-muted uppercase dark:text-gray-400"
                            >
                                <span
                                    class="h-2 w-2 rounded-full bg-primary"
                                ></span>

                                Secure
                            </div>

                            <h4
                                class="mb-3 text-xl font-bold text-secondary dark:text-white"
                            >
                                <span class="text-primary">
                                    CCTV Solutions
                                </span>

                                coverage that captures
                            </h4>

                            <p
                                class="text-sm leading-7 text-muted dark:text-gray-400"
                            >
                                Integrated, seamless 24/7 security recording and
                                retrieval.
                            </p>
                        </div>

                        <img
                            src="~/assets/images/Rectangle_15.png"
                            alt=""
                            class="h-[280px] flex-1 object-cover"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
