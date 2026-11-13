<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from "vue";
import L_module from "leaflet";
import {
    LocateFixed,
    Map as MapIcon,
    MapPin,
    Route as RouteIcon,
    Search,
    X,
} from "lucide-vue-next";

interface Location {
    lat: number;
    lng: number;
    label: string;
    street: string;
    city: string;
    province: string;
    country: string;
}

interface Suggestion {
    name: string;
    detail: string;
    type: string;
    lat: number;
    lng: number;
}

type Source = "search" | "map" | "drag" | "keyboard" | "gps";

const props = defineProps<{
    initialLat?: number;
    initialLng?: number;
    initialStreet?: string;
    initialCity?: string;
    initialProvince?: string;
    initialCountry?: string;
}>();

const emit = defineEmits<{
    (e: "location-selected", payload: Location): void;
    (e: "location-cleared"): void;
}>();

const MIN_QUERY = 3;
const DEBOUNCE_MS = 350;
const NUDGE_PX = 3;
const DEFAULT_CENTER: [number, number] = [7.0736, 125.611];
const AREA_TYPES = ["locality", "district", "city", "county", "state"];
const ZOOM_BY_TYPE: Record<string, number> = {
    house: 18,
    street: 17,
    locality: 16,
    district: 15,
    city: 13,
    county: 12,
    state: 10,
};
const SOURCE_LABEL: Record<Source, string> = {
    search: "via search",
    map: "via map click",
    drag: "via pin drag",
    keyboard: "via arrow keys",
    gps: "via my location",
};

const config = useRuntimeConfig();

const mapContainerEl = ref<HTMLElement | null>(null);
const inputEl = ref<HTMLInputElement | null>(null);

const query = ref("");
const suggestions = ref<Suggestion[]>([]);
const dropdownOpen = ref(false);
const activeIndex = ref(0);
const searching = ref(false);
const searchFailed = ref(false);
const inputFocused = ref(false);
const picked = ref(false);
const lastPickType = ref<string | null>(null);

const hasPin = ref(false);
const selectedLocation = ref<Location | null>(null);
const source = ref<Source | null>(null);
const resolving = ref(false);
const dragging = ref(false);
const noAddress = ref(false);
const lookupFailed = ref(false);
const locateError = ref("");

let L: any = null;
let map: L_module.Map | null = null;
let marker: L_module.Marker | null = null;
let mapClickHandler: ((e: any) => void) | null = null;
let searchTimer: ReturnType<typeof setTimeout> | undefined;
let nudgeTimer: ReturnType<typeof setTimeout> | undefined;
let searchAbort: AbortController | null = null;
let searchSeq = 0;
let geoSeq = 0;

const help = computed<{ tone: "muted" | "warn" | "info"; text: string }>(
    () => {
        const q = query.value.trim();

        if (locateError.value) return { tone: "warn", text: locateError.value };

        if (
            !hasPin.value &&
            q &&
            !picked.value &&
            !dropdownOpen.value &&
            !searching.value
        ) {
            return {
                tone: "warn",
                text: "Pick a suggestion to drop the pin. Typed text alone has no coordinates.",
            };
        }

        if (
            hasPin.value &&
            inputFocused.value &&
            selectedLocation.value &&
            q &&
            q !== selectedLocation.value.label &&
            !picked.value
        ) {
            return {
                tone: "info",
                text: "The pin stays where it is until you pick a suggestion.",
            };
        }

        if (hasPin.value && inputFocused.value && !q) {
            return {
                tone: "info",
                text: "Clearing the text keeps the pin. Pick a new suggestion to move it.",
            };
        }

        if (source.value === "search" && !resolving.value) {
            if (AREA_TYPES.includes(lastPickType.value ?? "")) {
                return {
                    tone: "info",
                    text: "This is the middle of the area. Drag the pin onto the exact spot.",
                };
            }

            if (lastPickType.value === "street") {
                return {
                    tone: "info",
                    text: "This is a point on the street. Drag the pin to the building's entrance.",
                };
            }
        }

        return {
            tone: "muted",
            text: "Search for a street, landmark or area, or click the map. Drag the pin to fine-tune.",
        };
    },
);

