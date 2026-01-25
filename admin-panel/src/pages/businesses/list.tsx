import { List, useTable, EditButton, ShowButton, DeleteButton, TagField } from "@refinedev/antd";
import { Table, Space } from "antd";
import { CheckCircleOutlined, CloseCircleOutlined } from "@ant-design/icons";
import type { Business } from "../../types";

export const BusinessList = () => {
    const { tableProps } = useTable<Business>({
        syncWithLocation: true,
    });

    return (
        <List>
            <Table {...tableProps} rowKey="id">
                <Table.Column dataIndex="id" title="ID" width={80} sorter />
                <Table.Column dataIndex="name" title="Name" sorter />
                <Table.Column dataIndex="slug" title="Slug" />
                <Table.Column
                    dataIndex="has_whatsapp"
                    title="WhatsApp"
                    width={100}
                    render={(value: boolean) =>
                        value ? (
                            <CheckCircleOutlined style={{ color: "green" }} />
                        ) : (
                            <CloseCircleOutlined style={{ color: "#ccc" }} />
                        )
                    }
                />
                <Table.Column
                    dataIndex="has_telegram"
                    title="Telegram"
                    width={100}
                    render={(value: boolean) =>
                        value ? (
                            <CheckCircleOutlined style={{ color: "green" }} />
                        ) : (
                            <CloseCircleOutlined style={{ color: "#ccc" }} />
                        )
                    }
                />
                <Table.Column
                    dataIndex="is_active"
                    title="Status"
                    width={100}
                    render={(value: boolean) => (
                        <TagField
                            value={value ? "Active" : "Inactive"}
                            color={value ? "green" : "default"}
                        />
                    )}
                />
                <Table.Column
                    title="Actions"
                    width={150}
                    render={(_, record: Business) => (
                        <Space>
                            <ShowButton hideText size="small" recordItemId={record.id} />
                            <EditButton hideText size="small" recordItemId={record.id} />
                            <DeleteButton hideText size="small" recordItemId={record.id} />
                        </Space>
                    )}
                />
            </Table>
        </List>
    );
};
