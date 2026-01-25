import { List, useTable, DeleteButton } from "@refinedev/antd";
import { useModalForm } from "@refinedev/antd";
import { Table, Tag, Space, Button, Modal, Form, Input, Select, Switch } from "antd";
import { PlusOutlined } from "@ant-design/icons";

export const NotificationChannelList = () => {
    const { tableProps } = useTable({
        syncWithLocation: true,
    });

    const {
        modalProps: createModalProps,
        formProps: createFormProps,
        show: showCreateModal,
    } = useModalForm({
        action: "create",
        syncWithLocation: true,
    });

    const platformColors: Record<string, string> = {
        whatsapp: "green",
        telegram: "blue",
    };

    return (
        <List
            headerButtons={[
                <Button
                    key="create"
                    type="primary"
                    icon={<PlusOutlined />}
                    onClick={() => showCreateModal()}
                >
                    Add Channel
                </Button>,
            ]}
        >
            <Table {...tableProps} rowKey="id">
                <Table.Column dataIndex="id" title="ID" width={80} />
                <Table.Column dataIndex="name" title="Name" />
                <Table.Column
                    dataIndex="platform"
                    title="Platform"
                    render={(value) => (
                        <Tag color={platformColors[value] || "default"}>
                            {value}
                        </Tag>
                    )}
                />
                <Table.Column dataIndex="chat_id" title="Chat ID" />
                <Table.Column
                    dataIndex="is_active"
                    title="Status"
                    render={(value) => (
                        <Tag color={value ? "green" : "default"}>
                            {value ? "Active" : "Inactive"}
                        </Tag>
                    )}
                />
                <Table.Column
                    title="Actions"
                    render={(_, record: any) => (
                        <Space>
                            <DeleteButton hideText size="small" recordItemId={record.id} />
                        </Space>
                    )}
                />
            </Table>

            <Modal {...createModalProps} title="Add Notification Channel">
                <Form {...createFormProps} layout="vertical">
                    <Form.Item
                        label="Name"
                        name="name"
                        rules={[{ required: true }]}
                    >
                        <Input placeholder="e.g., Managers Group" />
                    </Form.Item>

                    <Form.Item
                        label="Platform"
                        name="platform"
                        rules={[{ required: true }]}
                    >
                        <Select
                            options={[
                                { label: "WhatsApp", value: "whatsapp" },
                                { label: "Telegram", value: "telegram" },
                            ]}
                        />
                    </Form.Item>

                    <Form.Item
                        label="Chat ID"
                        name="chat_id"
                        rules={[{ required: true }]}
                    >
                        <Input placeholder="Group chat ID" />
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
            </Modal>
        </List>
    );
};
