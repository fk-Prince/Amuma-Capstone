import BaseService from '~/api/BaseService';

class BookingService extends BaseService {
    private static instance: BookingService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): BookingService {
        if (!BookingService.instance) {
            BookingService.instance = new BookingService();
        }
        return BookingService.instance;
    }


    async create(payload: object): Promise<any> {
        return await this.request(this.resource, 'POST', payload);
    }



    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/bookings`;
    }
}

export const bookingService = BookingService.getInstance();
