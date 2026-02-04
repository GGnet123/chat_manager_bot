import { Edit, useForm } from "@refinedev/antd";
import { useGetIdentity } from "@refinedev/core";
import { Form, Input, Select, Switch } from "antd";
import { USER_ROLE_LABELS } from "../../types";

interface UserIdentity {
    id: number;
    role: string;
    business_id?: number;
}

export const UserEdit = () => {
    const { data: currentUser } = useGetIdentity<UserIdentity>();
    const isSuperAdmin = currentUser?.role === "super_admin";

    const { formProps, saveButtonProps } = useForm({
        errorNotification: (error) => ({
            message: "Error",
            description: error?.message || "Failed to update user",
            type: "error",
        }),
    });

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
        <Edit saveButtonProps={saveButtonProps}>
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
                    label="New Password"
                    name="password"
                    help="Leave empty to keep current password"
                    rules={[
                        { min: 8, message: "Password must be at least 8 characters" },
                    ]}
                >
                    <Input.Password placeholder="Enter new password" />
                </Form.Item>

                <Form.Item
                    label="Confirm New Password"
                    name="password_confirmation"
                    dependencies={["password"]}
                    rules={[
                        ({ getFieldValue }) => ({
                            validator(_, value) {
                                const password = getFieldValue("password");
                                if (!password || !value || password === value) {
                                    return Promise.resolve();
                                }
                                return Promise.reject(new Error("Passwords do not match"));
                            },
                        }),
                    ]}
                >
                    <Input.Password placeholder="Confirm new password" />
                </Form.Item>

                <Form.Item
                    label="Role"
                    name="role"
                    rules={[{ required: true, message: "Please select a role" }]}
                >
                    <Select
                        options={roleOptions}
                        disabled={!isSuperAdmin}
                    />
                </Form.Item>

                <Form.Item
                    label="Active"
                    name="is_active"
                    valuePropName="checked"
                >
                    <Switch />
                </Form.Item>
            </Form>
        </Edit>
    );
};
