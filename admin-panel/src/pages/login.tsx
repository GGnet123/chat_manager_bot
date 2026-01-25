import { useLogin } from "@refinedev/core";
import { Form, Input, Button, Card, Typography, Layout, Space } from "antd";
import { UserOutlined, LockOutlined } from "@ant-design/icons";

const { Title, Text } = Typography;

export const LoginPage = () => {
    const { mutate: login, isLoading } = useLogin();

    const onFinish = (values: { email: string; password: string }) => {
        login(values);
    };

    return (
        <Layout
            style={{
                minHeight: "100vh",
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
                background: "#f0f2f5",
            }}
        >
            <Card
                style={{
                    width: 400,
                    boxShadow: "0 4px 12px rgba(0,0,0,0.1)",
                }}
            >
                <Space direction="vertical" style={{ width: "100%" }} size="large">
                    <div style={{ textAlign: "center" }}>
                        <Title level={2} style={{ marginBottom: 8 }}>
                            ServiceBot
                        </Title>
                        <Text type="secondary">Admin Panel</Text>
                    </div>

                    <Form
                        name="login"
                        onFinish={onFinish}
                        layout="vertical"
                        requiredMark={false}
                    >
                        <Form.Item
                            name="email"
                            rules={[
                                { required: true, message: "Please input your email" },
                                { type: "email", message: "Please enter a valid email" },
                            ]}
                        >
                            <Input
                                prefix={<UserOutlined />}
                                placeholder="Email"
                                size="large"
                            />
                        </Form.Item>

                        <Form.Item
                            name="password"
                            rules={[
                                { required: true, message: "Please input your password" },
                            ]}
                        >
                            <Input.Password
                                prefix={<LockOutlined />}
                                placeholder="Password"
                                size="large"
                            />
                        </Form.Item>

                        <Form.Item>
                            <Button
                                type="primary"
                                htmlType="submit"
                                size="large"
                                block
                                loading={isLoading}
                            >
                                Sign In
                            </Button>
                        </Form.Item>
                    </Form>
                </Space>
            </Card>
        </Layout>
    );
};
