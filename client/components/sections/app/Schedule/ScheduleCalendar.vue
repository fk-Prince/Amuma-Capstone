<template>
    <div ref="root" class="flex flex-col gap-4 p-3 sm:p-5">
        <!-- Month bar -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    aria-label="Previous month"
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-primary hover:text-primary dark:border-white/10 dark:text-gray-300"
                    @click="shiftMonth(-1)"
                >
                    <ChevronLeft class="h-4 w-4" />
                </button>
                <button
                    type="button"
                    aria-label="Next month"
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-primary hover:text-primary dark:border-white/10 dark:text-gray-300"
                    @click="shiftMonth(1)"
                >
                    <ChevronRight class="h-4 w-4" />
                </button>
                <button
                    type="button"
                    class="h-8 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-primary hover:text-primary dark:border-white/10 dark:text-gray-300"
                    @click="goToToday"
                >
                    Today
                </button>
            </div>

            <h3 class="text-base font-bold text-slate-800 sm:text-lg dark:text-white">
                {{ monthTitle }}
            </h3>

            <div
                class="flex flex-wrap items-center gap-1.5 text-xs font-semibold sm:ml-auto"
            >
                <slot name="actions" />
                <span
                    class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 dark:bg-white/10 dark:text-gray-300"
                >
                    {{ monthStats.total }} {{ monthStats.total === 1 ? "visit" : "visits" }}
                </span>
                <span
                    v-if="monthStats.needStaff"
                    class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                >
                    <UserX class="h-3 w-3" />
                    {{ monthStats.needStaff }} need staff
                </span>
                <span
                    v-if="monthStats.missed"
                    class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                >
                    {{ monthStats.missed }} missed
                </span>
            </div>
        </div>

        <!-- Colour key -->
        <div
            class="-mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500 dark:text-gray-400"
        >
            <span
                v-for="item in legend"
                :key="item.label"
                class="inline-flex items-center gap-1.5"
            >
                <span class="h-2 w-2 rounded-full" :class="item.dot" />
                {{ item.label }}
            </span>
            <span class="inline-flex items-center gap-1">
                <UserX class="h-3 w-3 text-rose-500" />
                No staff yet
            </span>
        </div>

        <!-- The loaded dates don't cover this whole month -->
        <div
            v-if="partialMonth"
            class="flex flex-wrap items-center gap-2 rounded-xl border border-primary-100 bg-primary-50 px-3 py-2 text-xs text-primary-700 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-200"
        >
            <Info class="h-3.5 w-3.5 shrink-0" />
            <span class="min-w-0 flex-1">{{ partialMonth }}</span>
            <button
                type="button"
                class="rounded-md bg-white px-2 py-1 font-semibold text-primary shadow-sm transition hover:bg-primary-100 dark:bg-white/10 dark:text-primary-200"
                @click="showWholeMonth"
            >
                Show all of {{ monthName }}
            </button>
        </div>

        <div
            :class="
                wide
                    ? 'grid grid-cols-[minmax(0,1fr)_22rem] items-start gap-4'
                    : 'flex flex-col gap-4'
            "
        >
        <!-- Month grid -->
        <div
            class="relative overflow-hidden rounded-2xl border border-slate-200 dark:border-white/10"
        >
            <div
                v-if="loading"
                class="absolute inset-x-0 top-0 z-10 h-0.5 overflow-hidden bg-primary/10"
            >
                <span class="calendar-loading block h-full w-1/3 bg-primary" />
            </div>

            <div
                class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/5"
            >
                <span
                    v-for="(name, i) in WEEKDAYS"
                    :key="name"
                    class="py-2 text-center text-[11px] font-bold uppercase tracking-wide"
                    :class="i === 0 || i === 6 ? 'text-slate-400 dark:text-gray-500' : 'text-slate-500 dark:text-gray-400'"
                >
                    <span class="md:hidden">{{ name.charAt(0) }}</span>
                    <span class="hidden md:inline">{{ name }}</span>
                </span>
            </div>

            <div class="grid grid-cols-7">
                <div
                    v-for="cell in cells"
                    :key="cell.iso"
                    role="button"
                    tabindex="0"
                    :aria-label="cellLabel(cell)"
                    :aria-pressed="cell.iso === selectedDate"
                    class="group relative flex min-h-[3.5rem] cursor-pointer flex-col gap-1 border-b border-r border-slate-100 p-1 text-left outline-none transition focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary md:min-h-[7.25rem] md:p-1.5 dark:border-white/[0.06] [&:nth-child(7n)]:border-r-0"
                    :class="cellClass(cell)"
                    @click="selectDay(cell.iso)"
                    @keydown.enter.prevent="selectDay(cell.iso)"
                    @keydown.space.prevent="selectDay(cell.iso)"
                >
                    <div class="flex items-center justify-between gap-1">
                        <span
                            class="flex h-6 min-w-[1.5rem] items-center justify-center rounded-full px-1 text-xs font-semibold tabular-nums"
                            :class="dayNumberClass(cell)"
                        >
                            {{ cell.day }}
                        </span>

                        <span
                            v-if="cell.inMonth && cell.needStaff"
                            class="hidden items-center gap-0.5 rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-bold text-rose-600 md:inline-flex dark:bg-rose-500/10 dark:text-rose-300"
                            :title="`${cell.needStaff} need staff`"
                        >
                            <UserX class="h-2.5 w-2.5" />
                            {{ cell.needStaff }}
                        </span>
                    </div>

                    <!-- Phones: a dot per visit -->
                    <div
                        v-if="cell.inMonth && cell.items.length"
                        class="flex flex-wrap items-center justify-center gap-0.5 md:hidden"
                    >
                        <span
                            v-for="s in cell.items.slice(0, 4)"
                            :key="s.schedule_id"
                            class="h-1.5 w-1.5 rounded-full"
                            :class="scheduleStatusTheme(s.status).dot"
                        />
                        <span
                            v-if="cell.items.length > 4"
                            class="text-[9px] font-bold leading-none text-slate-500"
                        >
                            +{{ cell.items.length - 4 }}
                        </span>
                        <UserX
                            v-if="cell.needStaff"
                            class="ml-0.5 h-2.5 w-2.5 text-rose-500"
                        />
                    </div>

                    <!-- Wider screens: one line per visit; hover for everything -->
                    <ul
                        v-if="cell.inMonth && cell.items.length"
                        class="hidden min-w-0 flex-col gap-1 md:flex"
                    >
                        <li
                            v-for="s in cell.items.slice(0, MAX_CHIPS)"
                            :key="s.schedule_id"
                        >
                            <button
                                type="button"
                                class="flex w-full min-w-0 items-center gap-1 rounded-md border-l-[3px] px-1.5 py-[3px] text-left text-[11px] leading-tight transition hover:brightness-95 dark:hover:brightness-125"
                                :class="chipClass(s)"
                                @click.stop="emit('view-details', s)"
                                @mouseenter="showPeek(s, $event)"
                                @mouseleave="hidePeekSoon"
                                @focus="showPeek(s, $event)"
                                @blur="hidePeekSoon"
                            >
                                <span class="shrink-0 font-bold tabular-nums">
                                    {{ shortTime(s.start_time) }}
                                </span>
                                <span class="min-w-0 flex-1 truncate font-medium">
                                    {{ patientName(s) }}
                                </span>
                                <UserX
                                    v-if="needsStaff(s)"
                                    class="h-3 w-3 shrink-0 text-rose-500"
                                />
                            </button>
                        </li>
                        <li v-if="cell.items.length > MAX_CHIPS">
                            <span
                                class="block px-1.5 text-[11px] font-semibold text-primary dark:text-primary-300"
                            >
                                +{{ cell.items.length - MAX_CHIPS }} more
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- The picked day, in full -->
        <section
            ref="dayPanel"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-white/10 dark:bg-secondary"
        >
            <header
                class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-slate-100 px-4 py-3 sm:px-5 dark:border-white/10"
            >
                <span
                    class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl leading-none"
                    :class="
                        selectedDate === todayIso
                            ? 'bg-primary text-white'
                            : 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-gray-300'
                    "
                >
                    <span class="text-[10px] font-semibold uppercase">
                        {{ formatShort(selectedDate, { month: "short" }) }}
                    </span>
                    <span class="text-base font-bold">{{ parseIso(selectedDate).getDate() }}</span>
                </span>

                <div class="min-w-0 flex-1">
                    <p class="flex flex-wrap items-center gap-2 font-semibold text-slate-800 dark:text-white">
                        {{ formatShort(selectedDate, { weekday: "long", month: "long", day: "numeric" }) }}
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                            :class="relativeDay(selectedDate).class"
                        >
                            {{ relativeDay(selectedDate).label }}
                        </span>
                    </p>
                    <p class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-slate-500 dark:text-gray-400">
                        <span>
                            {{ selectedItems.length }}
                            {{ selectedItems.length === 1 ? "visit" : "visits" }}
                        </span>
                        <span
                            v-if="selectedNeedStaff"
                            class="font-semibold text-rose-600 dark:text-rose-300"
                        >
                            {{ selectedNeedStaff }} need staff
                        </span>
                    </p>
                </div>
            </header>

            <p
                v-if="!inRange(selectedDate)"
                class="px-5 py-8 text-center text-sm text-slate-400 dark:text-gray-500"
            >
                This day is outside your date filter.
                <button
                    type="button"
                    class="font-semibold text-primary hover:underline dark:text-primary-300"
                    @click="showWholeMonth"
                >
                    Show all of {{ monthName }}
                </button>
            </p>

            <p
                v-else-if="!selectedItems.length"
                class="px-5 py-8 text-center text-sm text-slate-400 dark:text-gray-500"
            >
                {{ loading ? "Loading visits…" : "No visits on this day." }}
            </p>

            <ol
                v-else
                class="divide-y divide-slate-100 dark:divide-white/10"
            >
                <template
                    v-for="(schedule, i) in selectedItems"
                    :key="schedule.schedule_id"
                >
                    <li
                        v-if="selectedDate === todayIso && i === nowIndex"
                        class="flex items-center gap-2 px-4 py-1.5 sm:px-5"
                        aria-label="Current time"
                    >
                        <span class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white">
                            Now · {{ nowLabel }}
                        </span>
                        <span class="h-px flex-1 bg-primary/40" />
                    </li>

                    <li
                        class="relative cursor-pointer transition hover:bg-slate-50/70 dark:hover:bg-white/[0.03]"
                        :class="{ 'opacity-60': isCancelled(schedule) }"
                        @click="emit('view-details', schedule)"
                    >
                        <span
                            class="absolute inset-y-0 left-0 w-1"
                            :class="scheduleStatusTheme(schedule.status).dot"
                            aria-hidden="true"
                        />

                        <div
                            class="grid grid-cols-[4.75rem_minmax(0,1fr)] gap-x-3 gap-y-2 py-3 pl-4 pr-3 sm:pl-5"
                            :class="
                                wide
                                    ? ''
                                    : 'lg:grid-cols-[6rem_minmax(0,1.3fr)_minmax(0,1fr)_auto] lg:items-center lg:gap-x-5 lg:pr-5'
                            "
                        >
                            <div class="leading-tight">
                                <p class="text-sm font-bold tabular-nums text-slate-800 dark:text-white">
                                    {{ schedule.start_time ?? "Any time" }}
                                </p>
                                <p
                                    v-if="schedule.end_time"
                                    class="text-xs tabular-nums text-slate-400 dark:text-gray-500"
                                >
                                    to {{ schedule.end_time }}
                                </p>
                                <p
                                    v-if="durationLabel(schedule)"
                                    class="mt-0.5 text-[11px] text-slate-400 dark:text-gray-500"
                                >
                                    {{ durationLabel(schedule) }}
                                </p>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-white">
                                        {{ schedule.patient?.full_name ?? "—" }}
                                    </p>
                                    <span
                                        v-if="upNextId === schedule.schedule_id"
                                        class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-700 dark:bg-amber-500/15 dark:text-amber-300"
                                    >
                                        Up next
                                    </span>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <span
                                        class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold"
                                        :class="scheduleStatusTheme(schedule.status).badge"
                                    >
                                        {{ scheduleStatusLabel(schedule.status) }}
                                    </span>
                                    <span
                                        class="rounded-md px-1.5 py-0.5 text-[10px] font-medium"
                                        :class="categoryClass(schedule)"
                                    >
                                        {{ categoryLabel(schedule) }}
                                    </span>
                                    <span class="font-mono text-[10px] text-slate-400 dark:text-gray-500">
                                        {{ schedule.schedule_code }}
                                    </span>
                                </div>
                                <p
                                    v-if="placeLabel(schedule)"
                                    class="mt-1 flex min-w-0 items-center gap-1 text-[11px] text-slate-500 dark:text-gray-400"
                                    :title="placeLabel(schedule)"
                                >
                                    <MapPin class="h-3 w-3 shrink-0" />
                                    <span class="min-w-0 truncate">{{ placeLabel(schedule) }}</span>
                                </p>
                            </div>

                            <div
                                class="col-span-2 min-w-0 rounded-xl bg-slate-50 px-3 py-2 dark:bg-white/5"
                                :class="
                                    wide
                                        ? ''
                                        : 'lg:col-span-1 lg:bg-transparent lg:p-0 lg:dark:bg-transparent'
                                "
                            >
                                <p
                                    v-if="serviceNames(schedule).length"
                                    class="truncate text-xs text-slate-600 dark:text-gray-300"
                                    :title="serviceNames(schedule).join(', ')"
                                >
                                    <Stethoscope class="mr-1 inline h-3.5 w-3.5 text-slate-400" />
                                    {{ serviceNames(schedule).join(", ") }}
                                </p>
                                <StaffLine :people="assigneesOf(schedule)" class="mt-1" />
                            </div>

                            <div
                                class="col-span-2 flex gap-2"
                                :class="wide ? '' : 'lg:col-span-1 lg:justify-end'"
                                @click.stop
                            >
                                <button
                                    type="button"
                                    class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-primary hover:text-primary lg:flex-none dark:border-white/10 dark:bg-white/5 dark:text-gray-200"
                                    @click="emit('view-details', schedule)"
                                >
                                    Details
                                </button>
                                <button
                                    v-if="canAssignSchedule && canBeAssigned(schedule)"
                                    type="button"
                                    class="flex-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition lg:flex-none"
                                    :class="assignButtonClass(schedule)"
                                    @click="emit('assign', schedule)"
                                >
                                    {{ hasScheduleAssignees(schedule) ? "Change staff" : "Assign staff" }}
                                </button>
                                <!-- Missed visits: set a new date and time from the details window -->
                                <button
                                    v-if="canRescheduleSchedule && isMissed(schedule)"
                                    type="button"
                                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-600 lg:flex-none"
                                    @click="emit('view-details', schedule)"
                                >
                                    <CalendarClock class="h-3.5 w-3.5" />
                                    Reschedule
                                </button>
                            </div>
                        </div>
                    </li>
                </template>

                <li
                    v-if="selectedDate === todayIso && nowIndex === selectedItems.length"
                    class="flex items-center gap-2 px-4 py-1.5 sm:px-5"
                    aria-label="Current time"
                >
                    <span class="rounded-full bg-primary px-2 py-0.5 text-[10px] font-bold text-white">
                        Now · {{ nowLabel }}
                    </span>
                    <span class="h-px flex-1 bg-primary/40" />
                </li>
            </ol>
        </section>
        </div>

        <!-- Hover card: every detail of a visit -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0 translate-y-1"
                leave-active-class="transition duration-100 ease-in"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="peek"
                    class="fixed z-[60] w-[19rem] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/15 dark:border-white/10 dark:bg-secondary"
                    :style="peekStyle"
                    role="dialog"
                    :aria-label="`Visit for ${peek.schedule.patient?.full_name ?? 'patient'}`"
                    @mouseenter="cancelHide"
                    @mouseleave="hidePeekSoon"
                >
                    <div
                        class="h-1.5"
                        :class="scheduleStatusTheme(peek.schedule.status).dot"
                    />

                    <div class="space-y-3 p-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold"
                                    :class="scheduleStatusTheme(peek.schedule.status).badge"
                                >
                                    {{ scheduleStatusLabel(peek.schedule.status) }}
                                </span>
                                <span
                                    class="rounded-md px-1.5 py-0.5 text-[10px] font-medium"
                                    :class="categoryClass(peek.schedule)"
                                >
                                    {{ categoryLabel(peek.schedule) }}
                                </span>
                                <span class="ml-auto font-mono text-[10px] text-slate-400 dark:text-gray-500">
                                    {{ peek.schedule.schedule_code }}
                                </span>
                            </div>
                            <p class="mt-2 text-base font-bold leading-snug text-slate-800 dark:text-white">
                                {{ peek.schedule.patient?.full_name ?? "—" }}
                            </p>
                        </div>

                        <dl class="space-y-2 text-xs">
                            <div class="flex gap-2">
                                <dt><Clock class="h-3.5 w-3.5 text-slate-400" /></dt>
                                <dd class="text-slate-700 dark:text-gray-200">
                                    <span class="font-semibold">
                                        {{ peek.schedule.start_time ?? "Any time" }}
                                        <template v-if="peek.schedule.end_time">
                                            – {{ peek.schedule.end_time }}
                                        </template>
                                    </span>
                                    <span v-if="durationLabel(peek.schedule)" class="text-slate-400">
                                        · {{ durationLabel(peek.schedule) }}
                                    </span>
                                    <span class="block text-slate-500 dark:text-gray-400">
                                        {{ peekDateLabel }}
                                    </span>
                                </dd>
                            </div>

                            <div
                                v-if="placeLabel(peek.schedule) || peek.schedule.note"
                                class="flex gap-2"
                            >
                                <dt><MapPin class="h-3.5 w-3.5 text-slate-400" /></dt>
                                <dd class="min-w-0 text-slate-700 dark:text-gray-200">
                                    <template v-if="placeLabel(peek.schedule)">
                                        {{ placeLabel(peek.schedule) }}
                                    </template>
                                    <span
                                        v-if="peek.schedule.note"
                                        class="mt-0.5 flex items-start gap-1 text-slate-500 dark:text-gray-400"
                                    >
                                        <Signpost class="mt-0.5 h-3 w-3 shrink-0" />
                                        <span class="line-clamp-3">{{ peek.schedule.note }}</span>
                                    </span>
                                </dd>
                            </div>

                            <div v-if="serviceNames(peek.schedule).length" class="flex gap-2">
                                <dt><Stethoscope class="h-3.5 w-3.5 text-slate-400" /></dt>
                                <dd class="min-w-0">
                                    <ul class="space-y-0.5 text-slate-700 dark:text-gray-200">
                                        <li
                                            v-for="name in serviceNames(peek.schedule)"
                                            :key="name"
                                        >
                                            {{ name }}
                                        </li>
                                    </ul>
                                </dd>
                            </div>

                            <div class="flex gap-2">
                                <dt><Users class="h-3.5 w-3.5 text-slate-400" /></dt>
                                <dd class="min-w-0 flex-1">
                                    <ul
                                        v-if="assigneesOf(peek.schedule).length"
                                        class="space-y-1"
                                    >
                                        <li
                                            v-for="person in assigneesOf(peek.schedule)"
                                            :key="person.employee_id"
                                            class="flex items-center gap-2 text-slate-700 dark:text-gray-200"
                                        >
                                            <Avatar :person="person" />
                                            <span class="truncate">{{ person.full_name }}</span>
                                        </li>
                                    </ul>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"
                                    >
                                        <UserX class="h-3 w-3" />
                                        No staff assigned yet
                                    </span>
                                </dd>
                            </div>

                            <div
                                v-if="peek.schedule.patient?.guardian?.full_name"
                                class="flex gap-2"
                            >
                                <dt><Phone class="h-3.5 w-3.5 text-slate-400" /></dt>
                                <dd class="text-slate-700 dark:text-gray-200">
                                    {{ peek.schedule.patient.guardian.full_name }}
                                    <span
                                        v-if="peek.schedule.patient.guardian.relationship"
                                        class="text-slate-400"
                                    >
                                        ({{ peek.schedule.patient.guardian.relationship }})
                                    </span>
                                    <span
                                        v-if="peek.schedule.patient.guardian.phone_number"
                                        class="block text-slate-500 dark:text-gray-400"
                                    >
                                        {{ peek.schedule.patient.guardian.phone_number }}
                                    </span>
                                </dd>
                            </div>
                        </dl>

                        <div class="flex gap-2 pt-1">
                            <button
                                type="button"
                                class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-primary hover:text-primary dark:border-white/10 dark:bg-white/5 dark:text-gray-200"
                                @click="openFromPeek('view-details')"
                            >
                                Details
                            </button>
                            <button
                                v-if="canAssignSchedule && canBeAssigned(peek.schedule)"
                                type="button"
                                class="flex-1 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                :class="assignButtonClass(peek.schedule)"
                                @click="openFromPeek('assign')"
                            >
                                {{ hasScheduleAssignees(peek.schedule) ? "Change staff" : "Assign staff" }}
                            </button>
                            <button
                                v-if="canRescheduleSchedule && isMissed(peek.schedule)"
                                type="button"
                                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-600"
                                @click="openFromPeek('view-details')"
                            >
                                <CalendarClock class="h-3.5 w-3.5" />
                                Reschedule
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script lang="ts" setup>
import { computed, defineComponent, h, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useElementSize } from "@vueuse/core";
import {
    CalendarClock,
    ChevronLeft,
    ChevronRight,
    Clock,
    Info,
    MapPin,
    Phone,
    Stethoscope,
    Signpost,
    Users,
    UserX,
} from "lucide-vue-next";
import type { ScheduleItem } from "~/types/schedule";
import { hasScheduleAssignees } from "~/utils/schedule-status";
import { initials } from "~/utils/user";
import { useSchedule } from "~/composables/useSchedule";
import { usePermissions } from "~/composables/usePermission";
import { Modules } from "~/types/module";

