import { DataProvider, HttpError } from "@refinedev/core";
import axios, { AxiosError } from "axios";

const API_URL = "/api/v1/admin";

const axiosInstance = axios.create();

// Add auth token to requests
axiosInstance.interceptors.request.use((config) => {
    const token = localStorage.getItem("token");
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Transform errors to Refine's HttpError format
const transformError = (error: AxiosError<any>): HttpError => {
    const status = error.response?.status || 500;
    const data = error.response?.data;

    // Extract message from Laravel response
    let message = "An unexpected error occurred";
    let errors: Record<string, string[]> | undefined;

    if (data) {
        // Laravel validation errors (422)
        if (data.errors && typeof data.errors === "object") {
            errors = data.errors;
            // Combine all validation errors into one message
            const errorMessages = Object.entries(data.errors)
                .map(([field, msgs]) => `${field}: ${(msgs as string[]).join(", ")}`)
                .join("\n");
            message = data.message ? `${data.message}\n${errorMessages}` : errorMessages;
        }
        // Standard Laravel error message
        else if (data.message) {
            message = data.message;
        }
        // Fallback to error string
        else if (typeof data === "string") {
            message = data;
        }
    }

    const httpError: HttpError = {
        message,
        statusCode: status,
        errors,
    };

    return httpError;
};

// Handle response errors
axiosInstance.interceptors.response.use(
    (response) => response,
    (error: AxiosError<any>) => {
        // Handle 401 - redirect to login
        if (error.response?.status === 401) {
            localStorage.removeItem("token");
            localStorage.removeItem("user");
            window.location.href = "/login";
        }

        // Transform to HttpError for Refine
        const httpError = transformError(error);
        return Promise.reject(httpError);
    }
);

export const dataProvider: DataProvider = {
    getList: async ({ resource, pagination, filters, sorters }) => {
        const url = `${API_URL}/${resource}`;

        const { current = 1, pageSize = 10 } = pagination ?? {};

        const params: Record<string, any> = {
            page: current,
            per_page: pageSize,
        };

        // Add filters
        if (filters) {
            filters.forEach((filter: any) => {
                if (filter.operator === "eq") {
                    params[filter.field] = filter.value;
                } else if (filter.operator === "contains") {
                    params[`${filter.field}`] = filter.value;
                }
            });
        }

        // Add sorting
        if (sorters && sorters.length > 0) {
            params.sort = sorters[0].field;
            params.order = sorters[0].order;
        }

        const { data } = await axiosInstance.get(url, { params });

        // Handle different response formats
        const responseData = data.data ?? data ?? [];
        const total = data.meta?.total ?? (Array.isArray(responseData) ? responseData.length : 0);

        return {
            data: responseData,
            total,
        };
    },

    getOne: async ({ resource, id }) => {
        const url = `${API_URL}/${resource}/${id}`;
        const { data } = await axiosInstance.get(url);

        return {
            data: data.data ?? data,
        };
    },

    create: async ({ resource, variables }) => {
        const url = `${API_URL}/${resource}`;
        const { data } = await axiosInstance.post(url, variables);

        return {
            data: data.data ?? data,
        };
    },

    update: async ({ resource, id, variables }) => {
        const url = `${API_URL}/${resource}/${id}`;
        const { data } = await axiosInstance.put(url, variables);

        return {
            data: data.data ?? data,
        };
    },

    deleteOne: async ({ resource, id }) => {
        const url = `${API_URL}/${resource}/${id}`;
        const { data } = await axiosInstance.delete(url);

        return {
            data: data.data ?? data,
        };
    },

    getApiUrl: () => API_URL,

    custom: async ({ url, method, filters, payload, headers }) => {
        let requestUrl = `${API_URL}${url}`;

        if (filters) {
            const params = new URLSearchParams();
            filters.forEach((filter: any) => {
                params.append(filter.field, filter.value);
            });
            requestUrl = `${requestUrl}?${params.toString()}`;
        }

        const { data } = await axiosInstance({
            method,
            url: requestUrl,
            data: payload,
            headers,
        });

        return { data };
    },
};
