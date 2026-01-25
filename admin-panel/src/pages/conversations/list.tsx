import { List, useTable, ShowButton, TagField } from "@refinedev/antd";
import { Table, Tag, Space } from "antd";

export const ConversationList = () => {
    const { tableProps } = useTable({
        syncWithLocation: true,
    });

    const platformColors: Record<string, string> = {
        whatsapp: "green",
        telegram: "blue",
    };

    const statusColors: Record<string, string> = {
        active: "green",
        closed: "default",
    };

    return (
        <List>
            <Table {...tableProps} rowKey="id">
                <Table.Column dataIndex="id" title="ID" width={80} />
                <Table.Column
                    dataIndex="platform"
                    title="Platform"
                    render={(value) => (
                        <Tag color={platformColors[value] || "default"}>
                            {value}
                        </Tag>
                    )}
                />
                <Table.Column
                    dataIndex={["client", "name"]}
                    title="Client"
                    render={(value, record: any) =>
                        value || record.client?.phone || record.client?.telegram_id || "-"
                    }
                />
                <Table.Column
                    dataIndex={["client", "phone"]}
                    title="Phone"
                    render={(value) => value || "-"}
                />
                <Table.Column
                    dataIndex="status"
                    title="Status"
                    render={(value) => (
                        <TagField
                            value={value}
                            color={statusColors[value] || "default"}
                        />
                    )}
                />
                <Table.Column
                    dataIndex="last_message_at"
                    title="Last Message"
                    render={(value) =>
                        value ? new Date(value).toLocaleString() : "-"
                    }
                />
                <Table.Column
                    dataIndex="created_at"
                    title="Started"
                    render={(value) => new Date(value).toLocaleString()}
                />
                <Table.Column
                    title="Actions"
                    render={(_, record: any) => (
                        <Space>
                            <ShowButton
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
