import { useShow } from "@refinedev/core";
import { Show } from "@refinedev/antd";
import { Card, Descriptions, Table, Tag, Space } from "antd";
import { CheckCircleOutlined, CloseCircleOutlined } from "@ant-design/icons";
import type { User, UserBusiness } from "../../types";
import { USER_ROLE_LABELS, USER_ROLE_COLORS, BUSINESS_USER_ROLE_LABELS, BUSINESS_USER_ROLE_COLORS } from "../../types";

export const UserShow = () => {
    const { queryResult } = useShow<User>();
    const { data, isLoading } = queryResult;
    const record = data?.data;

    return (
        <Show isLoading={isLoading}>
            <Card title="User Details" style={{ marginBottom: 16 }}>
                <Descriptions bordered column={2}>
                    <Descriptions.Item label="ID">{record?.id}</Descriptions.Item>
                    <Descriptions.Item label="Status">
                        {record?.is_active ? (
                            <Space>
                                <CheckCircleOutlined style={{ color: "green" }} />
                                <span>Active</span>
                            </Space>
                        ) : (
                            <Space>
                                <CloseCircleOutlined style={{ color: "red" }} />
                                <span>Inactive</span>
                            </Space>
                        )}
                    </Descriptions.Item>
                    <Descriptions.Item label="Name">{record?.name}</Descriptions.Item>
                    <Descriptions.Item label="Email">{record?.email}</Descriptions.Item>
                    <Descriptions.Item label="Global Role">
                        {record?.role && (
                            <Tag color={USER_ROLE_COLORS[record.role]}>
                                {USER_ROLE_LABELS[record.role] || record.role}
                            </Tag>
                        )}
                    </Descriptions.Item>
                    <Descriptions.Item label="Email Verified">
                        {record?.email_verified_at ? (
                            <Space>
                                <CheckCircleOutlined style={{ color: "green" }} />
                                <span>{new Date(record.email_verified_at).toLocaleString()}</span>
                            </Space>
                        ) : (
                            <Space>
                                <CloseCircleOutlined style={{ color: "#ccc" }} />
                                <span>Not verified</span>
                            </Space>
                        )}
                    </Descriptions.Item>
                    <Descriptions.Item label="Created">
                        {record?.created_at ? new Date(record.created_at).toLocaleString() : "-"}
                    </Descriptions.Item>
                    <Descriptions.Item label="Updated">
                        {record?.updated_at ? new Date(record.updated_at).toLocaleString() : "-"}
                    </Descriptions.Item>
                </Descriptions>
            </Card>

            <Card title="Business Memberships">
                {record?.businesses && record.businesses.length > 0 ? (
                    <Table
                        dataSource={record.businesses}
                        rowKey="id"
                        pagination={false}
                        columns={[
                            { title: "ID", dataIndex: "id", width: 80 },
                            { title: "Business Name", dataIndex: "name" },
                            { title: "Slug", dataIndex: "slug" },
                            {
                                title: "Role in Business",
                                dataIndex: "role",
                                render: (role: UserBusiness["role"]) => (
                                    <Tag color={BUSINESS_USER_ROLE_COLORS[role]}>
                                        {BUSINESS_USER_ROLE_LABELS[role] || role}
                                    </Tag>
                                ),
                            },
                        ]}
                    />
                ) : (
                    <p>User is not assigned to any businesses.</p>
                )}
            </Card>
        </Show>
    );
};
