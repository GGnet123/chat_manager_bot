import { List, useTable, EditButton, DeleteButton } from "@refinedev/antd";
import { Table, Tag, Space } from "antd";

export const PromptList = () => {
    const { tableProps } = useTable({
        syncWithLocation: true,
    });

    return (
        <List>
            <Table {...tableProps} rowKey="id">
                <Table.Column dataIndex="id" title="ID" width={80} />
                <Table.Column dataIndex="name" title="Name" />
                <Table.Column
                    dataIndex="type"
                    title="Type"
                    render={(value) => <Tag color="blue">{value}</Tag>}
                />
                <Table.Column
                    dataIndex="content"
                    title="Content Preview"
                    render={(value) =>
                        value?.length > 100
                            ? `${value.substring(0, 100)}...`
                            : value
                    }
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
                            <EditButton hideText size="small" recordItemId={record.id} />
                            <DeleteButton hideText size="small" recordItemId={record.id} />
                        </Space>
                    )}
                />
            </Table>
        </List>
    );
};
