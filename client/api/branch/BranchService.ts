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
        // const errors: Record<string, string> = {};
        // if (!params.name?.trim()) errors.branch_name = "Branch name is required";
        // if (!params.description?.trim()) errors.branch_description = "Branch description is required";
        // if (!params.location?.street?.trim()) errors["location.street"] = "Street is required";
        // if (!params.location?.city?.trim()) errors["location.city"] = "City is required";
        // if (!params.location?.province?.trim()) errors["location.province"] = "Province is required";
        // if (!params.location?.country?.trim()) errors["location.country"] = "Country is required";
        // if (Object.keys(errors).length) {
        //     throw {
        //         errors,
        //     };
        // }
        return await this.request(this.getBackendApi + '/api/validate/branches', 'POST', params);
    }
    private get resource(): string {
        return `${this.getBackendApi}/api/branches`;
    }
}

export const branchService = BranchService.getInstance();