const helpClass = computed(
    () =>
        ({
            muted: "text-slate-500 dark:text-gray-400",
            warn: "text-amber-600 dark:text-amber-300",
            info: "text-primary",
        })[help.value.tone],
);

function iconFor(type: string) {
    if (type === "street") return RouteIcon;
    if (AREA_TYPES.includes(type)) return MapIcon;
    return MapPin;
}

function kindLabel(type: string) {
    if (type === "house") return "Address";
    if (type === "street") return "Street";
    if (type === "locality" || type === "district") return "Area";
    if (type === "city" || type === "county") return "City";
    if (type === "state") return "Province";
    return "Place";
}

function highlightParts(name: string) {
    const tokens = query.value
        .trim()
        .split(/[\s,]+/)
        .filter((token) => token.length > 1)
        .map((token) => token.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"));

    if (!tokens.length) return [{ text: name, match: false }];

    const pattern = new RegExp(`(${tokens.join("|")})`, "gi");

    return name
        .split(pattern)
        .filter(Boolean)
        .map((text) => ({
            text,
            match: tokens.some((token) =>
                new RegExp(`^${token}$`, "i").test(text),
            ),
        }));
}

function cancelSearch() {
    clearTimeout(searchTimer);
    searchAbort?.abort();
    searchAbort = null;
    searchSeq++;
    searching.value = false;
}

function onSearchInput(event: Event) {
    query.value = (event.target as HTMLInputElement).value;
    picked.value = false;
    searchFailed.value = false;
    locateError.value = "";
    cancelSearch();

    const q = query.value.trim();

    if (q.length < MIN_QUERY) {
        suggestions.value = [];
        dropdownOpen.value = false;
        return;
    }

    const seq = searchSeq;
    searching.value = true;
    searchTimer = setTimeout(() => fetchSuggestions(q, seq), DEBOUNCE_MS);
}

async function fetchSuggestions(q: string, seq: number) {
    const controller = new AbortController();
    searchAbort = controller;

    const params = new URLSearchParams({ q });
    const center = map?.getCenter();

    if (center) {
        params.set("lat", center.lat.toFixed(4));
        params.set("lng", center.lng.toFixed(4));
    }

    try {
        const res = await fetch(
            `${config.public.backendApi}/api/geocode/suggest?${params}`,
            {
                headers: { Accept: "application/json" },
                signal: controller.signal,
            },
        );
        const body = res.ok ? await res.json() : null;

        if (seq !== searchSeq) return;

        searchFailed.value = !body?.success;
        suggestions.value = body?.success ? (body.data ?? []) : [];
        activeIndex.value = 0;
        dropdownOpen.value = inputFocused.value;
    } catch (error: any) {
        if (error?.name === "AbortError" || seq !== searchSeq) return;

        console.error("[suggest] Failed:", error);
        searchFailed.value = true;
        suggestions.value = [];
        dropdownOpen.value = inputFocused.value;
    } finally {
        if (seq === searchSeq) searching.value = false;
    }
}

function onSearchFocus() {
    inputFocused.value = true;

    if (
        !picked.value &&
        query.value.trim().length >= MIN_QUERY &&
        query.value !== selectedLocation.value?.label &&
        (suggestions.value.length || searchFailed.value)
    ) {
        dropdownOpen.value = true;
    }
}

function onSearchBlur() {
    inputFocused.value = false;
    dropdownOpen.value = false;
    cancelSearch();

    if (
        selectedLocation.value &&
        !resolving.value &&
        query.value !== selectedLocation.value.label
    ) {
        query.value = selectedLocation.value.label;
    }
}

function onSearchKeydown(event: KeyboardEvent) {
    const count = suggestions.value.length;

    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
        if (!dropdownOpen.value || !count) return;

        event.preventDefault();
        const step = event.key === "ArrowDown" ? 1 : count - 1;
        activeIndex.value = (activeIndex.value + step) % count;
        return;
    }

    if (event.key === "Enter") {
        event.preventDefault();
        const choice = suggestions.value[activeIndex.value];

        if (dropdownOpen.value && choice) pickSuggestion(choice);
        return;
    }

    if (event.key === "Escape") {
        dropdownOpen.value = false;
    }
}

