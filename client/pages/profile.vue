<template>
    <div
        :class="
            embedded
                ? 'w-full font-sans'
                : 'min-h-screen bg-light pt-[124px] font-sans dark:bg-surface'
        "
    >
        <div
            :class="
                embedded
                    ? 'w-full space-y-4 pb-16'
                    : 'mx-auto max-w-[100rem] space-y-4 px-4 pb-16 sm:px-6'
            "
        >
            <!-- Header: title, save actions and the tabs -->
            <header
                class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-white/10 dark:bg-secondary"
            >
                <div
                    class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:p-6"
                    :class="headerHasTitle ? 'sm:justify-end' : 'sm:justify-between'"
                >
                    <div v-if="!headerHasTitle" class="flex min-w-0 items-center gap-3">
                        <span
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary dark:bg-primary-500/15 dark:text-primary-300"
                        >
                            <UserRound class="h-5 w-5" />
                        </span>

                        <div class="min-w-0">
                            <h1
                                class="text-lg font-bold tracking-tight text-secondary sm:text-xl dark:text-white"
                            >
                                My Profile
                            </h1>

                            <p class="text-sm text-muted dark:text-gray-400">
                                Manage your personal details and account settings.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            v-if="isDirty && !saving"
                            class="mr-1 inline-flex items-center gap-1.5 text-xs font-semibold text-amber-600 dark:text-amber-300"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500" />
                            Unsaved changes
                        </span>

                        <button
                            type="button"
                            :disabled="saving || !isDirty"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                            @click="reset"
                        >
                            Cancel
                        </button>

                        <button
                            type="button"
                            :disabled="saving || !isDirty"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-primary/20 transition hover:bg-primary-600 disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                            @click="save"
                        >
                            <LoaderCircle
                                v-if="saving"
                                class="h-3.5 w-3.5 animate-spin"
                            />
                            <Check v-else class="h-4 w-4" />
                            {{ saving ? "Saving..." : "Save changes" }}
                        </button>
                    </div>
                </div>

                <nav
                    class="profile-tabs flex gap-1 overflow-x-auto border-t border-slate-100 px-2 sm:px-4 dark:border-white/10"
                    aria-label="Profile sections"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        class="relative inline-flex shrink-0 items-center gap-2 px-3 py-3 text-sm font-semibold transition sm:px-4"
                        :class="
                            activeTab === tab.value
                                ? 'text-primary dark:text-primary-300'
                                : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                        :aria-current="activeTab === tab.value ? 'page' : undefined"
                        @click="activeTab = tab.value"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />
                        {{ tab.label }}
                        <span
                            v-if="tab.value === 'notifications' && unreadCount"
                            class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"
                        >
                            {{ unreadCount }}
                        </span>
                        <span
                            v-if="activeTab === tab.value"
                            class="absolute inset-x-2 bottom-0 h-0.5 rounded-full bg-primary"
                        />
                    </button>
                </nav>
            </header>

            <!-- Loading -->
            <div
                v-if="loading"
                class="grid gap-4 xl:grid-cols-[320px_minmax(0,1fr)]"
            >
                <div
                    class="h-80 animate-pulse rounded-2xl bg-white shadow-sm dark:bg-secondary"
                />
                <div class="space-y-4">
                    <div
                        v-for="n in 3"
                        :key="n"
                        class="h-40 animate-pulse rounded-2xl bg-white shadow-sm dark:bg-secondary"
                    />
                </div>
            </div>

            <template v-else>
                <!-- GENERAL -->
                <div
                    v-show="activeTab === 'profile'"
                    class="grid items-start gap-4 xl:grid-cols-[320px_minmax(0,1fr)]"
                >
                    <!-- Who you are -->
                    <aside
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:sticky xl:top-4 dark:border-white/10 dark:bg-secondary"
                    >
                        <div
                            class="h-20 bg-gradient-to-r from-primary-100 via-primary-50 to-accent-50 dark:from-primary-500/20 dark:via-primary-500/10 dark:to-accent-500/10"
                        />

                        <div class="-mt-12 px-5 pb-5 text-center">
                            <div class="relative mx-auto h-24 w-24">
                                <img
                                    :src="avatarPreview || fallbackAvatar"
                                    alt="Profile photo"
                                    class="h-24 w-24 rounded-full object-cover ring-4 ring-white dark:ring-secondary"
                                />

                                <button
                                    type="button"
                                    aria-label="Change photo"
                                    class="absolute bottom-0 right-0 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-white shadow-md ring-2 ring-white transition hover:bg-primary-600 dark:ring-secondary"
                                    @click="avatarInput?.click()"
                                >
                                    <Camera class="h-4 w-4" />
                                </button>
                            </div>

                            <p
                                class="mt-3 truncate text-base font-bold text-secondary dark:text-white"
                            >
                                {{ displayName }}
                            </p>
                            <p class="truncate text-sm text-slate-500 dark:text-gray-400">
                                {{ form.email || "—" }}
                            </p>

                            <div
                                v-if="roleBadges.length"
                                class="mt-3 flex flex-wrap justify-center gap-1.5"
                            >
                                <span
                                    v-for="badge in roleBadges"
                                    :key="badge"
                                    class="rounded-full bg-primary-50 px-2.5 py-0.5 text-[11px] font-semibold text-primary dark:bg-primary-500/15 dark:text-primary-300"
                                >
                                    {{ badge }}
                                </span>
                            </div>

                            <div class="mt-4 flex justify-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-600 transition hover:border-primary hover:text-primary dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                                    @click="avatarInput?.click()"
                                >
                                    Change photo
                                </button>

                                <button
                                    v-if="avatarFile && avatarSource"
                                    type="button"
                                    class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-semibold text-slate-600 transition hover:border-primary hover:text-primary dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                                    @click="showAvatarCrop = true"
                                >
                                    Adjust
                                </button>

                                <button
                                    v-if="avatarPreview"
                                    type="button"
                                    class="rounded-xl px-3 py-2 text-sm font-semibold text-slate-500 transition hover:bg-rose-50 hover:text-rose-600 dark:text-gray-400 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                                    @click="removeAvatar"
                                >
                                    Remove
                                </button>
                            </div>

                            <input
                                ref="avatarInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleAvatar"
                            />

                            <p
                                v-if="errors.avatar"
                                class="mt-2 text-xs text-red-600"
                            >
                                {{ errors.avatar }}
                            </p>
                        </div>

                        <!-- Account (read-only) -->
                        <dl
                            class="divide-y divide-slate-100 border-t border-slate-100 px-5 dark:divide-white/10 dark:border-white/10"
                        >
                            <div class="flex items-center justify-between gap-4 py-3">
                                <dt class="text-xs text-slate-500 dark:text-gray-400">
                                    Sign-in method
                                </dt>
                                <dd
                                    class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                >
                                    {{ signInMethods }}
                                </dd>
                            </div>

                            <div
                                v-for="row in accountRows"
                                :key="row.label"
                                class="flex items-center justify-between gap-4 py-3"
                            >
                                <dt class="text-xs text-slate-500 dark:text-gray-400">
                                    {{ row.label }}
                                </dt>
                                <dd
                                    class="truncate text-sm font-semibold text-slate-800 dark:text-white"
                                >
                                    {{ row.value }}
                                </dd>
                            </div>
                        </dl>
                    </aside>

                    <div class="min-w-0 space-y-4">
                        <!-- Personal info -->
                        <ProfileSection
                            :icon="IdCard"
                            title="Personal info"
                            description="Your name and contact details."
                        >
                            <div class="grid gap-5 sm:grid-cols-6">
                                <BaseInput
                                    v-model="form.first_name"
                                    label="First name"
                                    class-name="sm:col-span-2"
                                    :error="errors.first_name"
                                    @update:modelValue="clearError('first_name')"
                                />

                                <BaseInput
                                    v-model="form.middle_name"
                                    label="Middle name"
                                    class-name="sm:col-span-2"
                                    :error="errors.middle_name"
                                    @update:modelValue="clearError('middle_name')"
                                />

                                <BaseInput
                                    v-model="form.last_name"
                                    label="Last name"
                                    class-name="sm:col-span-2"
                                    :error="errors.last_name"
                                    @update:modelValue="clearError('last_name')"
                                />

                                <BaseInput
                                    v-model="form.email"
                                    label="Email"
                                    mode="email"
                                    class-name="sm:col-span-6"
                                    :error="errors.email"
                                    readonly
                                    @update:modelValue="clearError('email')"
                                />

                                <PhoneInput
                                    v-model="form.phone_number"
                                    label="Contact number"
                                    class-name="sm:col-span-3"
                                    :error="errors.phone_number"
                                    @update:modelValue="clearError('phone_number')"
                                />

                                <DatePickerField
                                    v-if="roles.is_employee"
                                    v-model="form.birth_date"
                                    label="Birth date"
                                    class-name="sm:col-span-3"
                                    placeholder="Select birth date"
                                    :max="today"
                                    :default-to-today="false"
                                    :error="errors.birth_date"
                                    @update:modelValue="clearError('birth_date')"
                                />

                                <BaseInput
                                    v-if="roles.is_client"
                                    v-model="form.occupation"
                                    label="Occupation"
                                    class-name="sm:col-span-3"
                                    :error="errors.occupation"
                                    @update:modelValue="clearError('occupation')"
                                />
                            </div>
                        </ProfileSection>

                        <!-- Address -->
                        <ProfileSection
                            v-if="canEditLocation"
                            :icon="MapPin"
                            title="Address"
                            description="Where you're based."
                        >
                            <template #actions>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-primary hover:text-primary dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                                    @click="useMap = !useMap"
                                >
                                    <component
                                        :is="useMap ? PencilLine : MapIcon"
                                        class="h-3.5 w-3.5"
                                    />
                                    {{ useMap ? "Enter manually" : "Pick on map" }}
                                </button>
                            </template>

                            <ClientOnly v-if="useMap">
                                <LocationSelector
                                    :initial-lat="form.latitude || undefined"
                                    :initial-lng="form.longitude || undefined"
                                    :initial-street="form.street || undefined"
                                    :initial-city="form.city || undefined"
                                    :initial-province="form.province || undefined"
                                    :initial-country="form.country || undefined"
                                    @location-selected="handleLocation"
                                />

                                <template #fallback>
                                    <div
                                        class="flex h-64 items-center justify-center rounded-xl bg-slate-50 text-sm text-slate-400 dark:bg-white/5 dark:text-gray-500"
                                    >
                                        Loading map...
                                    </div>
                                </template>
                            </ClientOnly>

                            <div v-else class="grid gap-5 sm:grid-cols-6">
                                <BaseInput
                                    v-model="form.street"
                                    label="Street"
                                    class-name="sm:col-span-6"
                                    :error="errors.street"
                                    @update:modelValue="clearError('street')"
                                />

                                <BaseInput
                                    v-model="form.city"
                                    label="City"
                                    class-name="sm:col-span-3"
                                    :error="errors.city"
                                    @update:modelValue="clearError('city')"
                                />

                                <BaseInput
                                    v-model="form.province"
                                    label="Province"
                                    class-name="sm:col-span-3"
                                    :error="errors.province"
                                    @update:modelValue="clearError('province')"
                                />

                                <BaseInput
                                    v-model="form.country"
                                    label="Country"
                                    class-name="sm:col-span-3"
                                    :error="errors.country"
                                    @update:modelValue="clearError('country')"
                                />
                            </div>
                        </ProfileSection>

                        <!-- Password -->
                        <ProfileSection
                            v-if="meta.provider === 'local'"
                            :icon="KeyRound"
                            title="Password"
                            :description="
                                meta.has_password
                                    ? 'Set a new password for your account.'
                                    : 'Add a password so you can sign in without Google.'
                            "
                        >
                            <div class="space-y-4">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <BaseInput
                                        v-if="meta.has_password"
                                        v-model="form.current_password"
                                        label="Current password"
                                        mode="password"
                                        :error="errors.current_password"
                                        @update:modelValue="clearError('current_password')"
                                    />

                                    <div>
                                        <BaseInput
                                            v-model="form.password"
                                            label="New password"
                                            mode="password"
                                            :error="errors.password"
                                            @update:modelValue="clearError('password')"
                                        />

                                        <p
                                            v-if="!errors.password"
                                            class="mt-1.5 text-xs text-slate-400 dark:text-gray-500"
                                        >
                                            Minimum 8 characters
                                        </p>
                                    </div>
                                </div>

                                <BaseInput
                                    v-if="form.password"
                                    v-model="form.password_confirmation"
                                    label="Confirm new password"
                                    mode="password"
                                    class="sm:max-w-[calc(50%-0.625rem)]"
                                    :error="errors.password_confirmation"
                                    @update:modelValue="clearError('password_confirmation')"
                                />

                                <button
                                    type="button"
                                    :disabled="saving || !form.password"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                                    @click="save"
                                >
                                    <ShieldCheck class="h-4 w-4" />
                                    Update password
                                </button>
                            </div>
                        </ProfileSection>
                    </div>
                </div>

                <!-- NOTIFICATIONS -->
                <div v-show="activeTab === 'notifications'">
                    <ProfileSection
                        :icon="Bell"
                        title="Notifications"
                        :description="
                            unreadCount
                                ? `${unreadCount} unread`
                                : 'You\'re all caught up.'
                        "
                        flush
                    >
                        <template #actions>
                            <div
                                class="flex items-center gap-1 rounded-xl bg-slate-100 p-1 dark:bg-white/10"
                                role="group"
                                aria-label="Show"
                            >
                                <button
                                    v-for="nTab in notificationFilters"
                                    :key="nTab.value"
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                    :class="
                                        notificationFilter === nTab.value
                                            ? 'bg-white text-primary shadow-sm dark:bg-secondary dark:text-primary-300'
                                            : 'text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-200'
                                    "
                                    @click="setNotificationFilter(nTab.value)"
                                >
                                    {{ nTab.label }}

                                    <span
                                        v-if="nTab.value === 'unread' && unreadCount"
                                        class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"
                                    >
                                        {{ unreadCount }}
                                    </span>
                                </button>
                            </div>

                            <button
                                type="button"
                                :disabled="!unreadCount || markingAllRead"
                                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                                @click="markAllNotificationsRead"
                            >
                                <LoaderCircle
                                    v-if="markingAllRead"
                                    class="h-3.5 w-3.5 animate-spin"
                                />
                                <CheckCheck v-else class="h-4 w-4" />
                                Mark all as read
                            </button>
                        </template>

                        <div v-if="notificationsLoading" class="space-y-3 p-4 sm:p-6">
                            <div
                                v-for="n in 4"
                                :key="n"
                                class="h-16 animate-pulse rounded-xl bg-slate-100 dark:bg-white/5"
                            />
                        </div>

                        <div
                            v-else-if="!notifications.length"
                            class="flex flex-col items-center justify-center px-6 py-20 text-center"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-white/5 dark:text-gray-500"
                            >
                                <Bell class="h-6 w-6" />
                            </div>

                            <p class="mt-3 text-sm font-semibold text-slate-800 dark:text-white">
                                {{
                                    notificationFilter === "unread"
                                        ? "No unread notifications"
                                        : "No notifications yet"
                                }}
                            </p>

                            <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-gray-400">
                                {{
                                    notificationFilter === "unread"
                                        ? "Everything here has been read."
                                        : "Updates about bookings, schedules and billing will show up here."
                                }}
                            </p>
                        </div>

                        <ul
                            v-else
                            class="divide-y divide-slate-100 dark:divide-white/10"
                        >
                            <li
                                v-for="item in notifications"
                                :key="item.id"
                                role="button"
                                tabindex="0"
                                class="group relative flex cursor-pointer items-start gap-3 px-4 py-3.5 outline-none transition hover:bg-slate-50 focus-visible:bg-slate-50 sm:gap-4 sm:px-6 dark:hover:bg-white/[0.03] dark:focus-visible:bg-white/[0.03]"
                                :class="
                                    item.unread
                                        ? 'bg-primary-50/50 dark:bg-primary-500/[0.06]'
                                        : ''
                                "
                                @click="openNotification(item)"
                                @keydown.enter.prevent="openNotification(item)"
                            >
                                <span
                                    v-if="item.unread"
                                    class="absolute inset-y-0 left-0 w-1 bg-primary"
                                    aria-hidden="true"
                                />

                                <img
                                    v-if="
                                        item.branch?.image &&
                                        !failedNotificationImages.has(item.id)
                                    "
                                    :src="item.branch.image"
                                    :alt="item.branch.name ?? 'Branch'"
                                    class="mt-0.5 h-10 w-10 shrink-0 rounded-xl object-cover ring-1 ring-slate-200 dark:ring-white/10"
                                    @error="failedNotificationImages.add(item.id)"
                                />

                                <span
                                    v-else
                                    class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                    :class="
                                        item.branch
                                            ? 'bg-primary-50 text-primary dark:bg-primary-500/15 dark:text-primary-300'
                                            : notificationToneFor(item.message_type).wrapper
                                    "
                                >
                                    <Building2 v-if="item.branch" class="h-4 w-4" />
                                    <component
                                        :is="notificationToneFor(item.message_type).icon"
                                        v-else
                                        class="h-4 w-4"
                                    />
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <p
                                            class="text-sm text-slate-800 dark:text-white"
                                            :class="item.unread ? 'font-bold' : 'font-semibold'"
                                        >
                                            {{ item.message_type }}
                                        </p>

                                        <span
                                            v-if="item.branch?.name"
                                            class="truncate rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500 dark:bg-white/5 dark:text-gray-400"
                                        >
                                            {{ item.branch.name }}
                                        </span>

                                        <span
                                            class="ml-auto shrink-0 text-xs text-slate-400 dark:text-gray-500"
                                        >
                                            {{ notifcationFormatDate(item.created_at) }}
                                        </span>
                                    </div>

                                    <p
                                        class="mt-0.5 text-sm leading-6 text-slate-600 dark:text-gray-300"
                                    >
                                        {{ item.message }}
                                    </p>
                                </div>

                                <span
                                    v-if="item.unread"
                                    class="mt-2 h-2 w-2 shrink-0 rounded-full bg-primary"
                                    aria-label="Unread"
                                />
                                <ChevronRight
                                    v-else
                                    class="mt-2 h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-primary dark:text-gray-600"
                                />
                            </li>
                        </ul>

                        <div
                            v-if="
                                !notificationsLoading &&
                                notificationsPage < notificationsLastPage
                            "
                            class="flex justify-center border-t border-slate-100 p-4 dark:border-white/10"
                        >
                            <button
                                type="button"
                                :disabled="notificationsLoadingMore"
                                class="rounded-xl border border-slate-200 bg-white px-5 py-2 text-sm font-semibold text-slate-600 transition hover:border-primary hover:text-primary disabled:cursor-not-allowed disabled:opacity-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300"
                                @click="fetchNotifications(notificationsPage + 1)"
                            >
                                {{ notificationsLoadingMore ? "Loading..." : "Load more" }}
                            </button>
                        </div>
                    </ProfileSection>
                </div>

                <ProfileTransactions v-if="activeTab === 'transactions'" />

                <!-- APPEARANCE -->
                <div v-show="activeTab === 'appearance'" class="space-y-4">
                    <ProfileSection
                        :icon="Palette"
                        title="Theme"
                        description="How AMUMA looks on this device."
                    >
                        <div class="grid max-w-xl grid-cols-2 gap-3">
                            <button
                                v-for="option in themeOptions"
                                :key="option.label"
                                type="button"
                                class="group rounded-2xl border-2 p-2 text-left transition"
                                :class="
                                    isDark === option.dark
                                        ? 'border-primary ring-4 ring-primary/10'
                                        : 'border-slate-200 hover:border-primary-200 dark:border-white/10 dark:hover:border-primary-500/40'
                                "
                                :aria-pressed="isDark === option.dark"
                                @click="setTheme(option.dark)"
                            >
                                <!-- Mini preview of the app -->
                                <span
                                    class="flex h-24 gap-1.5 overflow-hidden rounded-xl p-2"
                                    :class="option.dark ? 'bg-[#0f172a]' : 'bg-[#EEF3FB]'"
                                    aria-hidden="true"
                                >
                                    <span
                                        class="w-5 rounded-lg"
                                        :class="option.dark ? 'bg-[#1e293b]' : 'bg-white'"
                                    />
                                    <span class="flex flex-1 flex-col gap-1.5">
                                        <span
                                            class="h-4 rounded-md"
                                            :class="option.dark ? 'bg-[#1e293b]' : 'bg-white'"
                                        />
                                        <span class="flex flex-1 gap-1.5">
                                            <span
                                                class="flex-1 rounded-md"
                                                :class="option.dark ? 'bg-[#1e293b]' : 'bg-white'"
                                            />
                                            <span
                                                class="flex-1 rounded-md"
                                                :class="option.dark ? 'bg-[#1e293b]' : 'bg-white'"
                                            />
                                        </span>
                                    </span>
                                </span>

                                <span
                                    class="mt-2 flex items-center gap-2 px-1 pb-1 text-sm font-semibold"
                                    :class="
                                        isDark === option.dark
                                            ? 'text-primary dark:text-primary-300'
                                            : 'text-slate-600 dark:text-gray-300'
                                    "
                                >
                                    <component :is="option.icon" class="h-4 w-4" />
                                    {{ option.label }}
                                    <Check
                                        v-if="isDark === option.dark"
                                        class="ml-auto h-4 w-4"
                                    />
                                </span>
                            </button>
                        </div>
                    </ProfileSection>

                    <SoundSettings />
                </div>
            </template>
        </div>

        <AvatarCropModal
            :show="showAvatarCrop"
            :file="avatarSource"
            @close="showAvatarCrop = false"
            @apply="applyAvatarCrop"
        />
    </div>