/**
 * Month calendar of visits. Each day shows its visits at a glance (time,
 * patient, status colour, missing staff); hovering one shows everything about
 * it, and picking a day lists that day in full underneath.
 */
const props = withDefaults(
    defineProps<{
        schedules?: ScheduleItem[];
        loading?: boolean;
        date?: string;
        rangeEnd?: string;
    }>(),
    { schedules: () => [], loading: false },
);

const emit = defineEmits<{
    (e: "view-details", schedule: ScheduleItem): void;
    (e: "assign", schedule: ScheduleItem): void;
    (e: "change-range", range: { from: string; to: string }): void;
}>();

const WEEKDAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
const MAX_CHIPS = 3;

const root = ref<HTMLElement | null>(null);
const { width: rootWidth } = useElementSize(root);
const wide = computed(() => rootWidth.value >= 1100);

const { canAssign, canUpdate } = usePermissions();
const canAssignSchedule = computed(() => canAssign(Modules.Schedules));
// Missed visits can be moved to a new date in the details window (same rule it uses).
const canRescheduleSchedule = computed(() => canUpdate(Modules.Schedules));

const { today, toLocalDateString, schedulesForDay, scheduleStatusTheme, scheduleStatusLabel } =
    useSchedule(props);

type Person = { employee_id: number; full_name: string; avatar?: string | null };

