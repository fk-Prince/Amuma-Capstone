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

            const config = useRuntimeConfig();

            const res = await fetch(
                `${config.public.backendApi}/api/geocode?q=${encodeURIComponent(locationQuery)}`,
                {
                    headers: {
                        Accept: "application/json",
                        "Accept-Language": "en",
                    },
                },
            );

            const data = await res.json();

            if (data.lat && data.lng) {
                centerLat.value = Number(data.lat);
                centerLng.value = Number(data.lng);
            }
        } catch (err) {
            console.error(err);
            return;
        }
    };

    return { centerLat, centerLng, geocodeLocation };
};


