import { Edit, useForm } from "@refinedev/antd";
import { Form, Input, Switch } from "antd";

export const BusinessEdit = () => {
    const { formProps, saveButtonProps } = useForm({
        errorNotification: (error) => ({
            message: "Error",
            description: error?.message || "Failed to update business",
            type: "error",
        }),
    });

    return (
        <Edit saveButtonProps={saveButtonProps}>
            <Form {...formProps} layout="vertical">
                <Form.Item
                    label="Name"
                    name="name"
                    rules={[{ required: true, message: "Please enter business name" }]}
                >
                    <Input placeholder="e.g., Pizza Palace" />
                </Form.Item>

                <Form.Item
                    label="Slug"
                    name="slug"
                    rules={[{ required: true, message: "Please enter slug" }]}
                >
                    <Input placeholder="e.g., pizza-palace" />
                </Form.Item>

                <Form.Item
                    label="WhatsApp Phone ID"
                    name="whatsapp_phone_id"
                    help="From Meta Business Suite"
                >
                    <Input placeholder="e.g., 123456789012345" />
                </Form.Item>

                <Form.Item
                    label="WhatsApp Access Token"
                    name="whatsapp_access_token"
                    help="Leave empty to keep existing token"
                >
                    <Input.Password placeholder="Enter new access token" />
                </Form.Item>

                <Form.Item
                    label="Telegram Bot Token"
                    name="telegram_bot_token"
                    help="Leave empty to keep existing token"
                >
                    <Input.Password placeholder="Enter new bot token" />
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
