<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from "vue";
import L_module from "leaflet";
import BaseInput from "~/components/ui/BaseInput.vue";

interface Location {
    lat: number | null;
    lng: number | null;
    label: string;
    street: string;
    city: string;
    province: string;
    country: string;
}

const props = withDefaults(
    defineProps<{
        initialLat?: number;
        initialLng?: number;
        initialStreet?: string;
        initialCity?: string;
        initialProvince?: string;
        initialCountry?: string;
        mode?: "map" | "type";
        manualFallback?: boolean;
    }>(),
    {
        mode: "map",
        manualFallback: false,
    },
);

const emit = defineEmits<{
    (e: "location-selected", payload: Location): void;
    (e: "location-cleared"): void;
}>();

const selectedLocation = ref<Location | null>(null);
const mapContainerEl = ref<HTMLElement | null>(null);
const activeMode = ref<"map" | "type">(props.mode);

const typedAddress = ref("");
const isLocating = ref(false);
const typeError = ref("");

const manual = ref(false);
const manualAddress = ref("");
const manualError = ref("");

function fallBackToManual(text: string): boolean {
    if (!props.manualFallback) return false;

    manual.value = true;
    manualAddress.value = text;
    manualError.value =
        "We couldn't find that location. Type the full address instead.";
    selectedLocation.value = null;
    marker?.remove();
    marker = null;
    emit("location-cleared");

    return true;
}

async function useMap() {
    manual.value = false;
    manualError.value = "";
    activeMode.value = "map";

    await nextTick();
    map?.invalidateSize();
}

function applyManualAddress(showError: boolean) {
    const text = manualAddress.value.trim();
    const parts = text
        .split(",")
        .map((part) => part.trim())
        .filter(Boolean);

    const country =
        parts.length > 3 && /^(ph|philippines)$/i.test(parts[parts.length - 1]!)
            ? parts.pop()!
            : "Philippines";
    const province = parts.length >= 3 ? parts.pop()! : "";
    const city = parts.length >= 2 ? parts.pop()! : "";
    const street = parts.join(", ");

    if (!street || !city || !province) {
        manualError.value = showError
            ? "Separate the street, city and province with commas, e.g. 123 Rizal St, Davao City, Davao del Sur."
            : "";

        if (selectedLocation.value) {
            selectedLocation.value = null;
            emit("location-cleared");
        }

        return;
    }

    manualError.value = "";

    selectedLocation.value = {
        lat: null,
        lng: null,
        label: text,
        street,
        city,
        province,
        country,
    };

    confirmLocation();
}

function onManualInput(value: string | number) {
    manualAddress.value = String(value);
    applyManualAddress(false);
}

watch(
    () => props.mode,
    (next) => {
        activeMode.value = next;
    },
);

watch(activeMode, (next) => {
    typeError.value = "";
    manual.value = false;

    if (next === "type") {
        typedAddress.value = selectedLocation.value?.label ?? "";

        return;
    }

    nextTick(() => {
        map?.invalidateSize();

        if (!selectedLocation.value) {
            map?.setView(defaultView(), 13);
        }
    });
});

function switchMode(next: "map" | "type") {
    activeMode.value = next;
}

async function applyTypedAddress() {
    const query = typedAddress.value.trim();

    if (!query) {
        typeError.value = "Enter an address.";
        return;
    }

    if (selectedLocation.value && query === selectedLocation.value.label) {
        confirmLocation();
        return;
    }

    isLocating.value = true;
    typeError.value = "";

    try {
        const config = useRuntimeConfig();

        const res = await fetch(
            `${config.public.backendApi}/api/geocode?q=${encodeURIComponent(query)}`,
            { headers: { Accept: "application/json" } },
        );

        const data = res.ok ? await res.json() : null;
        const lat = Number(data?.lat);
        const lng = Number(data?.lng);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            if (fallBackToManual(query)) return;

            typeError.value =
                "We couldn't find that address. Try adding the city, or pick it on the map.";
            return;
        }

        map?.setView([lat, lng], 16);
        placeMarker(lat, lng);

        const found = await reverseGeocode(lat, lng);

        if (!found && fallBackToManual(query)) {
            return;
        }

        confirmLocation();
    } catch (error) {
        console.error("[geocode] Failed:", error);

        if (fallBackToManual(query)) return;

        typeError.value = "We couldn't look up that address right now.";
    } finally {
        isLocating.value = false;
    }
}

const DEFAULT_CENTER: [number, number] = [7.0736, 125.611];

function defaultView(): [number, number] {
    return props.initialLat && props.initialLng
        ? [props.initialLat, props.initialLng]
        : DEFAULT_CENTER;
}

