import BaseService from '~/api/BaseService';
import type { Service } from '~/types/service';

class ServiceService extends BaseService {
    private static instance: ServiceService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): ServiceService {
        if (!ServiceService.instance) {
            ServiceService.instance = new ServiceService();
        }
        return ServiceService.instance;
    }

    async list(payload: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', payload);
    }

    async assignEmployeeService(payload: object): Promise<any> {
        return await this.request(this.resource + '/assign-employee', 'POST', payload);
    }

    async getBranchService(uuid: string) {
        return await this.request(`${this.getBackendApi}/api/branches/${uuid}/services`, 'GET');
    }

    async create(payload: Service): Promise<any> {
        return await this.request(this.resource, 'POST', payload);
    }

    async update(id: number, payload: {}) {
        return await this.request(`${this.resource}/${id}`, 'PUT', payload);
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/services`;
    }
}

export const serviceService = ServiceService.getInstance();