</template>

<script setup lang="ts">
import {
    computed,
    defineAsyncComponent,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
} from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    Bell,
    Building2,
    CalendarClock,
    Camera,
    Check,
    CheckCheck,
    ChevronRight,
    ClipboardList,
    CreditCard,
    IdCard,
    KeyRound,
    LoaderCircle,
    Map as MapIcon,
    MapPin,
    MessageSquare,
    Moon,
    Palette,
    PencilLine,
    ShieldCheck,
    Sun,
    UserRound,
} from "lucide-vue-next";

import SoundSettings from "~/components/ui/SoundSettings.vue";
import AvatarCropModal from "~/components/ui/AvatarCropModal.vue";
import BaseInput from "~/components/ui/BaseInput.vue";
import DatePickerField from "~/components/ui/DatePickerField.vue";
import PhoneInput from "~/components/ui/PhoneInput.vue";
import ProfileTransactions from "~/components/sections/profile/ProfileTransactions.vue";
import ProfileSection from "~/components/sections/profile/ProfileSection.vue";
import { userService } from "~/api/user/UserService";
import { notificationService } from "~/api/notification/NotificationService";
import { useToast } from "~/composables/useToast";
import { fetchAuthUser, useAuthUser } from "~/composables/useAuthUser";
import { notifcationFormatDate } from "~/utils/notification-time";
import type { Notification } from "~/types/notification";
import {
    notificationAudience,
    resolveNotificationTarget,
} from "~/utils/notificationTarget";

