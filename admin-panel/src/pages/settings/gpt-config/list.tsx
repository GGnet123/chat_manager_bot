import {
    List,
    useTable,
    EditButton,
    DeleteButton,
} from "@refinedev/antd";
import { Table, Tag, Space, Button, message } from "antd";
import { CheckCircleOutlined } from "@ant-design/icons";

export const GptConfigList = () => {
    const { tableProps, tableQueryResult } = useTable({
        syncWithLocation: true,
    });

    const handleActivate = async (id: number) => {
        try {
            const response = await fetch(`/api/v1/admin/gpt-configs/${id}/activate`, {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                },
            });

            if (response.ok) {
                message.success("Configuration activated");
                tableQueryResult.refetch();
            } else {
                message.error("Failed to activate configuration");
            }
        } catch (error) {
            message.error("Failed to activate configuration");
        }
    };

    return (
        <List>
            <Table {...tableProps} rowKey="id">
                <Table.Column dataIndex="id" title="ID" width={80} />
                <Table.Column dataIndex="name" title="Name" />
                <Table.Column dataIndex="model" title="Model" />
                <Table.Column dataIndex="max_tokens" title="Max Tokens" />
                <Table.Column
                    dataIndex="temperature"
                    title="Temperature"
                    render={(value) => value?.toFixed(2)}
                />
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
                            {!record.is_active && (
                                <Button
                                    type="primary"
                                    size="small"
                                    icon={<CheckCircleOutlined />}
                                    onClick={() => handleActivate(record.id)}
                                >
                                    Activate
                                </Button>
                            )}
                            <EditButton hideText size="small" recordItemId={record.id} />
                            <DeleteButton
                                hideText
                                size="small"
                                recordItemId={record.id}
                            />
                        </Space>
                    )}
                />
            </Table>
        </List>
    );
};