// ---- small pieces -------------------------------------------------------

const Avatar = defineComponent({
    props: { person: { type: Object as () => Person, required: true } },
    setup(p) {
        return () =>
            h(
                "span",
                {
                    class: "flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-primary text-[9px] font-bold text-white dark:border-secondary",
                    title: p.person.full_name,
                },
                p.person.avatar
                    ? [h("img", { src: p.person.avatar, alt: p.person.full_name, class: "h-full w-full object-cover" })]
                    : initials(p.person.full_name),
            );
    },
});

const StaffLine = defineComponent({
    props: { people: { type: Array as () => Person[], required: true } },
    setup(p) {
        return () => {
            if (!p.people.length) {
                return h(
                    "p",
                    { class: "inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-600 dark:bg-rose-500/10 dark:text-rose-300" },
                    [h(UserX, { class: "h-3 w-3" }), "No staff assigned yet"],
                );
            }

            return h("div", { class: "flex min-w-0 items-center gap-2" }, [
                h(
                    "span",
                    { class: "flex shrink-0 -space-x-1.5" },
                    p.people.slice(0, 3).map((person) => h(Avatar, { key: person.employee_id, person })),
                ),
                h("span", { class: "truncate text-xs text-slate-600 dark:text-gray-300" }, [
                    p.people[0]!.full_name,
                    p.people.length > 1
                        ? h("span", { class: "text-slate-400" }, ` +${p.people.length - 1} more`)
                        : null,
                ]),
            ]);
        };
    },
});

