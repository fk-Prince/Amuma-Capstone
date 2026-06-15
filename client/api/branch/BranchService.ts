import BaseService from '~/api/BaseService';

class BranchService extends BaseService {
    private static instance: BranchService;

    public static getInstance(): BranchService {
        if (!BranchService.instance) {
            BranchService.instance = new BranchService();
        }
        return BranchService.instance;
    }


    async list(params: object = {}): Promise<any> {
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

    private get resource(): string {
        const config = useRuntimeConfig();
        return `${config.public.backendApi}/api/branches`;
    }
}

export const branchService = BranchService.getInstance();