function clearSearch() {
    cancelSearch();
    query.value = "";
    suggestions.value = [];
    dropdownOpen.value = false;
    picked.value = false;
    inputEl.value?.focus();
}

function pickSuggestion(suggestion: Suggestion) {
    cancelSearch();
    dropdownOpen.value = false;
    picked.value = true;
    lastPickType.value = suggestion.type;
    query.value = suggestion.name;

    map?.setView(
        [suggestion.lat, suggestion.lng],
        ZOOM_BY_TYPE[suggestion.type] ?? 18,
    );
    movePin(suggestion.lat, suggestion.lng, "search");
}

function dropSelection() {
    if (!selectedLocation.value) return;

    selectedLocation.value = null;
    emit("location-cleared");
}

function movePin(lat: number, lng: number, from: Source) {
    source.value = from;
    locateError.value = "";

    if (from !== "search") lastPickType.value = null;

    placeMarker(lat, lng);
    resolveAddress(lat, lng, from);
}

async function resolveAddress(
    lat: number,
    lng: number,
    from: Source | null,
    silent = false,
) {
    const seq = ++geoSeq;

    resolving.value = true;
    noAddress.value = false;
    lookupFailed.value = false;

    if (!silent) dropSelection();

    const address = await reverseGeocode(lat, lng);

    if (seq !== geoSeq) return;

    resolving.value = false;

    const keepTyping = inputFocused.value && from !== "search";

    if (!address) {
        lookupFailed.value = true;
        return;
    }

    if (!address.city && !address.province) {
        noAddress.value = true;
        if (!keepTyping) query.value = "";
        return;
    }

    selectedLocation.value = address;
    if (!keepTyping) query.value = address.label;
    if (!silent) emit("location-selected", { ...address });
}

function retryLookup() {
    const pos = marker?.getLatLng();
    if (pos) resolveAddress(pos.lat, pos.lng, source.value);
}