// ---- dates --------------------------------------------------------------

const parseIso = (iso: string) => new Date(`${iso}T00:00:00`);
const todayIso = computed(() => today());

const formatShort = (iso: string, options: Intl.DateTimeFormatOptions) =>
    parseIso(iso).toLocaleDateString("en-US", options);

// The dates the list was loaded for; days outside it are greyed out.
function inRange(iso: string) {
    if (props.date && iso < props.date) return false;
    if (props.rangeEnd && iso > props.rangeEnd) return false;
    return true;
}

function monthStartOf(iso: string) {
    const d = parseIso(iso);
    return new Date(d.getFullYear(), d.getMonth(), 1);
}

function initialMonth() {
    const t = todayIso.value;
    if (inRange(t)) return monthStartOf(t);
    return monthStartOf(props.date || t);
}

const visibleMonth = ref<Date>(initialMonth());

const monthName = computed(() =>
    visibleMonth.value.toLocaleDateString("en-US", { month: "long" }),
);
const monthTitle = computed(() =>
    visibleMonth.value.toLocaleDateString("en-US", { month: "long", year: "numeric" }),
);

const monthBounds = computed(() => {
    const start = visibleMonth.value;
    const end = new Date(start.getFullYear(), start.getMonth() + 1, 0);
    return { from: toLocalDateString(start), to: toLocalDateString(end) };
});

