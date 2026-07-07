import BaseService from '~/api/BaseService';
import type { SignupRequest } from '~/types/auth';

export interface OtpRequest {
    otp_key?: string,
    email?: string,
    otp_value?: string,
    user?: SignupRequest
}

class OtpService extends BaseService {
    private static instance: OtpService;

    public static getInstance(): OtpService {
        if (!OtpService.instance) {
            OtpService.instance = new OtpService();
        }
        return OtpService.instance;
    }

    async createOtp(payload: OtpRequest): Promise<any> {
        return await this.request(this.resource + '/send', 'POST', payload);
    }

    async validateOtp(payload: OtpRequest): Promise<any> {
        return await this.request(this.resource + '/verify', 'POST', payload);
    }

    private get resource(): string {
        const config = useRuntimeConfig();
        return `${config.public.backendApi}/api/auth/otp`;
    }
}

export const otpService = OtpService.getInstance();
