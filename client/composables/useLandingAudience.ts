import { computed, onMounted, ref } from "vue";
import { useAuthUser } from "~/composables/useAuthUser";
import {
    audienceOf,
    landingPathFor,
    type LandingAudience,
} from "~/utils/landingAudience";

export type { LandingAudience };
export {
    AGENCY_LANDING,
    FAMILY_LANDING,
    audienceOf,
    landingPathFor,
} from "~/utils/landingAudience";

export const useLandingAudience = () => {
    const user = useAuthUser();
    const hydrated = ref(false);

    onMounted(() => {
        hydrated.value = true;
    });

    const audience = computed<LandingAudience>(() =>
        hydrated.value ? audienceOf(user.value) : "both",
    );

    return {
        audience,
        landingPath: computed(() => landingPathFor(audience.value)),
        isFamily: computed(() => audience.value === "family"),
        isAgency: computed(() => audience.value === "agency"),
        // Families book care; they never see subscription/plan content.
        canSeeSubscription: computed(() => audience.value !== "family"),
    };
};