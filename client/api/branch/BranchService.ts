import BaseService from '~/api/BaseService';

class BranchService extends BaseService {
    private static instance: BranchService;

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

    private get resource(): string {
        const config = useRuntimeConfig();
        return `${config.public.backendApi}/api/branches`;
    }
}

export const branchService = BranchService.getInstance();
