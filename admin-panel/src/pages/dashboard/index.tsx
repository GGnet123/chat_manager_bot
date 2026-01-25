import { useCustom } from "@refinedev/core";
import { Card, Col, Row, Statistic, Table, Tag, Typography, Spin } from "antd";
import {
    ClockCircleOutlined,
    CheckCircleOutlined,
    CalendarOutlined,
    MessageOutlined,
} from "@ant-design/icons";

const { Title } = Typography;

export const DashboardPage = () => {
    const { data: statsData, isLoading: statsLoading } = useCustom({
        url: "/dashboard/stats",
        method: "get",
    });

    const { data: activityData, isLoading: activityLoading } = useCustom({
        url: "/dashboard/activity",
        method: "get",
    });

    const stats = statsData?.data;
    const activity = activityData?.data;

    const recentColumns = [
        {
            title: "Action",
            dataIndex: "action",
            key: "action",
            render: (action: string) => (
                <Tag color="blue">{action}</Tag>
            ),
        },
        {
            title: "Client",
            dataIndex: "client_name",
            key: "client_name",
        },
        {
            title: "Status",
            dataIndex: "status",
            key: "status",
            render: (status: string) => {
                const colors: Record<string, string> = {
                    pending: "orange",
                    processing: "blue",
                    completed: "green",
                    failed: "red",
                };
                return <Tag color={colors[status] || "default"}>{status}</Tag>;
            },
        },
        {
            title: "Assigned To",
            dataIndex: "assigned_to",
            key: "assigned_to",
            render: (name: string) => name || "-",
        },
        {
            title: "Created",
            dataIndex: "created_at",
            key: "created_at",
            render: (date: string) => new Date(date).toLocaleString(),
        },
    ];

    if (statsLoading) {
        return (
            <div style={{ textAlign: "center", padding: 50 }}>
                <Spin size="large" />
            </div>
        );
    }

    return (
        <div>
            <Title level={3}>Dashboard</Title>

            <Row gutter={[16, 16]}>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Pending Actions"
                            value={stats?.pending_actions || 0}
                            prefix={<ClockCircleOutlined />}
                            valueStyle={{ color: "#faad14" }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Today's Actions"
                            value={stats?.today_actions || 0}
                            prefix={<CalendarOutlined />}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="This Week"
                            value={stats?.week_actions || 0}
                            prefix={<CheckCircleOutlined />}
                            valueStyle={{ color: "#52c41a" }}
                        />
                    </Card>
                </Col>
                <Col xs={24} sm={12} lg={6}>
                    <Card>
                        <Statistic
                            title="Active Conversations"
                            value={stats?.active_conversations || 0}
                            prefix={<MessageOutlined />}
                            valueStyle={{ color: "#1890ff" }}
                        />
                    </Card>
                </Col>
            </Row>

            <Row gutter={[16, 16]} style={{ marginTop: 16 }}>
                <Col xs={24}>
                    <Card title="Recent Actions">
                        <Table
                            dataSource={activity?.recent_actions || []}
                            columns={recentColumns}
                            rowKey="id"
                            pagination={false}
                            loading={activityLoading}
                        />
                    </Card>
                </Col>
            </Row>
        </div>
    );
};
