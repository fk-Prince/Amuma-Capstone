export const DAVAO_DEFAULT = {
    label: "Davao City",
    lat: 7.1907,
    long: 125.4553,
};

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

    // Used only when the page hasn't been given an explicit search location
    // (a fresh visit with no query yet) — approximates the visitor's city
    // from their IP so the map/results start somewhere relevant instead of
    // always opening on Davao City. IP geolocation is unreliable on local/
    // loopback requests and behind some networks, so it falls back to the
    // browser's own geolocation (reverse-geocoded to a city) before finally
    // giving up on Davao City.
    const resolveDefaultCenter = async (): Promise<{
        label: string;
        lat: number;
        long: number;
    }> => {
        // A GPS fix the visitor has already allowed beats an IP guess, which
        // often points at the ISP's city rather than where they really are.
        if (await gpsAllowed()) {
            const gps = await getMyLocation();

            if (gps) {
                const label = await reverseGeocodeCity(gps.lat, gps.lng);

                return {
                    label: label || DAVAO_DEFAULT.label,
                    lat: gps.lat,
                    long: gps.lng,
                };
            }
        }

        try {
            const config = useRuntimeConfig();

            const res = await fetch(`${config.public.backendApi}/api/ip-locate`, {
                headers: { Accept: "application/json" },
            });

            const result = await res.json();
            const data = result?.data;

            if (result?.success && data?.lat != null && data?.lng != null) {
                return {
                    label: data.city || DAVAO_DEFAULT.label,
                    lat: Number(data.lat),
                    long: Number(data.lng),
                };
            }
        } catch (err) {
            console.error(err);
        }

        const browserLocation = await getMyLocation();
        if (browserLocation) {
            const label = await reverseGeocodeCity(
                browserLocation.lat,
                browserLocation.lng,
            );

            return {
                label: label || DAVAO_DEFAULT.label,
                lat: browserLocation.lat,
                long: browserLocation.lng,
            };
        }

        return { ...DAVAO_DEFAULT };
    };

    const gpsAllowed = async (): Promise<boolean> => {
        try {
            const status = await navigator.permissions?.query({
                name: "geolocation" as PermissionName,
            });

            return status?.state === "granted";
        } catch {
            return false;
        }
    };

    const reverseGeocodeCity = async (
        lat: number,
        lng: number,
    ): Promise<string | null> => {
        try {
            const config = useRuntimeConfig();

            const res = await fetch(
                `${config.public.backendApi}/api/reverse-geocode?lat=${lat}&lon=${lng}`,
                { headers: { Accept: "application/json" } },
            );

            const json = await res.json();
            const addr = json?.data?.address ?? {};

            return addr.city || addr.town || addr.village || null;
        } catch (err) {
            console.error(err);
            return null;
        }
    };

    // Browser geolocation for the "My Location" map marker — opt-in and
    // silently resolves to null if denied/unavailable, since this is only
    // a nice-to-have label and shouldn't block the page.
    const getMyLocation = (): Promise<{ lat: number; lng: number } | null> => {
        return new Promise((resolve) => {
            if (typeof navigator === "undefined" || !navigator.geolocation) {
                resolve(null);
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) =>
                    resolve({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    }),
                () => resolve(null),
                { timeout: 8000 },
            );
        });
    };

    return {
        centerLat,
        centerLng,
        geocodeLocation,
        resolveDefaultCenter,
        getMyLocation,
    };
};