// Loaded only in the browser (the map library can't run on the server).
const LocationSelector = defineAsyncComponent(
    () => import("~/components/ui/LocationSelector.vue"),
);

definePageMeta({
    middleware: [
        "auth-client",
        (to) => {
            const from = String(to.query.from ?? "");

            setPageLayout(
                ["dashboard", "owner", "portal"].includes(from)
                    ? (from as "dashboard" | "owner" | "portal")
                    : "default",
            );
        },
    ],
    navVariant: 3,
    theme: "light",
});

useHead({ title: "My Profile" });

const route = useRoute();
const router = useRouter();

const headerHasTitle = computed(() =>
    ["owner", "portal"].includes(String(route.query.from ?? "")),
);

const embedded = computed(() =>
    ["dashboard", "owner", "portal"].includes(String(route.query.from ?? "")),
);

const { success, error } = useToast();

const loading = ref(true);
const saving = ref(false);
const useMap = ref(false);

const tabs = [
    { label: "General", value: "profile", icon: UserRound },
    { label: "Notifications", value: "notifications", icon: Bell },
    { label: "Transactions", value: "transactions", icon: CreditCard },
    { label: "Appearance", value: "appearance", icon: Sun },
];

const validTabValues = tabs.map((tab) => tab.value);

const initialTab = validTabValues.includes(route.query.tab as string)
    ? (route.query.tab as string)
    : "profile";

