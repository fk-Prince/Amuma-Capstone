// type HttpMethod =
//     | 'GET'
//     | 'POST'
//     | 'PUT'
//     | 'PATCH'
//     | 'DELETE';

// const isFileLike = (value: unknown): value is File | Blob =>
//     value instanceof File || value instanceof Blob;

// const buildFormData = (
//     data: Record<string, any>,
//     formData = new FormData(),
//     parentKey?: string,
// ): FormData => {
//     Object.entries(data).forEach(([key, value]) => {
//         if (value === null || value === undefined) return;

//         const fieldName = parentKey ? `${parentKey}[${key}]` : key;

//         if (isFileLike(value)) {
//             formData.append(fieldName, value);
//             return;
//         }

//         if (Array.isArray(value)) {
//             value.forEach((item, index) => {
//                 if (item === null || item === undefined) return;

//                 const arrayFieldName = `${fieldName}[${index}]`;

//                 if (isFileLike(item)) {
//                     formData.append(arrayFieldName, item);
//                 } else if (typeof item === 'object') {
//                     buildFormData(item as Record<string, any>, formData, arrayFieldName);
//                 } else {
//                     formData.append(arrayFieldName, String(item));
//                 }
//             });
//             return;
//         }

//         if (typeof value === 'object') {

//             const containsFile = (value: unknown): boolean => {
//                 if (isFileLike(value)) {
//                     return true;
//                 }

//                 if (Array.isArray(value)) {
//                     return value.some(containsFile);
//                 }

//                 if (value && typeof value === "object") {
//                     return Object.values(value as Record<string, unknown>).some(containsFile);
//                 }

//                 return false;
//             };

//             buildFormData(value as Record<string, any>, formData, fieldName);
//             return;
//         }

//         formData.append(fieldName, String(value));
//     });

//     return formData;
// };

// export class BaseService {

//     async request<T>(
//         url: string,
//         method: HttpMethod,
//         params: Record<string, any> | FormData = {}
//     ): Promise<T> {
//         const config = useRuntimeConfig()

//         const xsrfToken = useCookie('XSRF-TOKEN').value

//         const headers: Record<string, string> = {
//             Accept: 'application/json',
//         }

//         if (xsrfToken) {
//             headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken)
//         }

//         const hasFileInput =
//             params instanceof FormData ||
//             Object.values(params as Record<string, any>).some((value) =>
//                 isFileLike(value) ||
//                 (Array.isArray(value) && value.some((item) => isFileLike(item))) ||
//                 (value && typeof value === 'object' && Object.values(value).some((nestedValue) =>
//                     isFileLike(nestedValue) ||
//                     (Array.isArray(nestedValue) && nestedValue.some((item) => isFileLike(item)))
//                 )),
//             );


//         const body = method === 'GET'
//             ? undefined
//             : hasFileInput
//                 ? buildFormData({
//                     ...params,
//                     _method: method,
//                 })
//                 : params;

//         if (body instanceof FormData) {
//             delete headers['Content-Type'];
//         } else {
//             headers['Content-Type'] = 'application/json';
//         }

//         try {
//             // return await $fetch<T>(url, {
//             //     baseURL: config.public.backendApi,
//             //     method,
//             //     credentials: 'include',
//             //     headers,
//             //     ...(method === 'GET'
//             //         ? { params }
//             //         : { body }),
//             // })
//             const requestMethod =
//                 body instanceof FormData ? "POST" : method;


//             return await $fetch<T>(url, {
//                 baseURL: config.public.backendApi,
//                 method: requestMethod,
//                 credentials: "include",
//                 headers,
//                 ...(requestMethod === "GET"
//                     ? { params }
//                     : { body }),
//             });
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

type HttpMethod =
    | 'GET'
    | 'POST'
    | 'PUT'
    | 'PATCH'
    | 'DELETE';

const isFileLike = (value: unknown): value is File | Blob =>
    value instanceof File || value instanceof Blob;

const containsFile = (value: unknown): boolean => {
    if (isFileLike(value)) {
        return true;
    }

    if (Array.isArray(value)) {
        return value.some(containsFile);
    }

    if (value && typeof value === "object") {
        return Object.values(value as Record<string, unknown>).some(containsFile);
    }

    return false;
};
const buildFormData = (
    data: Record<string, any>,
    formData = new FormData(),
    parentKey?: string,
): FormData => {
    Object.entries(data).forEach(([key, value]) => {
        if (value === null || value === undefined) return;

        const fieldName = parentKey ? `${parentKey}[${key}]` : key;

        if (isFileLike(value)) {
            formData.append(fieldName, value);
            return;
        }

        if (Array.isArray(value)) {
            value.forEach((item, index) => {
                if (item === null || item === undefined) return;

                const arrayFieldName = `${fieldName}[${index}]`;

                if (isFileLike(item)) {
                    formData.append(arrayFieldName, item);
                } else if (typeof item === 'object') {
                    buildFormData(item as Record<string, any>, formData, arrayFieldName);
                } else {
                    formData.append(arrayFieldName, String(item));
                }
            });
            return;
        }

        if (typeof value === 'object') {



            buildFormData(value as Record<string, any>, formData, fieldName);
            return;
        }

        formData.append(fieldName, String(value));
    });

    return formData;
};

export class BaseService {

    async request<T>(
        url: string,
        method: HttpMethod,
        params: Record<string, any> | FormData = {}
    ): Promise<T> {
        const config = useRuntimeConfig()

        const xsrfToken = useCookie('XSRF-TOKEN').value

        const headers: Record<string, string> = {
            Accept: 'application/json',
        }

        if (xsrfToken) {
            headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken)
        }

        // const hasFileInput =
        //     params instanceof FormData ||
        //     Object.values(params as Record<string, any>).some((value) =>
        //         isFileLike(value) ||
        //         (Array.isArray(value) && value.some((item) => isFileLike(item))) ||
        //         (value && typeof value === 'object' && Object.values(value).some((nestedValue) =>
        //             isFileLike(nestedValue) ||
        //             (Array.isArray(nestedValue) && nestedValue.some((item) => isFileLike(item)))
        //         )),
        //     );
        const hasFileInput =
            params instanceof FormData || containsFile(params);


        const body = method === 'GET'
            ? undefined
            : hasFileInput
                ? buildFormData({
                    ...params,
                    _method: method,
                })
                : params;



        if (body instanceof FormData) {
            delete headers['Content-Type'];
        } else {
            headers['Content-Type'] = 'application/json';
        }

        try {
            // return await $fetch<T>(url, {
            //     baseURL: config.public.backendApi,
            //     method,
            //     credentials: 'include',
            //     headers,
            //     ...(method === 'GET'
            //         ? { params }
            //         : { body }),
            // })
            const requestMethod =
                body instanceof FormData ? "POST" : method;


            return await $fetch<T>(url, {
                baseURL: config.public.backendApi,
                method: requestMethod,
                credentials: "include",
                headers,
                ...(requestMethod === "GET"
                    ? { params }
                    : { body }),
            });
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
