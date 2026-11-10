import { ref } from "vue";

const SETUP_RESOURCES = /\/api\/(employees|services|rooms|beds|contracts|settings)(\/|\?|$)/;

export const setupDataVersion = ref(0);

export function noteSetupChange(url: string, method: string) {
    if (!import.meta.client || method === "GET") return;
    if (SETUP_RESOURCES.test(url)) setupDataVersion.value++;
}
