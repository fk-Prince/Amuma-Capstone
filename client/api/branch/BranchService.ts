import BaseService from '~/api/BaseService';
import type { Branch } from '~/types/branch';

class BranchService extends BaseService {
    private static instance: BranchService;
    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): BranchService {
        if (!BranchService.instance) {
            BranchService.instance = new BranchService();
        }
        return BranchService.instance;
    }


    async featured(params: object = {}): Promise<any> {
        return await this.request(this.resource + '/featured', 'GET', params);
    }

    async filtered(params: object = {}): Promise<any> {
        return await this.request(this.resource + '/filtered', 'GET', params);
    }


    async get(uuid: string) {
        return await this.request(`${this.resource}/${uuid}`, 'GET');
    }

    async validate(params: Branch): Promise<any> {
        return await this.request(this.getBackendApi + '/api/validate/branches', 'POST', params);
    }

    async update(uuid: string, params: object = {}): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, 'PUT', params);
    }


    private get resource(): string {
        return `${this.getBackendApi}/api/branches`;
    }
}

export const branchService = BranchService.getInstance();
