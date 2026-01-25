import { Edit, useForm } from "@refinedev/antd";
import { Form, Input, Select, Switch } from "antd";
import { USER_ROLE_LABELS } from "../../types";

export const UserEdit = () => {
    const { formProps, saveButtonProps } = useForm();

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
                        options={[
                            { label: USER_ROLE_LABELS.super_admin, value: "super_admin" },
                            { label: USER_ROLE_LABELS.admin_manager, value: "admin_manager" },
                            { label: USER_ROLE_LABELS.manager, value: "manager" },
                        ]}
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