function needsStaff(s: ScheduleItem) {
    return canBeAssigned(s) && (s.services ?? []).some((x) => !x.assignees?.length);
}

function toMinutes(label?: string | null) {
    const match = label?.match(/(\d{1,2}):(\d{2})\s?(AM|PM)/i);
    if (!match) return null;

    let hour = Number(match[1]) % 12;
    if (match[3]!.toUpperCase() === "PM") hour += 12;

    return hour * 60 + Number(match[2]);
}

const sortByTime = (list: ScheduleItem[]) =>
    [...list].sort((a, b) => (toMinutes(a.start_time) ?? -1) - (toMinutes(b.start_time) ?? -1));

const cells = computed(() => {
    const first = visibleMonth.value;
    const month = first.getMonth();
    const lead = first.getDay();
    const daysInMonth = new Date(first.getFullYear(), month + 1, 0).getDate();
    const weeks = Math.ceil((lead + daysInMonth) / 7);

    const cursor = new Date(first);
    cursor.setDate(1 - lead);

    return Array.from({ length: weeks * 7 }, () => {
        const iso = toLocalDateString(cursor);
        const inMonth = cursor.getMonth() === month;
        const items = inMonth ? sortByTime(schedulesForDay(iso)) : [];
        const cell = {
            iso,
            day: cursor.getDate(),
            weekday: cursor.getDay(),
            inMonth,
            loaded: inRange(iso),
            items,
            needStaff: items.filter(needsStaff).length,
        };
        cursor.setDate(cursor.getDate() + 1);
        return cell;
    });
});

