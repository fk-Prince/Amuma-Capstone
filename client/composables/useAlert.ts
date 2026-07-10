import type { Ref } from "vue";
import { type Alert } from "~/types/alert";




export function showAlert(
    alert: Ref<Alert>,
    type: Alert["type"],
    message: string,
    duration = 5000
) {
    alert.value = {
        show: true,
        type,
        message,
    };

    console.log("asd")

    if (duration > 0) {
        setTimeout(() => {
            alert.value.show = false;
        }, duration);
    }
}