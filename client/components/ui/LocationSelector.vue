<script setup lang="ts">
import { ref, onMounted, onUnmounted } from "vue";
import L_module from "leaflet";

interface Location {
    lat: number;
    lng: number;
    label: string;
    street: string;
    city: string;
    province: string;
    country: string;
}

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
}>();

const selectedLocation = ref<Location | null>(null);

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

    await reverseGeocode(lat, lng);
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

const reverseGeocode = async (lat: number, lng: number): Promise<void> => {
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

        const street = [
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
                addr.suburb ??
                addr.amenity ??
                addr.road,
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

        const province = addr.state ?? addr.province ?? addr.region ?? "";
        const country = addr.country ?? "";

        const labelParts = [
            addr.neighbourhood ?? addr.suburb ?? addr.hamlet ?? addr.village,
            street,
            city,
            province,
            country,
        ].filter(Boolean);

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
    }
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

const useMyLocation = (): void => {
    if (!navigator.geolocation) {
        alert("Geolocation is not supported.");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        async ({ coords: { latitude, longitude } }) => {
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
        },
        (error) => {
            console.warn("Location denied:", error.message);
        },
        {
            enableHighAccuracy: true,
        },
    );
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
};

onMounted(async () => {
    const L = (L_module as any).default ?? L_module;
    (window as any).L = L;

    await import("leaflet/dist/leaflet.css");

    const mapContainer = document.getElementById("location-map");
    if (!mapContainer) return;

    if ((mapContainer as any)._leaflet_id) {
        return;
    }

    delete (L.Icon.Default.prototype as any)._getIconUrl;

    L.Icon.Default.mergeOptions({
        iconRetinaUrl:
            "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",
        iconUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",
        shadowUrl:
            "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
    });

    const initialView =
        props.initialLat && props.initialLng
            ? [props.initialLat, props.initialLng]
            : [7.0736, 125.611];

    map = L.map("location-map", { attributionControl: false }).setView(
        initialView as [number, number],
        13,
    );

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap contributors",
    }).addTo(map);

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
        <div
            class="flex items-start gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm shadow-sm min-h-[48px]"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-4 h-4 text-gray-400 shrink-0 mt-2"
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
                class="text-gray-600 flex-1 whitespace-normal break-words mt-1"
            >
                {{
                    selectedLocation?.label ||
                    "Click the map to select a location"
                }}
            </span>

            <button
                v-if="selectedLocation"
                @click="clearSelection"
                class="text-gray-400 hover:text-red-500 transition-colors shrink-0"
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
            id="location-map"
            class="w-full h-[400px] rounded-xl overflow-hidden border border-gray-200 shadow-sm"
        />

        <div class="flex gap-2">
            <button
                @click="useMyLocation"
                class="flex items-center gap-2 px-5 py-2 text-sm bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors shadow-sm"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-4 h-4 text-blue-500"
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