async function reverseGeocode(
    lat: number,
    lng: number,
): Promise<Location | null> {
    try {
        const res = await fetch(
            `${config.public.backendApi}/api/reverse-geocode?lat=${lat}&lon=${lng}`,
            {
                headers: {
                    Accept: "application/json",
                    "Accept-Language": "en",
                },
            },
        );

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}: ${await res.text()}`);
        }

        const response = await res.json();
        const addr = response?.data?.address ?? {};

        const road = [
            addr.house_number,
            addr.road ??
                addr.pedestrian ??
                addr.footway ??
                addr.path ??
                addr.cycleway ??
                addr.track ??
                addr.residential ??
                addr.service ??
                addr.unclassified ??
                addr.tertiary ??
                addr.secondary ??
                addr.primary ??
                addr.trunk ??
                addr.amenity,
        ]
            .filter(Boolean)
            .join(" ");

        const city =
            addr.city ??
            addr.town ??
            addr.municipality ??
            addr.village ??
            addr.suburb ??
            "";

        const locality = [
            addr.neighbourhood,
            addr.quarter,
            addr.suburb,
            addr.hamlet,
            addr.village,
        ].find((part) => part && part !== city);

        const street = [locality, road].filter(Boolean).join(", ");
        const province = addr.state ?? addr.province ?? addr.region ?? "";
        const country = addr.country ?? "";

        return {
            lat,
            lng,
            label: [street, city, province, country].filter(Boolean).join(", "),
            street,
            city,
            province,
            country,
        };
    } catch (error) {
        console.error("[reverseGeocode] Failed:", error);
        return null;
    }
}

function startMove() {
    geoSeq++;
    dragging.value = true;
    resolving.value = false;
    noAddress.value = false;
    lookupFailed.value = false;
    dropdownOpen.value = false;
    dropSelection();
}

function onMarkerKeydown(event: KeyboardEvent) {
    const offsets: Record<string, [number, number]> = {
        ArrowUp: [0, -NUDGE_PX],
        ArrowDown: [0, NUDGE_PX],
        ArrowLeft: [-NUDGE_PX, 0],
        ArrowRight: [NUDGE_PX, 0],
    };
    const offset = offsets[event.key];

    if (!offset || !map || !marker) return;

    event.preventDefault();
    event.stopPropagation();

    const point = map
        .latLngToContainerPoint(marker.getLatLng())
        .add(L.point(offset[0], offset[1]));
    const next = map.containerPointToLatLng(point);

    if (!nudgeTimer) startMove();

    marker.setLatLng(next);
    clearTimeout(nudgeTimer);
    nudgeTimer = setTimeout(() => {
        nudgeTimer = undefined;
        dragging.value = false;
        movePin(next.lat, next.lng, "keyboard");
    }, 400);
}

function placeMarker(lat: number, lng: number) {
    if (!map) return;

    hasPin.value = true;

    if (marker) {
        marker.setLatLng([lat, lng]);
        return;
    }

    marker = L.marker([lat, lng], { draggable: true }).addTo(map);

    marker!.on("dragstart", startMove);
    marker!.on("dragend", (e: any) => {
        const pos = e.target.getLatLng();
        dragging.value = false;
        movePin(pos.lat, pos.lng, "drag");
    });
    marker!.getElement()?.addEventListener("keydown", onMarkerKeydown);
}

function useMyLocation() {
    locateError.value = "";

    if (!navigator.geolocation) {
        locateError.value =
            "This browser can't share your location. Search or click the map instead.";
        return;
    }

    navigator.geolocation.getCurrentPosition(
        ({ coords: { latitude, longitude } }) => {
            map?.setView([latitude, longitude], 17);
            movePin(latitude, longitude, "gps");
        },
        (error) => {
            console.warn("Location unavailable:", error?.message ?? error);
            locateError.value =
                "We couldn't get your location. Search or click the map instead.";
        },
        { enableHighAccuracy: true },
    );
}

function defaultView(): [number, number] {
    return props.initialLat && props.initialLng
        ? [props.initialLat, props.initialLng]
        : DEFAULT_CENTER;
}

const clearSelection = (): void => {
    geoSeq++;
    cancelSearch();
    clearTimeout(nudgeTimer);
    nudgeTimer = undefined;

    marker?.remove();
    marker = null;

    hasPin.value = false;
    selectedLocation.value = null;
    source.value = null;
    resolving.value = false;
    dragging.value = false;
    noAddress.value = false;
    lookupFailed.value = false;
    locateError.value = "";
    lastPickType.value = null;
    picked.value = false;
    query.value = "";
    suggestions.value = [];
    dropdownOpen.value = false;

    nextTick(() => {
        map?.invalidateSize();
        map?.setView(defaultView(), 13);
    });

    emit("location-cleared");
};

defineExpose({ clearSelection });

watch(
    () => [props.initialLat, props.initialLng] as const,
    ([lat, lng]) => {
        if (!map || lat == null || lng == null || hasPin.value) return;

        map.setView([lat, lng], 16);
        placeMarker(lat, lng);
        resolveAddress(lat, lng, null, true);
    },
);

onMounted(async () => {
    L = (L_module as any).default ?? L_module;

    await import("leaflet/dist/leaflet.css");
    await nextTick();

    if (!mapContainerEl.value) return;

    delete (L.Icon.Default.prototype as any)._getIconUrl;

    L.Icon.Default.mergeOptions({
        iconRetinaUrl:
            "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",
        iconUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",
        shadowUrl:
            "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
    });

    map = L.map(mapContainerEl.value, {
        attributionControl: false,
        zoomControl: false,
    }).setView(defaultView(), props.initialLat && props.initialLng ? 16 : 13);

    L.control.zoom({ position: "topright" }).addTo(map);
    L.control.scale({ position: "bottomright", imperial: false }).addTo(map);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap contributors",
    }).addTo(map);

    requestAnimationFrame(() => map?.invalidateSize());
    setTimeout(() => map?.invalidateSize(), 300);

    const initialParts = [
        props.initialStreet,
        props.initialCity,
        props.initialProvince,
        props.initialCountry,
    ].filter(Boolean) as string[];

    if (props.initialLat && props.initialLng) {
        placeMarker(props.initialLat, props.initialLng);

        if (props.initialCity || props.initialProvince) {
            selectedLocation.value = {
                lat: props.initialLat,
                lng: props.initialLng,
                label: initialParts.join(", "),
                street: props.initialStreet ?? "",
                city: props.initialCity ?? "",
                province: props.initialProvince ?? "",
                country: props.initialCountry ?? "",
            };
            query.value = selectedLocation.value.label;
        } else {
            resolveAddress(props.initialLat, props.initialLng, null, true);
        }
    } else if (initialParts.length) {
        query.value =
            props.initialCity &&
            props.initialStreet?.includes(props.initialCity)
                ? props.initialStreet
                : initialParts.join(", ");
    }

    mapClickHandler = (e: any) => {
        dropdownOpen.value = false;
        movePin(e.latlng.lat, e.latlng.lng, "map");
    };

    map?.on("click", mapClickHandler);
});

onUnmounted(() => {
    cancelSearch();
    clearTimeout(nudgeTimer);

    if (map && mapClickHandler) {
        map.off("click", mapClickHandler);
        mapClickHandler = null;
    }

    marker?.getElement()?.removeEventListener("keydown", onMarkerKeydown);

    map?.remove();
    map = null;
    marker = null;
});
</script>

<template>
    <div class="flex w-full flex-col gap-2">
        <div class="relative z-30">
            <div
                class="flex h-11 items-center gap-2.5 rounded-lg border-[1.5px] border-slate-200 bg-white px-3 transition focus-within:border-blue-500 focus-within:ring-2 focus-within:ring-blue-500/15 dark:border-white/10 dark:bg-secondary"
            >
                <Search
                    class="h-4 w-4 shrink-0 text-slate-400 dark:text-gray-500"
                />

                <input
                    ref="inputEl"
                    :value="query"
                    type="text"
                    autocomplete="off"
                    spellcheck="false"
                    role="combobox"
                    aria-autocomplete="list"
                    :aria-expanded="dropdownOpen"
                    placeholder="Search a street, landmark or area"
                    class="min-w-0 flex-1 bg-transparent text-sm text-slate-800 outline-none placeholder:text-slate-400 dark:text-white dark:placeholder:text-gray-500"
                    @input="onSearchInput"
                    @focus="onSearchFocus"
                    @blur="onSearchBlur"
                    @keydown="onSearchKeydown"
                />

                <span
                    v-if="searching"
                    class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-slate-200 border-t-primary dark:border-white/10 dark:border-t-primary"
                />

                <button
                    v-else-if="query"
                    type="button"
                    aria-label="Clear search text"
                    class="shrink-0 rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                    @mousedown.prevent
                    @click="clearSearch"
                >
                    <X class="h-3.5 w-3.5" />
                </button>
            </div>

            <div
                v-if="dropdownOpen"
                role="listbox"
                class="absolute inset-x-0 top-full mt-1.5 max-h-80 overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg dark:border-white/10 dark:bg-secondary"
                @mousedown.prevent
            >
                <p
                    v-if="searchFailed"
                    class="px-3 py-2.5 text-sm text-slate-500 dark:text-gray-400"
                >
                    We couldn't load suggestions. Try again, or click the map.
                </p>

                <p
                    v-else-if="!suggestions.length"
                    class="px-3 py-2.5 text-sm text-slate-500 dark:text-gray-400"
                >
                    No matches for “{{ query.trim() }}”. Try a landmark or
                    street name, or click the map.
                </p>

                <template v-else>
                <button
                    v-for="(suggestion, index) in suggestions"
                    :key="`${suggestion.name}-${suggestion.lat}-${suggestion.lng}`"
                    type="button"
                    role="option"
                    :aria-selected="index === activeIndex"
                    class="flex w-full items-start gap-2.5 rounded-lg px-2.5 py-2 text-left transition"
                    :class="
                        index === activeIndex
                            ? 'bg-primary-50 dark:bg-primary-500/10'
                            : 'hover:bg-slate-50 dark:hover:bg-white/5'
                    "
                    @mouseenter="activeIndex = index"
                    @click="pickSuggestion(suggestion)"
                >
                    <span
                        class="grid h-7 w-7 shrink-0 place-items-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400"
                    >
                        <component
                            :is="iconFor(suggestion.type)"
                            class="h-3.5 w-3.5"
                        />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span
                            class="block text-sm font-medium text-slate-800 dark:text-white"
                        >
                            <template
                                v-for="(part, partIndex) in highlightParts(
                                    suggestion.name,
                                )"
                                :key="partIndex"
                            >
                                <mark
                                    v-if="part.match"
                                    class="bg-transparent font-bold text-primary"
                                    >{{ part.text }}</mark
                                >
                                <template v-else>{{ part.text }}</template>
                            </template>
                        </span>
                        <span
                            v-if="suggestion.detail"
                            class="block truncate text-xs text-slate-500 dark:text-gray-400"
                        >
                            {{ suggestion.detail }}
                        </span>
                    </span>

                    <span
                        class="shrink-0 pt-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-gray-500"
                    >
                        {{ kindLabel(suggestion.type) }}
                    </span>
                </button>
                </template>
            </div>
        </div>

        <p class="text-xs" :class="helpClass">{{ help.text }}</p>

        <div class="relative">
            <div
                ref="mapContainerEl"
                class="relative z-0 isolate h-[400px] w-full overflow-hidden rounded-xl border border-gray-200 shadow-sm dark:border-white/10"
            />

            <button
                type="button"
                class="absolute left-2.5 top-2.5 z-10 inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-white/10 dark:bg-secondary dark:text-gray-200 dark:hover:bg-white/5"
                @click="useMyLocation"
            >
                <LocateFixed class="h-4 w-4 text-primary" />
                My location
            </button>
        </div>

        <div
            class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 dark:border-white/10 dark:bg-white/5"
        >
            <span
                class="grid h-8 w-8 shrink-0 place-items-center rounded-lg"
                :class="
                    !hasPin
                        ? 'border border-slate-200 bg-white text-slate-400 dark:border-white/10 dark:bg-secondary dark:text-gray-500'
                        : noAddress || lookupFailed
                          ? 'bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400'
                          : 'bg-primary-50 text-primary dark:bg-primary-500/10'
                "
            >
                <MapPin class="h-4 w-4" />
            </span>

            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                <template v-if="!hasPin">
                    <span
                        class="text-sm font-medium text-slate-800 dark:text-white"
                    >
                        No pin yet
                    </span>
                    <span class="text-xs text-slate-500 dark:text-gray-400">
                        The address appears here once you pick a suggestion
                        or click the map.
                    </span>
                </template>

                <template v-else>
                    <span
                        class="break-words text-sm font-medium text-slate-800 dark:text-white"
                    >
                        <template v-if="dragging">Moving pin…</template>
                        <span
                            v-else-if="resolving"
                            class="inline-flex items-center gap-2"
                        >
                            <span
                                class="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-primary dark:border-white/10 dark:border-t-primary"
                            />
                            Looking up address…
                        </span>
                        <template v-else-if="noAddress">
                            No address at this spot
                        </template>
                        <template v-else-if="lookupFailed">
                            We couldn't look up this address
                        </template>
                        <template v-else>
                            {{ selectedLocation?.label }}
                        </template>
                    </span>

                    <span
                        class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs"
                    >
                        <span
                            v-if="source && selectedLocation && !resolving && !dragging"
                            class="rounded-full border border-slate-200 bg-white px-2 py-0.5 font-medium text-slate-500 dark:border-white/10 dark:bg-secondary dark:text-gray-400"
                        >
                            {{ SOURCE_LABEL[source] }}
                        </span>
                        <button
                            v-if="lookupFailed"
                            type="button"
                            class="font-medium text-primary hover:underline"
                            @click="retryLookup"
                        >
                            Try again
                        </button>
                        <button
                            type="button"
                            class="font-medium text-primary hover:underline"
                            @click="clearSelection"
                        >
                            Remove pin
                        </button>
                    </span>

                    <span
                        v-if="dragging"
                        class="text-xs text-slate-500 dark:text-gray-400"
                    >
                        Let go to look up the address.
                    </span>
                    <span
                        v-else-if="noAddress"
                        class="text-xs text-red-500 dark:text-red-400"
                    >
                        Move the pin onto land or closer to a street.
                    </span>
                </template>
            </div>
        </div>
    </div>
</template>
