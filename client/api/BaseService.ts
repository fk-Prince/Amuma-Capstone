type HttpMethod =
    | 'GET'
    | 'POST'
    | 'PUT'
    | 'PATCH'
    | 'DELETE';

export class BaseService {
    private async getCsrfToken() {
        const config = useRuntimeConfig()

        await $fetch('/sanctum/csrf-cookie', {
            baseURL: config.public.backendApi,
            credentials: 'include',
        })
    }

    async request<T>(
        url: string,
        method: HttpMethod,
        params: Record<string, any> = {}
    ): Promise<T> {
        const config = useRuntimeConfig()

        // if (method !== 'GET') {
        //     await this.getCsrfToken()
        // }

        const xsrfToken = useCookie('XSRF-TOKEN').value

        const headers: Record<string, string> = {
            Accept: 'application/json',
        }

        if (xsrfToken) {
            headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken)
        }

        try {
            return await $fetch<T>(url, {
                baseURL: config.public.backendApi,
                method,
                credentials: 'include',
                headers,
                ...(method === 'GET'
                    ? { params }
                    : { body: params }),
            })
        } catch (error: any) {
            const status = error?.response?.status
            const data = error?.response?._data

            throw {
                status,
                message:
                    data?.message ||
                    error?.message ||
                    'Something went wrong',
                errors: data?.errors || {},
                data,
            }
        }
    }
}

export default BaseService;

// // import { toFormData } from "~/utils/formData";

// type HttpMethod =
//     | 'GET'
//     | 'POST'
//     | 'PUT'
//     | 'PATCH'
//     | 'DELETE';

// export class BaseService {
//     private async getCsrfToken() {
//         const config = useRuntimeConfig()

//         await $fetch('/sanctum/csrf-cookie', {
//             baseURL: config.public.backendApi,
//             credentials: 'include',
//         })
//     }

//     async request<T>(
//         url: string,
//         method: HttpMethod,
//         params: Record<string, any> = {}
//     ): Promise<T> {
//         const config = useRuntimeConfig()

//         // if (method !== 'GET') {
//         //     await this.getCsrfToken()
//         // }

//         const xsrfToken = useCookie('XSRF-TOKEN').value

//         const headers: Record<string, string> = {
//             Accept: 'application/json',
//         }

//         if (xsrfToken) {
//             headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken)
//         }

//         const body =
//             method === 'GET'
//                 ? undefined
//                 : params instanceof FormData
//                     ? params
//                     : toFormData(params);

//         const isFormDataBody = body instanceof FormData;

//         if (isFormDataBody) {
//             delete headers['Content-Type'];
//         } else {
//             headers['Content-Type'] = 'application/json';
//         }

//         try {
//             return await $fetch<T>(url, {
//                 baseURL: config.public.backendApi,
//                 method,
//                 credentials: 'include',
//                 headers,
//                 ...(method === 'GET'
//                     ? { params }
//                     : { body }),
//             })
//         } catch (error: any) {
//             const status = error?.response?.status
//             const data = error?.response?._data

//             throw {
//                 status,
//                 message:
//                     data?.message ||
//                     error?.message ||
//                     'Something went wrong',
//                 errors: data?.errors || {},
//                 data,
//             }
//         }
//     }
// }

// export default BaseService;
