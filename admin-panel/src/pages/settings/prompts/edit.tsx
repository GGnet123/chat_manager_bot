import { Edit, useForm } from "@refinedev/antd";
import { Form, Input, Select, Switch } from "antd";

const { TextArea } = Input;

export const PromptEdit = () => {
    const { formProps, saveButtonProps } = useForm();

    return (
        <Edit saveButtonProps={saveButtonProps}>
            <Form {...formProps} layout="vertical">
                <Form.Item
                    label="Name"
                    name="name"
                    rules={[{ required: true }]}
                >
                    <Input />
                </Form.Item>

                <Form.Item
                    label="Type"
                    name="type"
                    rules={[{ required: true }]}
                >
                    <Select
                        options={[
                            { label: "System", value: "system" },
                            { label: "Welcome", value: "welcome" },
                            { label: "Error", value: "error" },
                            { label: "Confirmation", value: "confirmation" },
                            { label: "Custom", value: "custom" },
                        ]}
                    />
                </Form.Item>

                <Form.Item
                    label="Content"
                    name="content"
                    rules={[{ required: true }]}
                >
                    <TextArea rows={10} />
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
