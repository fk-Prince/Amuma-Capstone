<script setup lang="ts">
import { onMounted, onUnmounted, watch } from "vue";
import { type Location } from "~/types/location";
import { useGeo } from "~/composables/useGeo";

let L: any;
let map: any = null;
let markersLayer: any = null;
let myLocationMarker: any = null;
let highlightIcon: any = null;
let markersByUuid: Record<string, any> = {};
let highlightedMarker: any = null;
let resizeObserver: ResizeObserver | null = null;

const { getMyLocation } = useGeo();

const props = withDefaults(
    defineProps<{
        locations?: Location[];
        centerLat?: number;
        centerLng?: number;
        zoom?: number;
        extraClass?: string;
        hoveredUuid?: string | null;
        showMyLocation?: boolean;
    }>(),
    { showMyLocation: true },
);

let myLocation: { lat: number; lng: number } | null = null;

const renderMyLocation = () => {
    myLocationMarker?.remove();
    myLocationMarker = null;

    if (!map || !myLocation || !props.showMyLocation) return;

    myLocationMarker = L.circleMarker([myLocation.lat, myLocation.lng], {
        radius: 8,
        fillColor: "#3b82f6",
        color: "#fff",
        weight: 2,
        opacity: 1,
        fillOpacity: 0.9,
    })
        .addTo(map)
        .bindTooltip("This is me", {
            permanent: true,
            direction: "top",
            offset: [0, -8],
        })
        .bindPopup("<b>This is me</b>");
};

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

const applyHover = () => {
    if (highlightedMarker) {
        highlightedMarker.setIcon(new L.Icon.Default());
        highlightedMarker.setZIndexOffset(0);
        highlightedMarker = null;
    }

    const marker = props.hoveredUuid
        ? markersByUuid[props.hoveredUuid]
        : null;

    if (marker) {
        marker.setIcon(highlightIcon);
        marker.setZIndexOffset(1000);
        highlightedMarker = marker;
    }
};

const renderMarkers = () => {
    if (!map || !markersLayer) return;

    markersLayer.clearLayers();
    markersByUuid = {};
    highlightedMarker = null;

    props.locations?.forEach((location) => {
        if (
            location.latitude == null ||
            location.longitude == null ||
            Number.isNaN(Number(location.latitude)) ||
            Number.isNaN(Number(location.longitude))
        )
            return;

        const marker = L.marker([
            Number(location.latitude),
            Number(location.longitude),
        ]).addTo(markersLayer);

        if (location.uuid) {
            markersByUuid[location.uuid] = marker;
        }
    });

    if (props.centerLat == null) {
        fitToBounds();
    }

    applyHover();
};

const applyCenter = () => {
    if (!map || props.centerLat == null || props.centerLng == null) return;

    map.setView([props.centerLat, props.centerLng], props.zoom ?? 13);
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

    // bounce animates an inner wrapper, not the icon element Leaflet positions
    highlightIcon = L.divIcon({
        className: "highlight-marker-wrapper",
        html: '<div class="marker-bounce"><img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png" style="width:30px;height:49px;display:block;" /></div>',
        iconSize: [30, 49],
        iconAnchor: [15, 49],
        popupAnchor: [1, -34],
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

    // Switching between the list, split and full-map views changes this
    // container's size after the map exists, which leaves grey tiles and a
    // drifted centre until Leaflet is told to re-measure.
    const container = document.getElementById("locations-map");

    if (container && typeof ResizeObserver !== "undefined") {
        let frame = 0;

        resizeObserver = new ResizeObserver(() => {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                if (!map) return;

                map.invalidateSize();

                if (props.centerLat != null && props.centerLng != null) {
                    applyCenter();
                } else {
                    fitToBounds();
                }
            });
        });

        resizeObserver.observe(container);
    }

    getMyLocation().then((loc) => {
        if (!loc) return;

        myLocation = { lat: loc.lat, lng: loc.lng };
        renderMyLocation();
    });
});

watch(
    () => props.showMyLocation,
    () => renderMyLocation(),
);

watch(
    () => [props.centerLat, props.centerLng],
    () => {
        if (!props.locations?.length) {
            applyCenter();
            return;
        }

        if (props.centerLat == null || props.centerLng == null) {
            fitToBounds();
            return;
        }

        applyCenter();
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

watch(
    () => props.hoveredUuid,
    () => {
        if (!map) return;
        applyHover();
    },
);

onUnmounted(() => {
    resizeObserver?.disconnect();
    resizeObserver = null;
    map?.remove();
    map = null;
    myLocationMarker = null;
    markersByUuid = {};
    highlightedMarker = null;
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

<style>
.highlight-marker-wrapper {
    background: transparent;
    border: none;
}

@keyframes marker-bounce {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

.marker-bounce {
    animation: marker-bounce 0.6s ease-in-out infinite;
}
</style>
