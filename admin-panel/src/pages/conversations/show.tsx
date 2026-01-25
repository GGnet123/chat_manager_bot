import { useShow, useCustom } from "@refinedev/core";
import { Show } from "@refinedev/antd";
import { Card, Descriptions, Tag, Typography, Divider, List, Avatar } from "antd";
import { UserOutlined, RobotOutlined } from "@ant-design/icons";

const { Title, Text } = Typography;

export const ConversationShow = () => {
    const { queryResult } = useShow();
    const { data, isLoading } = queryResult;
    const record = data?.data;

    const { data: messagesData, isLoading: messagesLoading } = useCustom({
        url: `/conversations/${record?.id}/messages`,
        method: "get",
        queryOptions: {
            enabled: !!record?.id,
        },
    });

    const messages = messagesData?.data?.messages || [];

    const platformColors: Record<string, string> = {
        whatsapp: "green",
        telegram: "blue",
    };

    return (
        <Show isLoading={isLoading}>
            <Card>
                <Descriptions title="Conversation Details" bordered column={2}>
                    <Descriptions.Item label="ID">{record?.id}</Descriptions.Item>
                    <Descriptions.Item label="Platform">
                        <Tag color={platformColors[record?.platform] || "default"}>
                            {record?.platform_label || record?.platform}
                        </Tag>
                    </Descriptions.Item>
                    <Descriptions.Item label="Status">
                        <Tag color={record?.status === "active" ? "green" : "default"}>
                            {record?.status}
                        </Tag>
                    </Descriptions.Item>
                    <Descriptions.Item label="Client">
                        {record?.client?.name ||
                            record?.client?.phone ||
                            record?.client?.telegram_id ||
                            "-"}
                    </Descriptions.Item>
                    <Descriptions.Item label="Phone">
                        {record?.client?.phone || "-"}
                    </Descriptions.Item>
                    <Descriptions.Item label="Started">
                        {record?.created_at
                            ? new Date(record.created_at).toLocaleString()
                            : "-"}
                    </Descriptions.Item>
                    <Descriptions.Item label="Last Message">
                        {record?.last_message_at
                            ? new Date(record.last_message_at).toLocaleString()
                            : "-"}
                    </Descriptions.Item>
                </Descriptions>

                <Divider />

                <Title level={5}>Messages</Title>
                <div
                    style={{
                        maxHeight: 500,
                        overflowY: "auto",
                        padding: 16,
                        background: "#f5f5f5",
                        borderRadius: 8,
                    }}
                >
                    <List
                        loading={messagesLoading}
                        dataSource={messages}
                        renderItem={(item: any) => (
                            <List.Item
                                style={{
                                    justifyContent:
                                        item.role === "user" ? "flex-start" : "flex-end",
                                    border: "none",
                                }}
                            >
                                <div
                                    style={{
                                        display: "flex",
                                        flexDirection:
                                            item.role === "user" ? "row" : "row-reverse",
                                        alignItems: "flex-start",
                                        gap: 8,
                                        maxWidth: "80%",
                                    }}
                                >
                                    <Avatar
                                        icon={
                                            item.role === "user" ? (
                                                <UserOutlined />
                                            ) : (
                                                <RobotOutlined />
                                            )
                                        }
                                        style={{
                                            backgroundColor:
                                                item.role === "user" ? "#1890ff" : "#52c41a",
                                        }}
                                    />
                                    <div
                                        style={{
                                            background:
                                                item.role === "user" ? "#e6f7ff" : "#f6ffed",
                                            padding: "8px 12px",
                                            borderRadius: 8,
                                        }}
                                    >
                                        <Text>{item.content}</Text>
                                        <div>
                                            <Text
                                                type="secondary"
                                                style={{ fontSize: 12 }}
                                            >
                                                {new Date(item.created_at).toLocaleTimeString()}
                                            </Text>
                                        </div>
                                    </div>
                                </div>
                            </List.Item>
                        )}
                    />
                </div>
            </Card>
        </Show>
    );
};