type Cell = (typeof cells.value)[number];

const monthStats = computed(() => {
    const seen = new Map<number, ScheduleItem>();
    for (const cell of cells.value) for (const s of cell.items) seen.set(s.schedule_id, s);
    const all = [...seen.values()];

    return {
        total: all.length,
        needStaff: all.filter(needsStaff).length,
        missed: all.filter((s) => s.status?.toLowerCase() === "missed").length,
    };
});

const partialMonth = computed(() => {
    const { from, to } = monthBounds.value;
    const startsLate = !!props.date && props.date > from;
    const endsEarly = !!props.rangeEnd && props.rangeEnd < to;

    if (!startsLate && !endsEarly) return "";
    if (props.date && props.rangeEnd && (props.date > to || props.rangeEnd < from)) {
        return `Your date filter doesn't include ${monthName.value}.`;
    }

    const pieces: string[] = [];
    if (startsLate) pieces.push(`from ${formatShort(props.date!, { month: "short", day: "numeric" })}`);
    if (endsEarly) pieces.push(`until ${formatShort(props.rangeEnd!, { month: "short", day: "numeric" })}`);
    return `Showing visits ${pieces.join(" ")} only; greyed-out days aren't loaded.`;
});

function showWholeMonth() {
    emit("change-range", monthBounds.value);
}

function shiftMonth(step: number) {
    const d = visibleMonth.value;
    visibleMonth.value = new Date(d.getFullYear(), d.getMonth() + step, 1);
    selectedDate.value = defaultSelection();
    emit("change-range", monthBounds.value);
}

function goToToday() {
    const target = monthStartOf(todayIso.value);
    const changed = target.getTime() !== visibleMonth.value.getTime();
    visibleMonth.value = target;
    selectedDate.value = todayIso.value;
    if (changed || !inRange(todayIso.value)) emit("change-range", monthBounds.value);
}

// When the filter's dates move elsewhere, follow them.
watch(
    () => [props.date, props.rangeEnd],
    () => {
        const { from, to } = monthBounds.value;
        const overlaps =
            (!props.date || props.date <= to) && (!props.rangeEnd || props.rangeEnd >= from);
        if (!overlaps) {
            visibleMonth.value = initialMonth();
            selectedDate.value = defaultSelection();
        }
    },
);

// ---- picked day ---------------------------------------------------------

function defaultSelection() {
    const { from, to } = monthBounds.value;
    const t = todayIso.value;
    return t >= from && t <= to ? t : from;
}

const selectedDate = ref(defaultSelection());
const dayPanel = ref<HTMLElement | null>(null);

watch(
    () => props.schedules,
    () => {
        const current = cells.value.find((c) => c.iso === selectedDate.value);
        if (!current?.inMonth) selectedDate.value = defaultSelection();
    },
);

