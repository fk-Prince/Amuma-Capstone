import {
    LIFE_SYSTEM_ACTIVITIES,
    LIFE_SYSTEM_SCALE,
    type LifeSystemActivity,
    type LifeSystemScore,
} from "~/types/patient";

export { LIFE_SYSTEM_ACTIVITIES, LIFE_SYSTEM_SCALE };

export function activityLabel(activity: LifeSystemActivity | string) {
    return String(activity).charAt(0).toUpperCase() + String(activity).slice(1);
}

export function lifeSystemLabel(score: LifeSystemScore | number | undefined) {
    if (score === undefined || score === null) return "";

    const step = LIFE_SYSTEM_SCALE.find((item) => item.value === score);

    return step ? `${step.value} — ${step.label}` : String(score);
}

export const lifeSystemItems = LIFE_SYSTEM_SCALE.map((step) => ({
    label: `${step.value} — ${step.label}`,
    value: step.value,
}));

const ASSESSMENT_LABELS: Record<string, string> = {
    ambulatory: "Ambulatory",
    wheelchair: "Wheelchair",
    stretcher: "Stretcher",
    alert: "Alert",
    drowsy: "Drowsy",
    lethargic: "Lethargic",
    forgetfulness: "Forgetfulness",
    cheerful: "Cheerful",
    flat: "Flat",
    tearful: "Tearful",
    depressed: "Depressed",
    angry: "Angry",
    cooperative: "Cooperative",
    uncooperative: "Uncooperative",
    lack_of_interaction: "Lack of interaction",
    communication_barrier: "Communication barrier",
    clear: "Clear",
    slurred: "Slurred",
    aphasic: "Aphasic",
};

export function assessmentLabel(value?: string | null) {
    if (!value) return "";

    return ASSESSMENT_LABELS[value] ?? value;
}

export const assessmentOptions = {
    condition: [
        { label: "Ambulatory", value: "ambulatory" },
        { label: "Wheelchair", value: "wheelchair" },
        { label: "Stretcher", value: "stretcher" },
    ],
    mental_state: [
        { label: "Alert", value: "alert" },
        { label: "Drowsy", value: "drowsy" },
        { label: "Lethargic", value: "lethargic" },
        { label: "Forgetfulness", value: "forgetfulness" },
    ],
    affect: [
        { label: "Cheerful", value: "cheerful" },
        { label: "Flat", value: "flat" },
        { label: "Tearful", value: "tearful" },
        { label: "Depressed", value: "depressed" },
        { label: "Angry", value: "angry" },
    ],
    behavior: [
        { label: "Cooperative", value: "cooperative" },
        { label: "Uncooperative", value: "uncooperative" },
        { label: "Lack of interaction", value: "lack_of_interaction" },
        { label: "Communication barrier", value: "communication_barrier" },
    ],
    communication: [
        { label: "Coherent & Logical", value: "Coherent & Logical" },
        { label: "Impaired", value: "Impaired" },
    ],
    speech: [
        { label: "Clear", value: "clear" },
        { label: "Slurred", value: "slurred" },
        { label: "Aphasic", value: "aphasic" },
    ],
};
