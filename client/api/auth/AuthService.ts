import BaseService from '~/api/BaseService';
import type {
    ForgotPasswordRequest,
    ResetLinkRequest,
    ResetPasswordRequest,
    SigninRequest,
} from '~/types/auth';

class AuthService extends BaseService {
    private static instance: AuthService;

    public static getInstance(): AuthService {
        if (!AuthService.instance) {
            AuthService.instance = new AuthService();
        }
        return AuthService.instance;
    }

    async login(payload: SigninRequest): Promise<any> {
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

    async forgotPassword(payload: ForgotPasswordRequest): Promise<any> {
        return await this.request(this.resource + '/forgot-password', 'POST', payload);
    }

    async checkResetLink(payload: ResetLinkRequest): Promise<any> {
        return await this.request(this.resource + '/reset-password/check', 'POST', payload);
    }

    async resetPassword(payload: ResetPasswordRequest): Promise<any> {
        return await this.request(this.resource + '/reset-password', 'POST', payload);
    }

    private get resource(): string {
        const config = useRuntimeConfig();
        return `${config.public.backendApi}/api/auth`;
    }
}

export const authService = AuthService.getInstance();
