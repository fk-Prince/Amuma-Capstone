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

    if (value && typeof value === 'object') {
        return Object.values(
            value as Record<string, unknown>
        ).some(containsFile);
    }

    return false;
};

const buildFormData = (
    data: Record<string, any>,
    formData = new FormData(),
    parentKey?: string,
): FormData => {
    Object.entries(data).forEach(([key, value]) => {
        if (value === null || value === undefined) {
            return;
        }

        const fieldName = parentKey
            ? `${parentKey}[${key}]`
            : key;

        if (isFileLike(value)) {
            formData.append(fieldName, value);
            return;
        }

        if (typeof value === "boolean") {
            formData.append(fieldName, value ? "1" : "0");
            return;
        }

        if (Array.isArray(value)) {
            value.forEach((item, index) => {
                if (item === null || item === undefined) {
                    return;
                }

                const arrayFieldName =
                    `${fieldName}[${index}]`;

                if (isFileLike(item)) {
                    formData.append(arrayFieldName, item);
                } else if (typeof item === 'object') {
                    buildFormData(
                        item as Record<string, any>,
                        formData,
                        arrayFieldName,
                    );
                } else {
                    formData.append(
                        arrayFieldName,
                        String(item),
                    );
                }
            });

            return;
        }

        if (typeof value === 'object') {
            buildFormData(
                value as Record<string, any>,
                formData,
                fieldName,
            );

            return;
        }

        formData.append(fieldName, String(value));
    });

    return formData;
};

export class BaseService {
    private csrfReady = false;

    private async ensureCsrf(): Promise<void> {
        if (this.csrfReady) {
            return;
        }

        const config = useRuntimeConfig();

        await $fetch('/sanctum/csrf-cookie', {
            baseURL: config.public.backendApi,
            credentials: 'include',
        });

        this.csrfReady = true;
    }

    async request<T>(
        url: string,
        method: HttpMethod,
        params: Record<string, any> | FormData = {},
    ): Promise<T> {
        const config = useRuntimeConfig();

        const needsCsrf = [
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
        ].includes(method);

        if (needsCsrf) {
            await this.ensureCsrf();
        }

        const xsrfToken = useCookie('XSRF-TOKEN').value;

        const headers: Record<string, string> = {
            Accept: 'application/json',
        };

        if (needsCsrf && xsrfToken) {
            headers['X-XSRF-TOKEN'] =
                decodeURIComponent(xsrfToken);
        }

        const hasFileInput =
            params instanceof FormData ||
            containsFile(params);

        const body =
            method === 'GET'
                ? undefined
                : hasFileInput
                    ? params instanceof FormData
                        ? params
                        : buildFormData({
                            ...params,
                            _method: method,
                        })
                    : params;

        if (!(body instanceof FormData) && method !== 'GET') {
            headers['Content-Type'] =
                'application/json';
        }

        const requestMethod =
            body instanceof FormData
                ? 'POST'
                : method;

        try {
            return await $fetch<T>(url, {
                baseURL: config.public.backendApi,
                method: requestMethod,
                credentials: 'include',
                headers,
                ...(requestMethod === 'GET'
                    ? { params }
                    : { body }),
            });
        } catch (error: any) {
            if (
                error?.name === 'AbortError' ||
                error?.cause?.name === 'AbortError' ||
                error?.message?.includes(
                    'signal is aborted',
                )
            ) {
                return {
                    data: null,
                    cancelled: true,
                } as T;
            }

            const status = error?.response?.status;
            const data = error?.response?._data;
            const rawMessage =
                data?.message || error?.message || 'Something went wrong';

            const isSqlError = /sqlstate|syntax error|pdoexception|sql:\s/i.test(
                rawMessage,
            );


            throw {
                status,
                message: isSqlError ? 'Internal Server Error' : rawMessage,
                errors: data?.errors || {},
                data,
            };

            // throw {
            //     status,
            //     message:
            //         data?.message ||
            //         error?.message ||
            //         'Something went wrong',
            //     errors: data?.errors || {},
            //     data,
            // };
        }
    }
}

export default BaseService;