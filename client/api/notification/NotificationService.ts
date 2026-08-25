import BaseService from '~/api/BaseService';

class NotificationService extends BaseService {
    private static instance: NotificationService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): NotificationService {
        if (!NotificationService.instance) {
            NotificationService.instance = new NotificationService();
        }
        return NotificationService.instance;
    }


    async list(params: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', params);
    }

    async markRead(notificationId?: number): Promise<any> {
        return await this.request(`${this.resource}/read`, 'POST', notificationId ? { notification_id: notificationId } : {},);
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
        const backend = this.getBackendApi;
        return `${backend}/api/notifications`;
    }

    async createsample(payload: object): Promise<any> {
        return await this.request(this.getBackendApi + '/api/sample', 'POST', payload);
    }

}

export const notificationService = NotificationService.getInstance();
