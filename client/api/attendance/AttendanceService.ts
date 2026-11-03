import BaseService from '~/api/BaseService';

class AttendanceService extends BaseService {
    private static instance: AttendanceService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): AttendanceService {
        if (!AttendanceService.instance) {
            AttendanceService.instance = new AttendanceService();
        }
        return AttendanceService.instance;
    }

    async generateQr(payload: {}): Promise<any> {
        return await this.request(this.resource + '/qr', 'GET', payload);
    }
    async verifyQr(payload: { token: string; type: string; confirm_unpaid?: boolean }) {
        await this.request('/api/qr/verify', 'POST', payload);
    }

    async demoVerifyQr(payload: { token: string; type: string; employee_id: number }) {
        return await this.request(this.getBackendApi + '/api/qr/demo-verify', 'POST', payload);
    }

    async lookupPatientVisit(payload: { patient_uuid: string }): Promise<any> {
        return await this.request(this.getBackendApi + '/api/qr/patient/lookup', 'POST', payload);
    }

    async confirmPatientVisit(payload: {
        patient_uuid: string;
        schedule_id: number;
        employee_ids: number[];
    }): Promise<any> {
        return await this.request(this.getBackendApi + '/api/qr/patient/confirm', 'POST', payload);
    }

    async timeOutPatientVisit(payload: {
        patient_uuid: string;
        schedule_id: number;
        confirm_unpaid?: boolean;
    }): Promise<any> {
        return await this.request(this.getBackendApi + '/api/qr/patient/time-out', 'POST', payload);
    }

    async updateVisitAttendance(payload: {
        branch_uuid: string;
        schedule_id: number;
        employee_ids: number[];
    }): Promise<any> {
        return await this.request(this.resource + '/visit', 'POST', payload);
    }

    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/attendances`;
    }
}

export const attendanceService = AttendanceService.getInstance();
