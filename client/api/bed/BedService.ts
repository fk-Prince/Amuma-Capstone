import BaseService from '~/api/BaseService';

class BedService extends BaseService {
    private static instance: BedService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): BedService {
        if (!BedService.instance) {
            BedService.instance = new BedService();
        }
        return BedService.instance;
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

    async update(id: number, payload: object): Promise<any> {
        return await this.request(`${this.resource}/${id}`, 'PUT', payload);
    }


    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/beds`;
    }
}

export const bedService = BedService.getInstance();
