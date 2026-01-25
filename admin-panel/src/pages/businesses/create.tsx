import { Create, useForm } from "@refinedev/antd";
import { Form, Input, Switch } from "antd";

export const BusinessCreate = () => {
    const { formProps, saveButtonProps } = useForm();

    return (
        <Create saveButtonProps={saveButtonProps}>
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
                    help="Leave empty to auto-generate from name"
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
                    help="Permanent access token from Meta"
                >
                    <Input.Password placeholder="Enter access token" />
                </Form.Item>

                <Form.Item
                    label="Telegram Bot Token"
                    name="telegram_bot_token"
                    help="From @BotFather"
                >
                    <Input.Password placeholder="e.g., 123456:ABC-DEF..." />
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
