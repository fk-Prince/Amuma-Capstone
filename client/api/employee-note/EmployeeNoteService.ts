import BaseService from "~/api/BaseService";

class EmployeeNoteService extends BaseService {
    private static instance: EmployeeNoteService;

    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }

    public static getInstance(): EmployeeNoteService {
        if (!EmployeeNoteService.instance) {
            EmployeeNoteService.instance = new EmployeeNoteService();
        }
        return EmployeeNoteService.instance;
    }

    async list(params: object = {}): Promise<any> {
        return await this.request(this.resource, "GET", params);
    }

    async create(payload: object): Promise<any> {
        return await this.request(this.resource, "POST", payload);
    }

    async update(uuid: string, payload: object): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, "PUT", payload);
    }

    async remove(uuid: string, payload: object): Promise<any> {
        return await this.request(`${this.resource}/${uuid}`, "DELETE", payload);
    }

    private get resource(): string {
        return `${this.getBackendApi}/api/employee-notes`;
    }
}

export const employeeNoteService = EmployeeNoteService.getInstance();
