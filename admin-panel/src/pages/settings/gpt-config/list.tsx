import {
    List,
    useTable,
    EditButton,
    DeleteButton,
} from "@refinedev/antd";
import { useGetIdentity, useMany } from "@refinedev/core";
import { Table, Tag, Space, Button, message } from "antd";
import { CheckCircleOutlined } from "@ant-design/icons";

interface UserIdentity {
    id: number;
    role: string;
}

interface GptConfig {
    id: number;
    name: string;
    model: string;
    max_tokens: number;
    temperature: number;
    is_active: boolean;
    business_id: number;
}

export const GptConfigList = () => {
    const { data: user } = useGetIdentity<UserIdentity>();
    const isSuperAdmin = user?.role === "super_admin";

    const { tableProps, tableQueryResult } = useTable<GptConfig>({
        syncWithLocation: true,
    });

    // Get business names for super admin view
    const businessIds = tableProps.dataSource?.map((item) => item.business_id) || [];
    const { data: businessesData } = useMany({
        resource: "businesses",
        ids: businessIds,
        queryOptions: {
            enabled: isSuperAdmin && businessIds.length > 0,
        },
    });

    const getBusinessName = (businessId: number) => {
        const business = businessesData?.data?.find((b: any) => b.id === businessId);
        return business?.name || businessId;
    };

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
                const data = await response.json();
                message.error(data.message || "Failed to activate configuration");
            }
        } catch (error) {
            message.error("Failed to activate configuration");
        }
    };

    return (
        <List>
            <Table {...tableProps} rowKey="id">
                <Table.Column dataIndex="id" title="ID" width={80} />
                {isSuperAdmin && (
                    <Table.Column
                        dataIndex="business_id"
                        title="Business"
                        render={(value) => getBusinessName(value)}
                    />
                )}
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
                    render={(_, record: GptConfig) => (
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
