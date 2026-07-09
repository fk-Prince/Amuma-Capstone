import BaseService from '~/api/BaseService';

class RoomService extends BaseService {
    private static instance: RoomService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): RoomService {
        if (!RoomService.instance) {
            RoomService.instance = new RoomService();
        }
        return RoomService.instance;
    }


    async list(params: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', params);
    }

    async create(payload: object): Promise<any> {
        return await this.request(this.resource, 'POST', payload);
    }



    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/rooms`;
    }
}

export const roomService = RoomService.getInstance();
