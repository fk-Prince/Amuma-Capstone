<script setup lang="ts">
import { onMounted, onUnmounted, watch } from "vue";
import { type Location } from "~/types/location";

let L: any;
let map: any = null;
let markersLayer: any = null;
let centerMarker: any = null;

const props = defineProps<{
    locations?: Location[];
    centerLat?: number;
    centerLng?: number;
    zoom?: number;
    extraClass?: string;
}>();

const fitToBounds = () => {
    if (!map || !props.locations?.length) return;

    const valid = props.locations.filter(
        (loc) =>
            loc.latitude != null &&
            loc.longitude != null &&
            !Number.isNaN(Number(loc.latitude)) &&
            !Number.isNaN(Number(loc.longitude)),
    );

    if (valid.length) {
        map.fitBounds(
            L.latLngBounds(
                valid.map((loc) => [
                    Number(loc.latitude),
                    Number(loc.longitude),
                ]),
            ),
            { padding: [40, 40] },
        );
    }
};

const renderMarkers = () => {
    if (!map || !markersLayer) return;

    markersLayer.clearLayers();

    props.locations?.forEach((location) => {
        if (
            location.latitude == null ||
            location.longitude == null ||
            Number.isNaN(Number(location.latitude)) ||
            Number.isNaN(Number(location.longitude))
        )
            return;

        L.marker([Number(location.latitude), Number(location.longitude)]).addTo(
            markersLayer,
        );
    });

    if (props.centerLat == null) {
        fitToBounds();
    }
};

const applyCenter = () => {
    if (!map || props.centerLat == null || props.centerLng == null) return;

    map.setView([props.centerLat, props.centerLng], props.zoom ?? 13);

    if (centerMarker) {
        centerMarker.remove();
    }

    const redIcon = L.icon({
        iconUrl:
            "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png",
        iconRetinaUrl:
            "https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png",
        shadowUrl:
            "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41],
    });

    centerMarker = L.marker([props.centerLat, props.centerLng], {
        icon: redIcon,
    }).addTo(map);
};

onMounted(async () => {
    const leaflet = await import("leaflet");
    await import("leaflet/dist/leaflet.css");

    L = leaflet.default ?? leaflet;

    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl:
            "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",
        iconUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",
        shadowUrl:
            "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
    });

    map = L.map("locations-map", {
        attributionControl: false,
    }).setView(
        [props.centerLat ?? 7.0736, props.centerLng ?? 125.611],
        props.zoom ?? 13,
    );

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);

    if (props.locations?.length) {
        renderMarkers();
    }

    if (props.centerLat != null && props.centerLng != null) {
        applyCenter();
    }

    setTimeout(() => {
        map?.invalidateSize();
    }, 200);
});

watch(
    () => [props.centerLat, props.centerLng],
    () => {
        if (!props.locations?.length) {
            applyCenter();
        }
    },
);

watch(
    () => props.locations,
    (locs) => {
        if (!map) return;

        if (locs?.length) {
            renderMarkers();

            if (props.centerLat != null && props.centerLng != null) {
                applyCenter();
            }
        } else if (props.centerLat != null && props.centerLng != null) {
            markersLayer?.clearLayers();
            applyCenter();
        }
    },
    { deep: true },
);

onUnmounted(() => {
    map?.remove();
    map = null;
    centerMarker = null;
});
</script>

<template>
    <div
        id="locations-map"
        :class="[
            'w-full h-[400px] rounded-xl z-30 overflow-hidden border border-gray-200 shadow-sm dark:border-white/10',
            extraClass,
        ]"
    />
</template>
