import { Create, useForm } from "@refinedev/antd";
import { Form, Input, InputNumber, Select, Switch } from "antd";

const { TextArea } = Input;

export const GptConfigCreate = () => {
    const { formProps, saveButtonProps } = useForm();

    return (
        <Create saveButtonProps={saveButtonProps}>
            <Form {...formProps} layout="vertical">
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
                    initialValue="gpt-4-turbo-preview"
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
                    initialValue={1000}
                >
                    <InputNumber min={1} max={4096} style={{ width: "100%" }} />
                </Form.Item>

                <Form.Item
                    label="Temperature"
                    name="temperature"
                    rules={[{ required: true }]}
                    initialValue={0.7}
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
                    initialValue={false}
                >
                    <Switch />
                </Form.Item>
            </Form>
        </Create>
    );
};
