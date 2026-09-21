import BaseService from "~/api/BaseService";

class CaregiverShiftService extends BaseService {
    private static instance: CaregiverShiftService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): CaregiverShiftService {
        if (!CaregiverShiftService.instance) {
            CaregiverShiftService.instance = new CaregiverShiftService();
        }
        return CaregiverShiftService.instance;
    }

    async list(params: object): Promise<any> {
        return await this.request(this.resource, "GET", params);
    }

    async assign(payload: object): Promise<any> {
        return await this.request(this.resource, "POST", payload);
    }

    async update(id: number, payload: object): Promise<any> {
        return await this.request(`${this.resource}/${id}`, "PUT", payload);
    }

    private get resource(): string {
        return `${this.getBackendApi}/api/caregiver-shifts`;
    }
}

export const caregiverShiftService = CaregiverShiftService.getInstance();