function selectDay(iso: string) {
    const cell = cells.value.find((c) => c.iso === iso);
    if (cell && !cell.inMonth) {
        shiftMonth(iso < monthBounds.value.from ? -1 : 1);
    }
    selectedDate.value = iso;

    // The day's full list sits below the grid; bring it into view.
    if (!wide.value) {
        nextTick(() => dayPanel.value?.scrollIntoView({ behavior: "smooth", block: "nearest" }));
    }
}

const selectedItems = computed(() => sortByTime(schedulesForDay(selectedDate.value)));
const selectedNeedStaff = computed(() => selectedItems.value.filter(needsStaff).length);

function cellLabel(cell: Cell) {
    const date = formatShort(cell.iso, { weekday: "long", month: "long", day: "numeric" });
    if (!cell.items.length) return `${date}, no visits`;
    return `${date}, ${cell.items.length} visits${cell.needStaff ? `, ${cell.needStaff} need staff` : ""}`;
}

function cellClass(cell: Cell) {
    if (!cell.inMonth) return "bg-slate-50/60 dark:bg-white/[0.02]";
    if (!cell.loaded) return "calendar-unloaded";

    const selected = cell.iso === selectedDate.value;
    const weekend = cell.weekday === 0 || cell.weekday === 6;

    return [
        selected
            ? "bg-primary-50/70 ring-2 ring-inset ring-primary dark:bg-primary-500/10"
            : weekend
              ? "bg-slate-50/40 hover:bg-slate-50 dark:bg-white/[0.015] dark:hover:bg-white/[0.04]"
              : "bg-white hover:bg-slate-50 dark:bg-secondary dark:hover:bg-white/[0.04]",
    ];
}

function dayNumberClass(cell: Cell) {
    if (!cell.inMonth) return "text-slate-300 dark:text-gray-600";
    if (cell.iso === todayIso.value) return "bg-primary text-white";
    if (cell.iso < todayIso.value) return "text-slate-400 dark:text-gray-500";
    return "text-slate-700 dark:text-gray-200";
}

// ---- visit helpers ------------------------------------------------------

const isCancelled = (s: ScheduleItem) => s.status?.toLowerCase() === "cancelled";
const isMissed = (s: ScheduleItem) => s.status?.toLowerCase() === "missed";
const canBeAssigned = (s: ScheduleItem) =>
    !["completed", "cancelled"].includes(s.status?.toLowerCase() ?? "");

const CHIP: Record<string, string> = {
    pending: "border-violet-400 bg-violet-50 text-violet-900 dark:bg-violet-500/15 dark:text-violet-100",
    ongoing: "border-emerald-400 bg-emerald-50 text-emerald-900 dark:bg-emerald-500/15 dark:text-emerald-100",
    completed: "border-sky-400 bg-sky-50 text-sky-900 dark:bg-sky-500/15 dark:text-sky-100",
    missed: "border-rose-400 bg-rose-50 text-rose-900 dark:bg-rose-500/15 dark:text-rose-100",
    cancelled: "border-slate-300 bg-slate-100 text-slate-500 line-through dark:bg-white/10 dark:text-gray-400",
};

const chipClass = (s: ScheduleItem) => CHIP[s.status?.toLowerCase() ?? ""] ?? CHIP.pending;

const legend = [
    { label: "Pending", dot: "bg-violet-500" },
    { label: "Ongoing", dot: "bg-emerald-500" },
    { label: "Completed", dot: "bg-sky-500" },
    { label: "Missed", dot: "bg-rose-500" },
    { label: "Cancelled", dot: "bg-slate-400" },
];

function shortTime(label?: string | null) {
    const minutes = toMinutes(label);
    if (minutes === null) return "All day";

    const hour = Math.floor(minutes / 60);
    const minute = minutes % 60;
    const display = hour % 12 === 0 ? 12 : hour % 12;
    return `${display}${minute ? `:${String(minute).padStart(2, "0")}` : ""}${hour >= 12 ? "p" : "a"}`;
}

const patientName = (s: ScheduleItem) => s.patient?.full_name ?? "Patient";

const categoryLabel = (s: ScheduleItem) => (s.category === "Facility" ? "Facility" : "Homecare");
const categoryClass = (s: ScheduleItem) =>
    s.category === "Facility"
        ? "bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300"
        : "bg-teal-50 text-teal-600 dark:bg-teal-500/15 dark:text-teal-300";

const assignButtonClass = (s: ScheduleItem) =>
    hasScheduleAssignees(s)
        ? "border border-primary/20 bg-primary/5 text-primary hover:bg-primary/10"
        : "bg-rose-500 text-white shadow-sm hover:bg-rose-600";

function durationLabel(s: ScheduleItem) {
    const minutes =
        s.total_duration_minutes > 0 ? s.total_duration_minutes : s.total_hours > 0 ? s.total_hours * 60 : 0;
    if (!minutes) return "";

    const hours = Math.floor(minutes / 60);
    const rest = Math.round(minutes % 60);
    return [hours ? `${hours}h` : "", rest ? `${rest}m` : ""].filter(Boolean).join(" ");
}

