export interface Alert {
    show: boolean;
    type: "success" | "error" | "info";
    message: string;
}