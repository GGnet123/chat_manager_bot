export interface Business {
    id: number;
    name: string;
    slug: string;
    whatsapp_phone_id?: string;
    has_whatsapp: boolean;
    has_telegram: boolean;
    settings?: Record<string, unknown>;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    users?: BusinessUser[];
    gpt_configurations?: GptConfiguration[];
}

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    business_id?: number;
    is_active: boolean;
    email_verified_at?: string;
    created_at: string;
    updated_at: string;
    pivot_role?: BusinessUserRole;
    businesses?: UserBusiness[];
}

export interface BusinessUser {
    id: number;
    name: string;
    email: string;
    role: BusinessUserRole;
}

export interface UserBusiness {
    id: number;
    name: string;
    slug: string;
    role: BusinessUserRole;
}

export interface GptConfiguration {
    id: number;
    business_id: number;
    name: string;
    model: string;
    temperature: number;
    max_tokens: number;
    system_prompt?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export type UserRole = 'super_admin' | 'admin_manager' | 'manager';
export type BusinessUserRole = 'admin_manager' | 'manager';

export const USER_ROLE_LABELS: Record<UserRole, string> = {
    super_admin: 'Super Admin',
    admin_manager: 'Admin Manager',
    manager: 'Manager',
};

export const BUSINESS_USER_ROLE_LABELS: Record<BusinessUserRole, string> = {
    admin_manager: 'Admin Manager',
    manager: 'Manager',
};

export const USER_ROLE_COLORS: Record<UserRole, string> = {
    super_admin: 'red',
    admin_manager: 'orange',
    manager: 'blue',
};

export const BUSINESS_USER_ROLE_COLORS: Record<BusinessUserRole, string> = {
    admin_manager: 'orange',
    manager: 'blue',
};
