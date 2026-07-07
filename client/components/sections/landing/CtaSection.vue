<template>
    <section
        class="relative overflow-hidden bg-gradient-to-br from-blue-100 to-light px-[6%] py-16"
    >
        <!-- Blob -->
        <div
            class="absolute right-[-100px] top-[-50px] h-[400px] w-[400px] rounded-full bg-primary/30 blur-[120px]"
        ></div>

        <div class="relative z-10">
            <div
                class="flex flex-col items-center justify-between gap-8 rounded-[24px] border border-white/30 bg-primary px-8 py-12 backdrop-blur-xl md:flex-row md:px-14"
            >
                <div>
                    <h2
                        class="mb-3 text-center text-4xl font-black leading-tight text-white md:text-left md:text-5xl"
                    >
                        Start your business
                        <br />
                        with Amuma today.
                    </h2>

                    <p class="text-center text-white/80 md:text-left">
                        Create your business profile, manage your branches, and
                        reach more customers—all in one place.
                    </p>
                </div>

                <button
                    class="group flex items-center gap-2 rounded-xl bg-white px-[5rem] py-4 font-bold text-primary transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                >
                    Get Started

                    <span
                        class="transition-transform duration-300 group-hover:translate-x-1 group-hover:-translate-y-1"
                    >
                        →
                    </span>
                </button>
            </div>
        </div>
    </section>
</template>
