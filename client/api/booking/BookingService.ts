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

    // async facilityBooking(payload: object): Promise<any> {
    //     //note used
    //     return await this.request(this.resource + '/facility', 'POST', payload);
    // }
    // async facilityAdmission(payload: object): Promise<any> {
    //     //note used
    //     return await this.request(this.resource + '/facility-admission', 'POST', payload);
    // }

    //USED
    async list(payload: object): Promise<any> {
        return await this.request(this.resource, 'GET', payload);
    }

    //USED
    async actionBooking(payload: object): Promise<any> {
        return await this.request(this.resource + '/action', 'POST', payload);
    }



    async show(id: string, payload = {}): Promise<any> {
        // used
        return await this.request(`${this.resource}/${id}`, 'GET', payload);
    }

    async overview(payload = {}): Promise<any> {
        // used
        return await this.request(`${this.resource}/overview`, 'POST', payload);
    }
    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/bookings`;
    }
}

export const bookingService = BookingService.getInstance();
