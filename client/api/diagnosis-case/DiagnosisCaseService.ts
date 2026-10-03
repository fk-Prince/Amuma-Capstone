import BaseService from "~/api/BaseService";

export interface DiagnosisCasePayload {
    branch_uuid: string;
    title: string;
    description: string | null;
    price: number;
}

class DiagnosisCaseService extends BaseService {
    private static instance: DiagnosisCaseService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): DiagnosisCaseService {
        if (!DiagnosisCaseService.instance) {
            DiagnosisCaseService.instance = new DiagnosisCaseService();
        }
        return DiagnosisCaseService.instance;
    }

    async list(params: {
        branch_uuid: string;
        for?: "charges";
    }): Promise<any> {
        return await this.request(this.resource, "GET", params);
    }

    async create(payload: DiagnosisCasePayload): Promise<any> {
        return await this.request(this.resource, "POST", payload);
    }

    async update(uuid: string, payload: DiagnosisCasePayload): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, "PUT", payload);
    }

    private get resource(): string {
        return `${this.getBackendApi}/api/diagnosis-cases`;
    }
}

export const diagnosisCaseService = DiagnosisCaseService.getInstance();
