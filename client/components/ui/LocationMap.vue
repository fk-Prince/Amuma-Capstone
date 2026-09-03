<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from "vue";
import L_module from "leaflet";

const props = withDefaults(
    defineProps<{
        lat: number;
        lng: number;
        label?: string;
        zoom?: number;
        heightClass?: string;
    }>(),
    {
        zoom: 16,
        heightClass: "h-[220px]",
    },
);

// Multiple schedules can be open at once, so the container is referenced
// directly rather than by a shared element id.
const container = ref<HTMLElement | null>(null);

let map: L_module.Map | null = null;
let marker: L_module.Marker | null = null;

onMounted(async () => {
    const L = (L_module as any).default ?? L_module;
    (window as any).L = L;

    await import("leaflet/dist/leaflet.css");

    if (!container.value || (container.value as any)._leaflet_id) return;

    delete (L.Icon.Default.prototype as any)._getIconUrl;

    L.Icon.Default.mergeOptions({
        iconRetinaUrl:
            "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png",
        iconUrl: "https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png",
        shadowUrl:
            "https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png",
    });

    map = L.map(container.value, {
        attributionControl: false,
        // Read-only preview: scroll should keep scrolling the page.
        scrollWheelZoom: false,
        dragging: true,
    }).setView([props.lat, props.lng], props.zoom);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap contributors",
    }).addTo(map);

    marker = L.marker([props.lat, props.lng]).addTo(map!);

    if (props.label) {
        marker?.bindPopup(props.label);
    }
});

watch(
    () => [props.lat, props.lng] as const,
    ([lat, lng]) => {
        if (!map || lat == null || lng == null) return;

        map.setView([lat, lng], props.zoom);
        marker?.setLatLng([lat, lng]);
    },
);

onUnmounted(() => {
    map?.remove();
    map = null;
    marker = null;
});
</script>

<template>
    <div
        ref="container"
        class="w-full rounded-xl overflow-hidden border border-[#E4EFED] z-0 dark:border-white/10"
        :class="heightClass"
    />
</template>