const activeTab = ref(initialTab);

watch(activeTab, (value) => {
    router.replace({
        query: { ...route.query, tab: value },
    });
});

const isDark = useIsDark();

const themeOptions = [
    { label: "Light", dark: false, icon: Sun },
    { label: "Dark", dark: true, icon: Moon },
];

const preferences = reactive([
    {
        key: "schedule",
        label: "Schedule updates",
        description: "Changes to visits and shifts assigned to you.",
        enabled: true,
    },
    {
        key: "billing",
        label: "Billing notices",
        description: "Invoices, payments and subscription renewals.",
        enabled: true,
    },
    {
        key: "product",
        label: "Product news",
        description: "Feature announcements and tips from AMUMA.",
        enabled: false,
    },
]);

const avatarInput = ref<HTMLInputElement | null>(null);
const avatarFile = ref<File | null>(null);
const avatarPreview = ref<string | null>(null);
const avatarCleared = ref(false);
const avatarSource = ref<File | null>(null);
const showAvatarCrop = ref(false);

const errors = ref<Record<string, string>>({});

const roles = reactive({
    is_employee: false,
    is_client: false,
    is_family: false,
    is_system_owner: false,
});

const form = reactive({
    first_name: "",
    middle_name: "",
    last_name: "",
    email: "",
    phone_number: "",
    birth_date: "",
    occupation: "",

    street: "",
    city: "",
    province: "",
    country: "",
    latitude: 0,
    longitude: 0,

    current_password: "",
    password: "",
    password_confirmation: "",
});

