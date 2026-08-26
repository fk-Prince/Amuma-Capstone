<!-- <template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        class="w-4 h-4 text-red-500"
        :class="
            clickable
                ? 'cursor-pointer hover:scale-110 transition-transform'
                : ''
        "
        fill="currentColor"
        viewBox="0 0 24 24"
        @click="handleClick"
    >
        <path
            d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"
        />
    </svg>
</template>

<script setup lang="ts">
const props = defineProps<{
    clickable?: boolean;
}>();

export type LocationPayload = {
    lat: number;
    lng: number;
    street: string;
    city: string;
    country: string;
    label: string;
};

const emit = defineEmits<{
    (e: "get-location", payload: LocationPayload): void;
}>();

type ReverseGeoResult = {
    street: string;
    city: string;
    country: string;
    label: string;
};

async function reverseGeocode(
    lat: number,
    lng: number,
): Promise<ReverseGeoResult> {
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

        const json = await res.json();

        const data = json?.data ?? json;

        const addr = data?.address ?? {};
        const displayName = data?.display_name ?? "";

        const street =
            addr.road ||
            addr.pedestrian ||
            addr.suburb ||
            addr.neighbourhood ||
            "";

        const city = addr.city || addr.town || addr.village || "";

        const country = addr.country || "";

        const label =
            displayName ||
            [
                addr.road,
                addr.neighbourhood,
                addr.suburb,
                addr.city || addr.town || addr.village,
                addr.state || addr.region,
                addr.postcode,
                addr.country,
            ]
                .filter(Boolean)
                .join(", ")
                .trim() ||
            `${lat.toFixed(5)}, ${lng.toFixed(5)}`;

        return {
            street,
            city,
            country,
            label,
        };
    } catch (err) {
        return {
            street: "",
            city: "",
            country: "",
            label: `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
        };
    }
}

function handleClick() {
    if (!props.clickable) return;

    if (!navigator.geolocation) {
        alert("Geolocation not supported");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            const address = await reverseGeocode(lat, lng);

            emit("get-location", {
                lat,
                lng,
                street: address.street,
                city: address.city,
                country: address.country,
                label: address.label,
            });
        },
        (err) => {
            console.error("Geo error:", err);
            alert("Unable to get location");
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
        },
    );
}
</script> -->

<template>
    <svg
        v-if="loading"
        xmlns="http://www.w3.org/2000/svg"
        class="w-4 h-4 text-muted animate-spin"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
    >
        <path d="M21 12a9 9 0 1 1-6.219-8.56" />
    </svg>

    <svg
        v-else
        xmlns="http://www.w3.org/2000/svg"
        class="w-4 h-4 text-red-500"
        :class="
            clickable
                ? 'cursor-pointer hover:scale-110 transition-transform'
                : ''
        "
        fill="currentColor"
        viewBox="0 0 24 24"
        @click="handleClick"
    >
        <path
            d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"
        />
    </svg>
</template>

<script setup lang="ts">
import { ref } from "vue";

const props = defineProps<{
    clickable?: boolean;
}>();

export type LocationPayload = {
    lat: number;
    lng: number;
    street: string;
    city: string;
    country: string;
    label: string;
};

const emit = defineEmits<{
    (e: "get-location", payload: LocationPayload): void;
    (e: "loading", value: boolean): void;
}>();

const loading = ref(false);

function setLoading(value: boolean) {
    loading.value = value;
    emit("loading", value);
}

type ReverseGeoResult = {
    street: string;
    city: string;
    country: string;
    label: string;
};

async function reverseGeocode(
    lat: number,
    lng: number,
): Promise<ReverseGeoResult> {
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

        const json = await res.json();

        const data = json?.data ?? json;

        const addr = data?.address ?? {};
        const displayName = data?.display_name ?? "";

        const street =
            addr.road ||
            addr.pedestrian ||
            addr.suburb ||
            addr.neighbourhood ||
            "";

        const city = addr.city || addr.town || addr.village || "";

        const country = addr.country || "";

        const label =
            displayName ||
            [
                addr.road,
                addr.neighbourhood,
                addr.suburb,
                addr.city || addr.town || addr.village,
                addr.state || addr.region,
                addr.postcode,
                addr.country,
            ]
                .filter(Boolean)
                .join(", ")
                .trim() ||
            `${lat.toFixed(5)}, ${lng.toFixed(5)}`;

        return {
            street,
            city,
            country,
            label,
        };
    } catch (err) {
        return {
            street: "",
            city: "",
            country: "",
            label: `${lat.toFixed(5)}, ${lng.toFixed(5)}`,
        };
    }
}

/**
 * Wraps geolocation in a promise. The success handler is just `resolve`, so
 * nothing from the component's scope is referenced inside the callback —
 * location-spoofing browser extensions re-evaluate that callback in their own
 * scope, which breaks any closure it depends on.
 */
function currentPosition(): Promise<GeolocationPosition> {
    return new Promise((resolve, reject) =>
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
        }),
    );
}

async function handleClick() {
    if (!props.clickable || loading.value) return;

    if (!navigator.geolocation) {
        alert("Geolocation not supported");
        return;
    }

    setLoading(true);

    try {
        // Runs in the component's own scope rather than inside the callback.
        const pos = await currentPosition();

        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        const address = await reverseGeocode(lat, lng);

        emit("get-location", {
            lat,
            lng,
            street: address.street,
            city: address.city,
            country: address.country,
            label: address.label,
        });
    } catch (err) {
        console.error("Geo error:", err);
        alert("Unable to get location");
    } finally {
        setLoading(false);
    }
}
</script>
