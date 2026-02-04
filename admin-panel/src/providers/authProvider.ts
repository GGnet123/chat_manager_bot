import type { AuthProvider } from "@refinedev/core";
import axios from "axios";

// API URL - uses api.aibotchat.xyz subdomain in production
const API_URL = import.meta.env.VITE_API_URL
    ? `${import.meta.env.VITE_API_URL}/v1`
    : "/v1";

export const authProvider: AuthProvider = {
    login: async ({ email, password }) => {
        try {
            const response = await axios.post(`${API_URL}/auth/login`, {
                email,
                password,
            });

            const { token, user } = response.data;

            localStorage.setItem("token", token);
            localStorage.setItem("user", JSON.stringify(user));

            return {
                success: true,
                redirectTo: "/",
            };
        } catch (error: any) {
            return {
                success: false,
                error: {
                    name: "LoginError",
                    message: error.response?.data?.message || "Invalid credentials",
                },
            };
        }
    },

    logout: async () => {
        const token = localStorage.getItem("token");

        if (token) {
            try {
                await axios.post(
                    `${API_URL}/auth/logout`,
                    {},
                    {
                        headers: {
                            Authorization: `Bearer ${token}`,
                        },
                    }
                );
            } catch (error) {
                // Ignore errors on logout
            }
        }

        localStorage.removeItem("token");
        localStorage.removeItem("user");

        return {
            success: true,
            redirectTo: "/login",
        };
    },

    check: async () => {
        const token = localStorage.getItem("token");

        if (!token) {
            return {
                authenticated: false,
                redirectTo: "/login",
            };
        }

        try {
            await axios.get(`${API_URL}/auth/user`, {
                headers: {
                    Authorization: `Bearer ${token}`,
                },
            });

            return {
                authenticated: true,
            };
        } catch (error) {
            localStorage.removeItem("token");
            localStorage.removeItem("user");

            return {
                authenticated: false,
                redirectTo: "/login",
            };
        }
    },

    getPermissions: async () => {
        const user = localStorage.getItem("user");
        if (user) {
            const { role } = JSON.parse(user);
            return role;
        }
        return null;
    },

    getIdentity: async () => {
        const user = localStorage.getItem("user");
        if (user) {
            const parsed = JSON.parse(user);
            return {
                id: parsed.id,
                name: parsed.name,
                email: parsed.email,
                role: parsed.role,
                business_id: parsed.business_id,
                businesses: parsed.businesses,
                avatar: undefined,
            };
        }
        return null;
    },

    onError: async (error) => {
        if (error.response?.status === 401) {
            return {
                logout: true,
                redirectTo: "/login",
            };
        }
        return { error };
    },
};
