import BaseService from '~/api/BaseService';
import type { Agency } from '~/types/agency';

class AgencyService extends BaseService {
    private static instance: AgencyService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): AgencyService {
        if (!AgencyService.instance) {
            AgencyService.instance = new AgencyService();
        }
        return AgencyService.instance;
    }

    async list(params: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', params);
    }

    // async update(uuid: string, payload: object = {}): Promise<any> {
    //     return await this.request(`${this.resource}/${uuid}`, 'PUT', payload);
    // }

    // async validate(params: Agency): Promise<any> {
    //     const errors: Record<string, string> = {};

    //     if (!params.agency_name?.trim()) errors.agency_name = "Agency name is required";
    //     if (!params.agency_description?.trim()) errors.agency_description = "Agency description is required";
    //     if (!params.location?.street?.trim()) errors["location.street"] = "Street is required";
    //     if (!params.location?.city?.trim()) errors["location.city"] = "City is required";
    //     if (!params.location?.province?.trim()) errors["location.province"] = "Province is required";
    //     if (!params.location?.country?.trim()) errors["location.country"] = "Country is required";
    //     if (Object.keys(errors).length) {
    //         throw {
    //             errors,
    //         };
    //     }
    // }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/agencies`;
    }
}

export const agencyService = AgencyService.getInstance();
