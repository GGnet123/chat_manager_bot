import { useShow, useCustom } from "@refinedev/core";
import { Show } from "@refinedev/antd";
import {
    Card,
    Descriptions,
    Tag,
    Typography,
    Space,
    Button,
    Select,
    Divider,
} from "antd";
import { useState } from "react";

const { Title, Text } = Typography;

export const ActionShow = () => {
    const { queryResult } = useShow();
    const { data, isLoading } = queryResult;
    const record = data?.data;

    const [selectedStatus, setSelectedStatus] = useState<string | null>(null);

    const statusColors: Record<string, string> = {
        pending: "orange",
        processing: "blue",
        completed: "green",
        failed: "red",
        cancelled: "default",
    };

    const { refetch: updateStatus } = useCustom({
        url: `/actions/${record?.id}/status`,
        method: "post",
        config: {
            payload: { status: selectedStatus },
        },
        queryOptions: {
            enabled: false,
        },
    });

    const handleStatusUpdate = async () => {
        if (selectedStatus) {
            await updateStatus();
            queryResult.refetch();
        }
    };

    return (
        <Show isLoading={isLoading}>
            <Card>
                <Descriptions title="Action Details" bordered column={2}>
                    <Descriptions.Item label="ID">{record?.id}</Descriptions.Item>
                    <Descriptions.Item label="Action Type">
                        <Tag color="blue">{record?.action}</Tag>
                    </Descriptions.Item>
                    <Descriptions.Item label="Status">
                        <Tag color={statusColors[record?.status] || "default"}>
                            {record?.status_label || record?.status}
                        </Tag>
                    </Descriptions.Item>
                    <Descriptions.Item label="Priority">
                        <Tag
                            color={
                                record?.priority === "high"
                                    ? "red"
                                    : record?.priority === "normal"
                                    ? "blue"
                                    : "default"
                            }
                        >
                            {record?.priority}
                        </Tag>
                    </Descriptions.Item>
                    <Descriptions.Item label="Client Name">
                        {record?.client_name || "-"}
                    </Descriptions.Item>
                    <Descriptions.Item label="Client Phone">
                        {record?.client_phone || "-"}
                    </Descriptions.Item>
                    <Descriptions.Item label="Assigned To">
                        {record?.assigned_user?.name || "Unassigned"}
                    </Descriptions.Item>
                    <Descriptions.Item label="Created At">
                        {record?.created_at
                            ? new Date(record.created_at).toLocaleString()
                            : "-"}
                    </Descriptions.Item>
                    <Descriptions.Item label="Processed At">
                        {record?.processed_at
                            ? new Date(record.processed_at).toLocaleString()
                            : "-"}
                    </Descriptions.Item>
                </Descriptions>

                <Divider />

                <Title level={5}>Update Status</Title>
                <Space>
                    <Select
                        style={{ width: 150 }}
                        placeholder="Select status"
                        onChange={setSelectedStatus}
                        value={selectedStatus}
                        options={[
                            { label: "Pending", value: "pending" },
                            { label: "Processing", value: "processing" },
                            { label: "Completed", value: "completed" },
                            { label: "Failed", value: "failed" },
                            { label: "Cancelled", value: "cancelled" },
                        ]}
                    />
                    <Button
                        type="primary"
                        onClick={handleStatusUpdate}
                        disabled={!selectedStatus}
                    >
                        Update Status
                    </Button>
                </Space>

                {record?.details && (
                    <>
                        <Divider />
                        <Title level={5}>Details</Title>
                        <pre
                            style={{
                                background: "#f5f5f5",
                                padding: 16,
                                borderRadius: 4,
                                overflow: "auto",
                            }}
                        >
                            {JSON.stringify(record.details, null, 2)}
                        </pre>
                    </>
                )}

                {record?.notes && (
                    <>
                        <Divider />
                        <Title level={5}>Notes</Title>
                        <Text>{record.notes}</Text>
                    </>
                )}
            </Card>
        </Show>
    );
};