function placeLabel(s: ScheduleItem) {
    const bed = s.patient?.admission?.bed;
    if (bed) {
        return [bed.room?.room_no ? `Room ${bed.room.room_no}` : "", bed.bed_no ? `Bed ${bed.bed_no}` : ""]
            .filter(Boolean)
            .join(" · ");
    }
    return s.category !== "Facility" ? (s.address ?? "") : "";
}

const serviceNames = (s: ScheduleItem) =>
    (s.services ?? []).map((x) => x.service_name).filter(Boolean) as string[];

function assigneesOf(s: ScheduleItem): Person[] {
    const seen = new Map<number, Person>();
    for (const service of s.services ?? []) {
        for (const a of service.assignees ?? []) {
            if (!seen.has(a.employee_id)) seen.set(a.employee_id, a as Person);
        }
    }
    return [...seen.values()];
}

function relativeDay(iso: string) {
    const diff = Math.round((parseIso(iso).getTime() - parseIso(todayIso.value).getTime()) / 86_400_000);
    const muted = "bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-gray-400";

    if (diff === 0) return { label: "Today", class: "bg-primary/10 text-primary dark:text-primary-300" };
    if (diff === 1) return { label: "Tomorrow", class: "bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300" };
    if (diff > 1) return { label: `In ${diff} days`, class: muted };
    if (diff === -1) return { label: "Yesterday", class: muted };
    return { label: `${-diff} days ago`, class: muted };
}

// ---- now line -----------------------------------------------------------

const now = ref(new Date());
let ticker: ReturnType<typeof setInterval> | undefined;

const nowMinutes = computed(() => now.value.getHours() * 60 + now.value.getMinutes());
const nowLabel = computed(() =>
    now.value.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" }),
);

const nowIndex = computed(() => {
    const index = selectedItems.value.findIndex((s) => (toMinutes(s.start_time) ?? -1) > nowMinutes.value);
    return index === -1 ? selectedItems.value.length : index;
});

const upNextId = computed(
    () =>
        sortByTime(schedulesForDay(todayIso.value)).find(
            (s) => (toMinutes(s.start_time) ?? -1) >= nowMinutes.value && s.status?.toLowerCase() === "pending",
        )?.schedule_id ?? null,
);

// ---- hover card ---------------------------------------------------------

const peek = ref<{ schedule: ScheduleItem; rect: DOMRect } | null>(null);
let showTimer: ReturnType<typeof setTimeout> | undefined;
let hideTimer: ReturnType<typeof setTimeout> | undefined;

const PEEK_W = 304;

function canHover() {
    return window.matchMedia("(hover: hover) and (pointer: fine)").matches;
}

function showPeek(schedule: ScheduleItem, event: Event) {
    if (event.type === "mouseenter" && !canHover()) return;

    cancelHide();
    clearTimeout(showTimer);
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    const delay = peek.value ? 0 : 180;
    showTimer = setTimeout(() => (peek.value = { schedule, rect }), delay);
}

function hidePeekSoon() {
    clearTimeout(showTimer);
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => (peek.value = null), 160);
}

function cancelHide() {
    clearTimeout(hideTimer);
}

function hidePeekNow() {
    clearTimeout(showTimer);
    clearTimeout(hideTimer);
    peek.value = null;
}

function openFromPeek(kind: "view-details" | "assign") {
    const schedule = peek.value?.schedule;
    hidePeekNow();
    if (!schedule) return;
    if (kind === "assign") emit("assign", schedule);
    else emit("view-details", schedule);
}

const peekDateLabel = computed(() => {
    const s = peek.value?.schedule;
    const iso = s?.scheduled_date ?? (s?.scheduled_at ? toLocalDateString(new Date(s.scheduled_at)) : "");
    return iso ? formatShort(iso, { weekday: "long", month: "long", day: "numeric" }) : "";
});

// Beside the chip: to the right if it fits, else to the left; kept on screen.
const peekStyle = computed(() => {
    const rect = peek.value!.rect;
    const gap = 10;
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    const left =
        rect.right + gap + PEEK_W <= vw - 8
            ? rect.right + gap
            : Math.max(8, rect.left - gap - PEEK_W);

    const top = Math.min(Math.max(8, rect.top - 12), Math.max(8, vh - 440));

    return { left: `${left}px`, top: `${top}px` };
});

onMounted(() => {
    ticker = setInterval(() => (now.value = new Date()), 60_000);
    window.addEventListener("scroll", hidePeekNow, true);
    window.addEventListener("resize", hidePeekNow);
});

onBeforeUnmount(() => {
    clearInterval(ticker);
    clearTimeout(showTimer);
    clearTimeout(hideTimer);
    window.removeEventListener("scroll", hidePeekNow, true);
    window.removeEventListener("resize", hidePeekNow);
});
</script>

<style scoped>
.calendar-unloaded {
    background-image: repeating-linear-gradient(
        -45deg,
        rgb(148 163 184 / 0.08) 0 6px,
        transparent 6px 12px
    );
}

.calendar-loading {
    animation: calendar-loading 1.1s ease-in-out infinite;
}

@keyframes calendar-loading {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(300%); }
}

@media (prefers-reduced-motion: reduce) {
    .calendar-loading {
        animation: none;
    }
}
</style>