const meta = reactive({
    provider: "local",
    uuid: "",
    created_at: "",
    has_password: true,
});

const original = ref<Record<string, any>>({});

const today = new Date().toISOString().slice(0, 10);

const canEditPhone = computed(() => true);
const canEditLocation = computed(
    () => roles.is_employee || roles.is_client || roles.is_system_owner,
);

const trackedKeys = [
    "first_name",
    "middle_name",
    "last_name",
    "email",
    "phone_number",
    "birth_date",
    "occupation",
    "street",
    "city",
    "province",
    "country",
    "latitude",
    "longitude",
] as const;

const isDirty = computed(() => {
    if (loading.value || !Object.keys(original.value).length) return false;

    return (
        Boolean(avatarFile.value) ||
        avatarCleared.value ||
        Boolean(form.password) ||
        trackedKeys.some((key) => original.value[key] !== form[key])
    );
});

const memberSince = computed(() => {
    if (!meta.created_at) return "—";

    try {
        return new Date(meta.created_at).toLocaleDateString("en-US", {
            month: "long",
            year: "numeric",
        });
    } catch {
        return "—";
    }
});

const signInMethods = computed(() => {
    const methods: string[] = [];

    if (meta.has_password) {
        methods.push("Email & password");
    }

    if (meta.provider && meta.provider !== "local") {
        methods.push(
            meta.provider.charAt(0).toUpperCase() + meta.provider.slice(1),
        );
    }

    return methods.length ? methods.join(", ") : "—";
});

