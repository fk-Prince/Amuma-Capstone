import BaseService from "~/api/BaseService";

class DepositService extends BaseService {
    private static instance: DepositService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): DepositService {
        if (!DepositService.instance) {
            DepositService.instance = new DepositService();
        }
        return DepositService.instance;
    }

    async deposit(payload: {
        patient_id: number;
        amount: number;
        token_id: string;
        authentication_id: string;
    }): Promise<any> {
        return await this.request(this.resource + "/action", "POST", payload);
    }

    async issue(payload: {
        p_uuid: string;
        branch_uuid: string;
        amount: number;
        deposited_by?: string;
        note?: string;
    }): Promise<any> {
        return await this.request(this.resource + "/issue", "POST", payload);
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/deposits`;
    }
}

export const depositService = DepositService.getInstance();
