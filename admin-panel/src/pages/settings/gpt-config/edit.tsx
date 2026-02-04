import { Edit, useForm, useSelect } from "@refinedev/antd";
import { useGetIdentity } from "@refinedev/core";
import { Form, Input, InputNumber, Select, Switch } from "antd";

const { TextArea } = Input;

interface UserIdentity {
    id: number;
    name: string;
    email: string;
    role: string;
    business_id?: number;
}

export const GptConfigEdit = () => {
    const { data: user } = useGetIdentity<UserIdentity>();
    const isSuperAdmin = user?.role === "super_admin";

    const { formProps, saveButtonProps } = useForm({
        errorNotification: (error) => ({
            message: "Error",
            description: error?.message || "Failed to update GPT config",
            type: "error",
        }),
    });

    const { selectProps: businessSelectProps } = useSelect({
        resource: "businesses",
        optionLabel: "name",
        optionValue: "id",
    });

    return (
        <Edit saveButtonProps={saveButtonProps}>
            <Form {...formProps} layout="vertical">
                {isSuperAdmin && (
                    <Form.Item
                        label="Business"
                        name="business_id"
                        rules={[{ required: true, message: "Please select a business" }]}
                    >
                        <Select
                            {...businessSelectProps}
                            placeholder="Select business"
                        />
                    </Form.Item>
                )}

                <Form.Item
                    label="Name"
                    name="name"
                    rules={[{ required: true }]}
                >
                    <Input />
                </Form.Item>

                <Form.Item
                    label="Model"
                    name="model"
                    rules={[{ required: true }]}
                >
                    <Select
                        options={[
                            { label: "GPT-4 Turbo", value: "gpt-4-turbo-preview" },
                            { label: "GPT-4", value: "gpt-4" },
                            { label: "GPT-3.5 Turbo", value: "gpt-3.5-turbo" },
                        ]}
                    />
                </Form.Item>

                <Form.Item
                    label="Max Tokens"
                    name="max_tokens"
                    rules={[{ required: true }]}
                >
                    <InputNumber min={1} max={4096} style={{ width: "100%" }} />
                </Form.Item>

                <Form.Item
                    label="Temperature"
                    name="temperature"
                    rules={[{ required: true }]}
                >
                    <InputNumber
                        min={0}
                        max={2}
                        step={0.1}
                        style={{ width: "100%" }}
                    />
                </Form.Item>

                <Form.Item label="System Prompt" name="system_prompt">
                    <TextArea rows={6} />
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
