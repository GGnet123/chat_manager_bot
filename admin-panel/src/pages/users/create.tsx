import { Create, useForm, useSelect } from "@refinedev/antd";
import { Form, Input, Select, Switch } from "antd";
import { USER_ROLE_LABELS } from "../../types";

export const UserCreate = () => {
    const { formProps, saveButtonProps } = useForm();

    const { selectProps: businessSelectProps } = useSelect({
        resource: "businesses",
        optionLabel: "name",
        optionValue: "id",
    });

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
                    <Select
                        options={[
                            { label: USER_ROLE_LABELS.super_admin, value: "super_admin" },
                            { label: USER_ROLE_LABELS.admin_manager, value: "admin_manager" },
                            { label: USER_ROLE_LABELS.manager, value: "manager" },
                        ]}
                    />
                </Form.Item>

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
