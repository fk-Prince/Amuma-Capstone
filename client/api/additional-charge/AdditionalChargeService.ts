import BaseService from "~/api/BaseService";

class AdditionalChargeService extends BaseService {
    private static instance: AdditionalChargeService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): AdditionalChargeService {
        if (!AdditionalChargeService.instance) {
            AdditionalChargeService.instance = new AdditionalChargeService();
        }
        return AdditionalChargeService.instance;
    }

    async list(params: {
        branch_uuid: string;
        patient_uuid: string;
        page?: number;
        per_page?: number;
    }): Promise<any> {
        return await this.request(this.resource, "GET", params);
    }

    async diagnoses(params: {
        branch_uuid: string;
        patient_uuid: string;
    }): Promise<any> {
        return await this.request(`${this.resource}/diagnoses`, "GET", params);
    }

    async create(payload: {
        branch_uuid: string;
        patient_uuid: string;
        charges: {
            type: string;
            description: string;
            amount: number;
            diagnosis_case_uuid?: string | null;
            patient_diagnosis_uuid?: string | null;
        }[];
    }): Promise<any> {
        return await this.request(this.resource, "POST", payload);
    }

    private get resource(): string {
        return `${this.getBackendApi}/api/additional-charges`;
    }
}

export const additionalChargeService = AdditionalChargeService.getInstance();
