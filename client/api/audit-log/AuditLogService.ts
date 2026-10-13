import BaseService from '~/api/BaseService';

class AuditLogService extends BaseService {
    private static instance: AuditLogService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): AuditLogService {
        if (!AuditLogService.instance) {
            AuditLogService.instance = new AuditLogService();
        }
        return AuditLogService.instance;
    }

    async list(params: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', params);
    }

    async show(uuid: string, branchUuid: string): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, 'GET', { branch_uuid: branchUuid });
    }

    private get resource(): string {
        return `${this.getBackendApi}/api/audit-logs`;
    }
}

export const auditLogService = AuditLogService.getInstance();