// Shown on the profile card beside the form.
const displayName = computed(
    () => [form.first_name, form.last_name].filter(Boolean).join(" ") || "Your name",
);

const roleBadges = computed(() => {
    const list: string[] = [];
    if (roles.is_system_owner) list.push("AMUMA admin");
    if (roles.is_employee) list.push("Staff");
    if (roles.is_family) list.push("Family account");
    return list;
});

const accountRows = computed(() => [
    { label: "Member since", value: memberSince.value },
    { label: "Account ID", value: meta.uuid ? meta.uuid.split("-")[0] : "—" },
]);

const fallbackAvatar = computed(() => {
    const initials = `${form.first_name?.[0] ?? ""}${form.last_name?.[0] ?? ""}`;

    return `https://ui-avatars.com/api/?name=${encodeURIComponent(
        initials || "U",
    )}`;
});

const applyProfile = (data: any) => {
    form.first_name = data.first_name ?? "";
    form.middle_name = data.middle_name ?? "";
    form.last_name = data.last_name ?? "";
    form.email = data.email ?? "";
    form.phone_number = data.phone_number ?? "";
    form.occupation = data.occupation ?? "";
    form.birth_date = data.birth_date
        ? String(data.birth_date).slice(0, 10)
        : "";

    form.street = data.location?.street ?? "";
    form.city = data.location?.city ?? "";
    form.province = data.location?.province ?? "";
    form.country = data.location?.country ?? "";
    form.latitude = Number(data.location?.latitude) || 0;
    form.longitude = Number(data.location?.longitude) || 0;

    form.current_password = "";
    form.password = "";
    form.password_confirmation = "";

    roles.is_employee = Boolean(data.roles?.is_employee);
    roles.is_client = Boolean(data.roles?.is_client);
    roles.is_family = Boolean(data.roles?.is_family);
    roles.is_system_owner = Boolean(data.roles?.is_system_owner);

    meta.provider = data.provider ?? "local";
    meta.uuid = data.uuid ?? "";
    meta.created_at = data.created_at ?? "";
    meta.has_password = data.has_password !== false;

    avatarPreview.value = data.avatar ?? null;
    avatarCleared.value = false;

    original.value = Object.fromEntries(
        trackedKeys.map((key) => [key, form[key]]),
    );
};

