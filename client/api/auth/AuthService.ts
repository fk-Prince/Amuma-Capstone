import BaseService from '~/api/BaseService';
import type { SigninRequest } from '~/types/auth';

class AuthService extends BaseService {
    private static instance: AuthService;

    public static getInstance(): AuthService {
        if (!AuthService.instance) {
            AuthService.instance = new AuthService();
        }
        return AuthService.instance;
    }

    async login(payload: SigninRequest): Promise<any> {
        // const config = useRuntimeConfig()

        // await $fetch('/sanctum/csrf-cookie', {
        //     baseURL: config.public.backendApi,
        //     credentials: 'include',
        // })

        return await this.request(this.resource + '/login', 'POST', payload);
    }

    async register(payload: object): Promise<any> {
        return await this.request(this.resource + '/register', 'POST', payload);
    }

    async logout(): Promise<any> {
        return await this.request(this.resource + '/logout', 'POST', {});
    }

    async me(): Promise<any> {
        return await this.request(this.resource + '/me', 'GET', {});
    }

    async googleUrl(): Promise<any> {
        return await this.request(this.resource + '/google/url', 'POST', {});
    }

    private get resource(): string {
        const config = useRuntimeConfig();
        return `${config.public.backendApi}/api/auth`;
    }
}

export const authService = AuthService.getInstance();
