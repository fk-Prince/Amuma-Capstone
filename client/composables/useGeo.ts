export const useGeo = () => {
    const centerLat = ref<number | undefined>(undefined);
    const centerLng = ref<number | undefined>(undefined);

    const geocodeLocation = async (locationQuery: string) => {
        try {
            const coordMatch = locationQuery.match(/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/);
            if (coordMatch) {
                centerLat.value = Number(coordMatch[1]);
                centerLng.value = Number(coordMatch[2]);
                return;
            }

            const res = await fetch(
                `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(locationQuery)}&format=json&limit=1`,
            );
            const data = await res.json();
            if (data.length > 0) {
                centerLat.value = Number(data[0].lat);
                centerLng.value = Number(data[0].lon);
            }
        } catch (err) {
            console.error("Geocoding failed:", err);
        }
    };

    return { centerLat, centerLng, geocodeLocation };
};