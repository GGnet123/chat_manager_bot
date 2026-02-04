import { Edit, useForm, useSelect } from "@refinedev/antd";
import { useGetIdentity } from "@refinedev/core";
import { Form, Input, Select, Switch } from "antd";

const { TextArea } = Input;

interface UserIdentity {
    id: number;
    role: string;
    business_id?: number;
}

export const PromptEdit = () => {
    const { data: user } = useGetIdentity<UserIdentity>();
    const isSuperAdmin = user?.role === "super_admin";

    const { formProps, saveButtonProps } = useForm({
        errorNotification: (error) => ({
            message: "Error",
            description: error?.message || "Failed to update prompt",
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
