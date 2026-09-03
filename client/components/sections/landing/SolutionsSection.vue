<template>
    <section
        class="relative overflow-hidden bg-white py-24 text-center dark:bg-secondary"
    >
        <div
            class="pointer-events-none absolute top-1/2 left-1/2 h-[400px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-200 opacity-30 blur-[80px]"
        ></div>

        <div class="relative z-10 mx-auto w-[94%] max-w-[1600px] px-10">
            <p
                class="mb-3 text-xs font-bold uppercase tracking-[0.14em] text-primary"
            >
                ONE PLATFORM
            </p>

            <h2
                class="mx-auto mb-4 max-w-4xl text-4xl font-black text-secondary md:text-5xl dark:text-white"
            >
                Modular Solutions for
                <span
                    class="bg-gradient-to-br from-primary to-blue-700 bg-clip-text text-transparent"
                >
                    Every Care
                </span>
                Model
            </h2>

            <p class="mx-auto mb-14 max-w-2xl text-sm leading-7 text-muted dark:text-gray-400">
                Choose the modules your agency needs. Run home care, facility
                care, or both — AMUMA adapts to your exact workflow.
            </p>

            <div class="grid gap-6 lg:grid-cols-3">
                <div
                    class="rounded-[20px] border border-gray-200 bg-white p-8 text-left transition-all duration-300 hover:-translate-y-1.5 hover:border-primary/25 hover:shadow-[0_20px_40px_rgba(49,130,237,0.08),0_4px_12px_rgba(15,23,42,0.04)] dark:bg-secondary dark:border-white/10"
                >
                    <div
                        class="mb-6 flex h-14 w-14 items-center justify-center rounded-[14px] bg-light text-primary dark:bg-white/5"
                    >
                        <svg
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"
                            />
                            <path d="M9 21V12h6v9" />
                        </svg>
                    </div>

                    <h3 class="mb-3 text-lg font-bold text-secondary dark:text-white">
                        Home Care Services
                    </h3>

                    <p class="mb-6 text-sm leading-7 text-muted dark:text-gray-400">
                        Professional caregivers visit patients at home for
                        personalized care, medication management, and daily
                        living assistance.
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

                <div
                    class="rounded-[20px] border border-gray-200 bg-white p-8 text-left transition-all duration-300 hover:-translate-y-1.5 hover:border-primary/25 hover:shadow-[0_20px_40px_rgba(49,130,237,0.08),0_4px_12px_rgba(15,23,42,0.04)] dark:bg-secondary dark:border-white/10"
                >
                    <div
                        class="mb-6 flex h-14 w-14 items-center justify-center rounded-[14px] bg-light text-primary dark:bg-white/5"
                    >
                        <svg
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <rect x="3" y="3" width="18" height="18" rx="2" />
                            <path d="M9 3v18M3 9h6M3 15h6" />
                        </svg>
                    </div>

                    <h3 class="mb-3 text-lg font-bold text-secondary dark:text-white">
                        In-House Facility
                    </h3>

                    <p class="mb-6 text-sm leading-7 text-muted dark:text-gray-400">
                        Complete elderly care facility management with room
                        assignments, 24/7 monitoring, and comprehensive patient
                        care.
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

                <div
                    class="rounded-[20px] border border-gray-200 bg-white p-8 text-left transition-all duration-300 hover:-translate-y-1.5 hover:border-primary/25 hover:shadow-[0_20px_40px_rgba(49,130,237,0.08),0_4px_12px_rgba(15,23,42,0.04)] dark:bg-secondary dark:border-white/10"
                >
                    <div
                        class="mb-6 flex h-14 w-14 items-center justify-center rounded-[14px] bg-light text-primary dark:bg-white/5"
                    >
                        <svg
                            width="28"
                            height="28"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
                            />
                        </svg>
                    </div>

                    <h3 class="mb-3 text-lg font-bold text-secondary dark:text-white">
                        Hybrid Solution
                    </h3>

                    <p class="mb-6 text-sm leading-7 text-muted dark:text-gray-400">
                        Seamlessly manage both home care services and facility
                        operations from a single integrated platform.
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
            </div>
        </div>
    </section>
</template>
