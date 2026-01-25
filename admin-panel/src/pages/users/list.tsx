import { List, useTable, EditButton, ShowButton, DeleteButton } from "@refinedev/antd";
import { Table, Space, Tag } from "antd";
import { CheckCircleOutlined, CloseCircleOutlined } from "@ant-design/icons";
import type { User, UserRole } from "../../types";
import { USER_ROLE_LABELS, USER_ROLE_COLORS } from "../../types";

export const UserList = () => {
    const { tableProps } = useTable<User>({
        syncWithLocation: true,
    });

    return (
        <List>
            <Table {...tableProps} rowKey="id">
                <Table.Column dataIndex="id" title="ID" width={80} sorter />
                <Table.Column dataIndex="name" title="Name" sorter />
                <Table.Column dataIndex="email" title="Email" sorter />
                <Table.Column
                    dataIndex="role"
                    title="Role"
                    width={150}
                    render={(value: UserRole) => (
                        <Tag color={USER_ROLE_COLORS[value]}>
                            {USER_ROLE_LABELS[value] || value}
                        </Tag>
                    )}
                />
                <Table.Column
                    dataIndex="businesses"
                    title="Businesses"
                    render={(businesses: User["businesses"]) => (
                        <Space wrap>
                            {businesses?.map((b) => (
                                <Tag key={b.id}>{b.name}</Tag>
                            )) || "-"}
                        </Space>
                    )}
                />
                <Table.Column
                    dataIndex="is_active"
                    title="Status"
                    width={100}
                    render={(value: boolean) =>
                        value ? (
                            <CheckCircleOutlined style={{ color: "green" }} />
                        ) : (
                            <CloseCircleOutlined style={{ color: "red" }} />
                        )
                    }
                />
                <Table.Column
                    title="Actions"
                    width={150}
                    render={(_, record: User) => (
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
