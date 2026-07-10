export interface Alert {
    show: boolean;
    type: "success" | "error" | "info";
    message: string;
}


export const alertData = ref<Alert>({
    show: false,
    type: "info",
    message: "",
});