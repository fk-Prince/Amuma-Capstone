import BaseService from '~/api/BaseService';

class ScheduleService extends BaseService {
    private static instance: ScheduleService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): ScheduleService {
        if (!ScheduleService.instance) {
            ScheduleService.instance = new ScheduleService();
        }
        return ScheduleService.instance;
    }


    async list(params: object = {}): Promise<any> { // used
        return await this.request(this.resource, 'GET', params);
    }

    async action(payload: object): Promise<any> { // used
        return await this.request(`${this.resource}/action`, 'POST', payload);
    }

    async create(payload: object): Promise<any> { // used
        return await this.request(this.resource, 'POST', payload);
    }

    async update(uuid: string, payload: object): Promise<any> { // used
        return await this.request(`${this.resource}/${uuid}`, 'PUT', payload);
    }


    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/schedules`;
    }
}

export const scheduleService = ScheduleService.getInstance();
