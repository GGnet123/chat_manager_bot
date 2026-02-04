import { Create, useForm, useSelect } from "@refinedev/antd";
import { useGetIdentity } from "@refinedev/core";
import { Form, Input, Select, Switch } from "antd";
import { useEffect } from "react";
import { USER_ROLE_LABELS } from "../../types";

interface UserIdentity {
    id: number;
    role: string;
    business_id?: number;
    businesses?: { id: number; name: string }[];
}

export const UserCreate = () => {
    const { data: currentUser } = useGetIdentity<UserIdentity>();
    const isSuperAdmin = currentUser?.role === "super_admin";

    const { formProps, saveButtonProps, form } = useForm({
        errorNotification: (error) => ({
            message: "Error",
            description: error?.message || "Failed to create user",
            type: "error",
        }),
    });

    const { selectProps: businessSelectProps } = useSelect({
        resource: "businesses",
        optionLabel: "name",
        optionValue: "id",
    });

    // Auto-fill business for non-super admins
    useEffect(() => {
        if (!isSuperAdmin && currentUser?.business_id) {
            form.setFieldValue("business_ids", [currentUser.business_id]);
        } else if (!isSuperAdmin && currentUser?.businesses?.length) {
            form.setFieldValue("business_ids", [currentUser.businesses[0].id]);
        }
    }, [currentUser, isSuperAdmin, form]);

    // Role options based on current user's role
    const roleOptions = isSuperAdmin
        ? [
            { label: USER_ROLE_LABELS.super_admin, value: "super_admin" },
            { label: USER_ROLE_LABELS.admin_manager, value: "admin_manager" },
            { label: USER_ROLE_LABELS.manager, value: "manager" },
        ]
        : [
            { label: USER_ROLE_LABELS.manager, value: "manager" },
        ];

    return (
        <Create saveButtonProps={saveButtonProps}>
            <Form {...formProps} layout="vertical">
                <Form.Item
                    label="Name"
                    name="name"
                    rules={[{ required: true, message: "Please enter name" }]}
                >
                    <Input placeholder="John Doe" />
                </Form.Item>

                <Form.Item
                    label="Email"
                    name="email"
                    rules={[
                        { required: true, message: "Please enter email" },
                        { type: "email", message: "Please enter a valid email" },
                    ]}
                >
                    <Input placeholder="john@example.com" />
                </Form.Item>

                <Form.Item
                    label="Password"
                    name="password"
                    rules={[
                        { required: true, message: "Please enter password" },
                        { min: 8, message: "Password must be at least 8 characters" },
                    ]}
                >
                    <Input.Password placeholder="Enter password" />
                </Form.Item>

                <Form.Item
                    label="Confirm Password"
                    name="password_confirmation"
                    dependencies={["password"]}
                    rules={[
                        { required: true, message: "Please confirm password" },
                        ({ getFieldValue }) => ({
                            validator(_, value) {
                                if (!value || getFieldValue("password") === value) {
                                    return Promise.resolve();
                                }
                                return Promise.reject(new Error("Passwords do not match"));
                            },
                        }),
                    ]}
                >
                    <Input.Password placeholder="Confirm password" />
                </Form.Item>

                <Form.Item
                    label="Role"
                    name="role"
                    initialValue="manager"
                    rules={[{ required: true, message: "Please select a role" }]}
                >
                    <Select options={roleOptions} />
                </Form.Item>

                {isSuperAdmin ? (
                    <Form.Item
                        label="Assign to Businesses"
                        name="business_ids"
                        help="Select businesses this user will have access to"
                    >
                        <Select
                            {...businessSelectProps}
                            mode="multiple"
                            placeholder="Select businesses"
                            allowClear
                        />
                    </Form.Item>
                ) : (
                    <Form.Item name="business_ids" hidden>
                        <Select mode="multiple" />
                    </Form.Item>
                )}

                <Form.Item
                    label="Active"
                    name="is_active"
                    valuePropName="checked"
                    initialValue={true}
                >
                    <Switch />
                </Form.Item>
            </Form>
        </Create>
    );
};