const fetchProfile = async () => {
    loading.value = true;

    try {
        const res: any = await userService.profile();
        applyProfile(res?.data ?? res);
    } catch (err: any) {
        error(err?.message ?? "Failed to load your profile.");
    } finally {
        loading.value = false;
    }
};

const handleLocation = ({
    lat,
    lng,
    street,
    city,
    province,
    country,
}: {
    lat: number | null;
    lng: number | null;
    street: string;
    city: string;
    province: string;
    country: string;
}) => {
    form.street = street ?? "";
    form.city = city ?? "";
    form.province = province ?? "";
    form.country = country ?? "";
    form.latitude = lat ?? 0;
    form.longitude = lng ?? 0;

    ["street", "city", "province", "country"].forEach(clearError);
};

const handleAvatar = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    target.value = "";
    if (!file) return;

    if (!file.type.startsWith("image/")) {
        errors.value = { ...errors.value, avatar: "Choose an image file." };
        return;
    }

    avatarSource.value = file;
    showAvatarCrop.value = true;
};

const applyAvatarCrop = (file: File) => {
    if (avatarPreview.value?.startsWith("blob:")) {
        URL.revokeObjectURL(avatarPreview.value);
    }

    avatarFile.value = file;
    avatarCleared.value = false;
    avatarPreview.value = URL.createObjectURL(file);
    showAvatarCrop.value = false;
    clearError("avatar");
};

const removeAvatar = () => {
    avatarFile.value = null;
    avatarSource.value = null;
    avatarPreview.value = null;
    avatarCleared.value = true;

    if (avatarInput.value) {
        avatarInput.value.value = "";
    }
};

const clearError = (field: string) => {
    if (!errors.value[field]) return;

    const next = { ...errors.value };
    delete next[field];
    errors.value = next;
};

const reset = () => {
    Object.assign(form, original.value);

    form.current_password = "";
    form.password = "";
    form.password_confirmation = "";

    avatarFile.value = null;
    avatarCleared.value = false;
    errors.value = {};
};

const save = async () => {
    errors.value = {};

    if (form.password && form.password.length < 8) {
        errors.value = {
            password: "Password must be at least 8 characters.",
        };
        return;
    }

    saving.value = true;

    try {
        const payload: Record<string, any> = {
            first_name: form.first_name,
            middle_name: form.middle_name,
            last_name: form.last_name,
        };

        if (canEditPhone.value && form.phone_number) {
            payload.phone_number = form.phone_number;
        }

        if (roles.is_employee && form.birth_date) {
            payload.birth_date = form.birth_date;
        }

        if (roles.is_client && form.occupation) {
            payload.occupation = form.occupation;
        }

        if (canEditLocation.value) {
            Object.assign(payload, {
                street: form.street || undefined,
                city: form.city || undefined,
                province: form.province || undefined,
                country: form.country || undefined,
                latitude: form.latitude || undefined,
                longitude: form.longitude || undefined,
            });
        }

        if (form.password) {
            payload.password = form.password;
            payload.password_confirmation = form.password_confirmation;

            if (meta.has_password) {
                payload.current_password = form.current_password;
            }
        }

        if (avatarFile.value) {
            payload.avatar = avatarFile.value;
        }

        const res: any = await userService.updateProfile(payload);

        applyProfile(res?.data ?? res);
        avatarFile.value = null;
        await fetchAuthUser();
        success("Profile updated.");
    } catch (err: any) {
        const raw = err?.errors ?? {};

        errors.value = Object.fromEntries(
            Object.entries(raw).map(([key, value]: any) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ]),
        );

        error(err?.message ?? "Failed to update your profile.");
    } finally {
        saving.value = false;
    }
};

onMounted(fetchProfile);

// Notifications tab
const authUser = useAuthUser();
const { $echo } = useNuxtApp();

const notificationFilters = [
    { label: "All", value: "all" },
    { label: "Unread", value: "unread" },
] as const;

const notificationFilter = ref<"all" | "unread">("all");

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);

const notificationsLoading = ref(true);
const notificationsLoadingMore = ref(false);
const markingAllRead = ref(false);

const notificationsPage = ref(1);
const notificationsLastPage = ref(1);

