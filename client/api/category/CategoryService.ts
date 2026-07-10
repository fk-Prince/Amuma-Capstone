import BaseService from '~/api/BaseService';

class CategoryService extends BaseService {
    private static instance: CategoryService;


    private get getBackendApi(): string {
        const config = useRuntimeConfig();
        return config.public.backendApi;
    }
    public static getInstance(): CategoryService {
        if (!CategoryService.instance) {
            CategoryService.instance = new CategoryService();
        }
        return CategoryService.instance;
    }


    async list(params: object = {}): Promise<any> {
        return await this.request(this.resource, 'GET', params);
    }


    private get resource(): string {
        const backend = this.getBackendApi;
        return `${backend}/api/categories`;
    }
}

export const categoryService = CategoryService.getInstance();
