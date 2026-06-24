import BaseService from '~/api/BaseService';

class UserService extends BaseService {
    private static instance: UserService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): UserService {
        if (!UserService.instance) {
            UserService.instance = new UserService();
        }
        return UserService.instance;
    }

    public async userBranch(params: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', params);
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/users`;
    }

}

export const userService = UserService.getInstance();