let notificationChannel: any = null;
let notificationHandler: ((event: any) => void) | null = null;

const NOTIFICATION_TONES: Record<string, { icon: any; wrapper: string }> = {
    Booking: {
        icon: ClipboardList,
        wrapper:
            "bg-primary-50 text-primary dark:bg-primary-500/10 dark:text-primary-300",
    },
    Schedule: {
        icon: CalendarClock,
        wrapper:
            "bg-accent-50 text-accent-700 dark:bg-accent-500/10 dark:text-accent-300",
    },
    Billing: {
        icon: CreditCard,
        wrapper:
            "bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400",
    },
};

const failedNotificationImages = reactive(new Set<string | number>());

const notificationToneFor = (type: string) =>
    NOTIFICATION_TONES[type] ?? {
        icon: MessageSquare,
        wrapper:
            "bg-slate-100 text-slate-500 dark:bg-white/5 dark:text-gray-400",
    };

// Guards against a slow earlier page landing after a filter switch.
let notificationsRequestId = 0;

const fetchNotifications = async (page = 1) => {
    const thisRequest = ++notificationsRequestId;

    if (page === 1) notificationsLoading.value = true;
    else notificationsLoadingMore.value = true;

    try {
        const res: any = await notificationService.list({
            page,
            per_page: 15,
            unread_only: notificationFilter.value === "unread" ? 1 : undefined,
        });

        if (thisRequest !== notificationsRequestId) return;

        const rows: Notification[] = res?.data ?? [];

        notifications.value =
            page === 1 ? rows : [...notifications.value, ...rows];

        unreadCount.value = res?.meta?.unread_count ?? unreadCount.value;
        notificationsPage.value = res?.meta?.current_page ?? page;
        notificationsLastPage.value = res?.meta?.last_page ?? page;
    } catch (err: any) {
        if (thisRequest !== notificationsRequestId) return;

        console.error(err);
        error(err?.message ?? "Failed to load notifications.");
    } finally {
        if (thisRequest === notificationsRequestId) {
            notificationsLoading.value = false;
            notificationsLoadingMore.value = false;
        }
    }
};

const setNotificationFilter = (value: "all" | "unread") => {
    if (notificationFilter.value === value) return;

    notificationFilter.value = value;
    fetchNotifications(1);
};

const markNotificationRead = async (item: Notification) => {
    if (!item.unread) return;

    item.unread = false;
    unreadCount.value = Math.max(unreadCount.value - 1, 0);

    try {
        await notificationService.markRead(item.id);
    } catch (err: any) {
        item.unread = true;
        unreadCount.value += 1;
        error(err?.message ?? "Failed to mark as read.");
    }
};

// Marks it read, then opens the page it is about.
const openNotification = async (item: Notification) => {
    markNotificationRead(item);

    const audience = notificationAudience(
        item,
        route.path,
        String(route.query.from ?? ""),
        authUser.value,
    );
    const target = await resolveNotificationTarget(
        item,
        audience,
        typeof route.query.branch === "string" ? route.query.branch : null,
    );

    if (target) await router.push(target);
};

const markAllNotificationsRead = async () => {
    if (!unreadCount.value || markingAllRead.value) return;

    markingAllRead.value = true;

    try {
        await notificationService.markRead();

        notifications.value.forEach((n) => (n.unread = false));
        unreadCount.value = 0;

        // The unread tab's contents no longer match its filter.
        if (notificationFilter.value === "unread") {
            await fetchNotifications(1);
        }
    } catch (err: any) {
        error(err?.message ?? "Failed to mark all as read.");
    } finally {
        markingAllRead.value = false;
    }
};

const bindNotificationChannel = () => {
    const uuid = authUser.value?.uuid;

    if (notificationChannel && notificationHandler) {
        notificationChannel.stopListening(
            ".NotificationEvent",
            notificationHandler,
        );
        notificationChannel = null;
    }

    if (!uuid || !$echo) return;

    notificationHandler = (event: any) => {
        notifications.value.unshift({
            id: Date.now(),
            message: event.message,
            message_type: event.message_type,
            created_at: new Date().toISOString(),
            unread: true,
            branch: event.branch ?? null,
        } as Notification);

        unreadCount.value += 1;
    };

    notificationChannel = $echo
        .private(`Notification.${uuid}`)
        .listen(".NotificationEvent", notificationHandler);
};

watch(() => authUser.value?.uuid, bindNotificationChannel, {
    immediate: true,
});

onMounted(async () => {
    await fetchNotifications(1);
});

onBeforeUnmount(() => {
    if (!notificationChannel || !notificationHandler) return;

    notificationChannel.stopListening(
        ".NotificationEvent",
        notificationHandler,
    );
});
</script>

<style scoped>
/* Tabs scroll sideways on small screens without showing a bar. */
.profile-tabs {
    scrollbar-width: none;
}
.profile-tabs::-webkit-scrollbar {
    display: none;
}
</style>
