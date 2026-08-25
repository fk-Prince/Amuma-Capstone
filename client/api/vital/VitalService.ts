import BaseService from '~/api/BaseService';

class VitalService extends BaseService {
    private static instance: VitalService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): VitalService {
        if (!VitalService.instance) {
            VitalService.instance = new VitalService();
        }
        return VitalService.instance;
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

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/vitals`;
    }
}

export const vitalService = VitalService.getInstance();