let map: L_module.Map | null = null;
let marker: L_module.Marker | null = null;
let userMarker: L_module.CircleMarker | null = null;
let mapClickHandler: ((e: any) => void) | null = null;

const handleLocation = async (lat: number, lng: number): Promise<void> => {
    selectedLocation.value = {
        lat,
        lng,
        label: "Loading...",
        street: "",
        city: "",
        province: "",
        country: "",
    };

    const found = await reverseGeocode(lat, lng);

    if (!found && fallBackToManual("")) {
        return;
    }

    confirmLocation();
};

watch(
    () => [props.initialLat, props.initialLng] as const,
    async ([lat, lng]) => {
        if (!map || lat == null || lng == null) return;
        if (selectedLocation.value) return;
        map.setView([lat, lng], 15);
        placeMarker(lat, lng);
        await reverseGeocode(lat, lng);
    },
);

const reverseGeocode = async (lat: number, lng: number): Promise<boolean> => {
    try {
        const config = useRuntimeConfig();
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

        const data = response?.data ?? {};
        const addr = data.address ?? {};
        const displayName: string = data.display_name ?? "";

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

        const labelParts = [street, city, province, country].filter(Boolean);

        const hasEnoughParts = !!(city || street);
        const label = hasEnoughParts
            ? labelParts.join(", ")
            : displayName || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;

        selectedLocation.value = {
            lat,
            lng,
            label,
            street,
            city,
            province,
            country,
        };
    } catch (error) {
        console.error("[reverseGeocode] Failed:", error);

        selectedLocation.value = {
            lat,
            lng,
            label: `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
            street: "",
            city: "",
            province: "",
            country: "",
        };

        return false;
    }

    return true;
};

const confirmLocation = (): void => {
    if (!selectedLocation.value) return;
    emit("location-selected", { ...selectedLocation.value });
};

const placeMarker = (lat: number, lng: number): void => {
    const L = (window as any).L;

    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], {
            draggable: true,
        }).addTo(map!);

        marker?.on("dragend", async (e: any) => {
            const pos = e.target.getLatLng();
            await handleLocation(pos.lat, pos.lng);
        });
    }
};

const currentPosition = (): Promise<GeolocationPosition> =>
    new Promise((resolve, reject) =>
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
        }),
    );

const useMyLocation = async (): Promise<void> => {
    if (!navigator.geolocation) {
        alert("Geolocation is not supported.");
        return;
    }

    try {
        const {
            coords: { latitude, longitude },
        } = await currentPosition();

        map?.setView([latitude, longitude], 16);

        placeMarker(latitude, longitude);
        await handleLocation(latitude, longitude);

        const L = (window as any).L;

        if (userMarker) userMarker.remove();

        userMarker = L.circleMarker([latitude, longitude], {
            radius: 8,
            fillColor: "#3b82f6",
            color: "#fff",
            weight: 2,
            opacity: 1,
            fillOpacity: 0.9,
        })
            .addTo(map!)
            .bindPopup("<b>You are here</b>")
            .openPopup();
    } catch (error: any) {
        console.warn("Location unavailable:", error?.message ?? error);
    }
};

const clearSelection = (): void => {
    if (marker) {
        marker.remove();
        marker = null;
    }

    if (userMarker) {
        userMarker.remove();
        userMarker = null;
    }

    selectedLocation.value = null;
    typedAddress.value = "";
    typeError.value = "";
    manual.value = false;
    manualAddress.value = "";
    manualError.value = "";

    nextTick(() => {
        map?.invalidateSize();
        map?.setView(defaultView(), 13);
    });

    emit("location-cleared");
};

defineExpose({ clearSelection });

onMounted(async () => {
    const L = (L_module as any).default ?? L_module;
    (window as any).L = L;

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

    map = L.map(mapContainerEl.value, { attributionControl: false }).setView(
        defaultView(),
        13,
    );

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap contributors",
    }).addTo(map);

    requestAnimationFrame(() => map?.invalidateSize());
    setTimeout(() => map?.invalidateSize(), 300);

    if (
        props.manualFallback &&
        !(props.initialLat && props.initialLng) &&
        props.initialStreet
    ) {
        manual.value = true;
        manualAddress.value =
            props.initialCity && props.initialStreet.includes(props.initialCity)
                ? props.initialStreet
                : [
                      props.initialStreet,
                      props.initialCity,
                      props.initialProvince,
                  ]
                      .filter(Boolean)
                      .join(", ");
    }

    if (props.initialLat && props.initialLng) {
        placeMarker(props.initialLat, props.initialLng);

        if (
            props.initialStreet ||
            props.initialCity ||
            props.initialProvince ||
            props.initialCountry
        ) {
            const labelParts = [
                props.initialStreet,
                props.initialCity,
                props.initialProvince,
                props.initialCountry,
            ].filter(Boolean);

            selectedLocation.value = {
                lat: props.initialLat,
                lng: props.initialLng,
                label: labelParts.join(", "),
                street: props.initialStreet ?? "",
                city: props.initialCity ?? "",
                province: props.initialProvince ?? "",
                country: props.initialCountry ?? "",
            };
        } else {
            await reverseGeocode(props.initialLat, props.initialLng);
        }
    }

    mapClickHandler = async (e: any) => {
        placeMarker(e.latlng.lat, e.latlng.lng);
        await handleLocation(e.latlng.lat, e.latlng.lng);
    };

    map?.on("click", mapClickHandler);
});

onUnmounted(() => {
    if (map && mapClickHandler) {
        map.off("click", mapClickHandler);
        mapClickHandler = null;
    }

    if (map) {
        map.remove();
        map = null;
    }

    marker = null;
    userMarker = null;
});
</script>
<template>
    <div class="flex flex-col gap-2 w-full z-20">
        <BaseInput
            v-if="manual"
            :model-value="manualAddress"
            label="Location"
            @update:model-value="onManualInput"
            placeholder="House/unit no., street, barangay, city, province"
            :error="manualError"
            @focusout="applyManualAddress(true)"
            @keydown.enter.prevent="applyManualAddress(true)"
        />

        <button
            v-if="manual"
            type="button"
            class="self-start text-xs font-medium text-primary hover:underline"
            @click="useMap"
        >
            Pick on the map instead
        </button>

        <div
            v-show="!manual"
            class="flex items-start gap-2 bg-white dark:bg-secondary border border-gray-200 dark:border-white/10 rounded-xl px-3 py-2 text-sm shadow-sm min-h-[48px]"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-4 h-4 text-gray-400 dark:text-gray-500 shrink-0 mt-2"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2zm0 0v8m0 0C8 14 5 11.314 5 9a7 7 0 1114 0c0 2.314-3 5-7 10z"
                />
            </svg>

            <span
                class="text-gray-600 dark:text-gray-300 flex-1 whitespace-normal break-words mt-1"
            >
                {{
                    selectedLocation?.label ||
                    (activeMode === "map"
                        ? "Click the map to select a location"
                        : "Type the address below")
                }}
            </span>

            <div class="inline-flex shrink-0 rounded-lg border border-gray-200 p-0.5 dark:border-white/10">
                <button
                    type="button"
                    @click="switchMode('map')"
                    :class="[
                        'rounded px-2.5 py-1 text-xs font-medium transition-colors',
                        activeMode === 'map'
                            ? 'bg-primary text-white'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                    ]"
                >
                    Map
                </button>

                <button
                    type="button"
                    @click="switchMode('type')"
                    :class="[
                        'rounded px-2.5 py-1 text-xs font-medium transition-colors',
                        activeMode === 'type'
                            ? 'bg-primary text-white'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                    ]"
                >
                    Type
                </button>
            </div>

            <button
                v-if="selectedLocation"
                type="button"
                @click="clearSelection"
                class="text-gray-400 dark:text-gray-500 hover:text-red-500 transition-colors shrink-0"
                title="Clear"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4 mt-2"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <div
            v-show="!manual && activeMode === 'type'"
            class="flex flex-col gap-2 sm:flex-row"
        >
            <BaseInput
                v-model="typedAddress"
                class="flex-1"
                placeholder="Type this address and click &quot;Use this address&quot; to generate the location"
                :error="typeError"
                @keydown.enter.prevent="applyTypedAddress"
            />

            <button
                type="button"
                :disabled="isLocating"
                class="h-fit rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:cursor-wait disabled:opacity-60"
                @click="applyTypedAddress"
            >
                {{ isLocating ? "Locating..." : "Use this address" }}
            </button>
        </div>

        <div
            v-show="!manual && (activeMode === 'map' || selectedLocation)"
            ref="mapContainerEl"
            class="w-full h-[400px] z-20 rounded-xl overflow-hidden border border-gray-200 dark:border-white/10 shadow-sm"
        />

        <div v-show="!manual && activeMode === 'map'" class="flex gap-2">
            <button
                type="button"
                @click="useMyLocation"
                class="flex items-center gap-2 px-5 py-2 text-sm bg-white dark:bg-secondary dark:text-white border border-gray-200 dark:border-white/10 rounded-xl hover:bg-gray-50 dark:hover:bg-white/5 transition-colors shadow-sm"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4 text-blue-500 dark:text-blue-300"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 2a10 10 0 100 20A10 10 0 0012 2zm0 0v4m0 12v4M2 12h4m12 0h4"
                    />
                </svg>

                My Location
            </button>
        </div>
    </div>
</template>
