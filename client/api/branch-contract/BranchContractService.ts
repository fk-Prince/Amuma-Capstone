import BaseService from '~/api/BaseService';

class BranchContractService extends BaseService {
    private static instance: BranchContractService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): BranchContractService {
        if (!BranchContractService.instance) {
            BranchContractService.instance = new BranchContractService();
        }
        return BranchContractService.instance;
    }


    async list(params: object = {}): Promise<any> { // used
        return await this.request(this.resource, 'GET', params);
    }

    async create(payload: object): Promise<any> {
        return await this.request(this.resource, 'POST', payload);
    }

    async show(uuid: string): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, 'GET');
    }

    async update(uuid: string, payload: object): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, 'PUT', payload);
    }

    async delete(uuid: string): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, 'DELETE');
    }

    async restore(uuid: string): Promise<any> {
        return await this.request(`${this.resource}/${uuid}/restore`, 'POST');
    }

    async overview(payload: object): Promise<any> {
        return await this.request(`${this.resource}/overview`, 'POST', payload);
    }
    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/contracts`;
    }
}

export const branchContractService = BranchContractService.getInstance();
