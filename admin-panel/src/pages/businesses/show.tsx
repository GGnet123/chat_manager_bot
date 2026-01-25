import { useShow, useInvalidate } from "@refinedev/core";
import { Show, TagField } from "@refinedev/antd";
import {
    Card,
    Descriptions,
    Tabs,
    Table,
    Button,
    Space,
    Modal,
    Form,
    Input,
    Select,
    message,
    Tag,
} from "antd";
import {
    CheckCircleOutlined,
    CloseCircleOutlined,
    PlusOutlined,
    UserOutlined,
} from "@ant-design/icons";
import { useState } from "react";
import type { Business, BusinessUser, BusinessUserRole } from "../../types";
import { BUSINESS_USER_ROLE_LABELS } from "../../types";

export const BusinessShow = () => {
    const { queryResult } = useShow<Business>();
    const { data, isLoading } = queryResult;
    const record = data?.data;

    const [isAddUserModalOpen, setIsAddUserModalOpen] = useState(false);
    const [addUserForm] = Form.useForm();
    const [isSubmitting, setIsSubmitting] = useState(false);
    const invalidate = useInvalidate();

    const handleAddUser = async (values: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
        role: BusinessUserRole;
    }) => {
        if (!record) return;

        setIsSubmitting(true);
        try {
            const response = await fetch(`/api/v1/admin/businesses/${record.id}/users`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                },
                body: JSON.stringify(values),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || "Failed to add user");
            }

            message.success("User added successfully");
            setIsAddUserModalOpen(false);
            addUserForm.resetFields();
            invalidate({ resource: "businesses", invalidates: ["detail"], id: record.id });
        } catch (error) {
            message.error(error instanceof Error ? error.message : "Failed to add user");
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleRemoveUser = async (userId: number) => {
        if (!record) return;

        try {
            const response = await fetch(
                `/api/v1/admin/businesses/${record.id}/users/${userId}`,
                {
                    method: "DELETE",
                    headers: {
                        Authorization: `Bearer ${localStorage.getItem("token")}`,
                    },
                }
            );

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || "Failed to remove user");
            }

            message.success("User removed from business");
            invalidate({ resource: "businesses", invalidates: ["detail"], id: record.id });
        } catch (error) {
            message.error(error instanceof Error ? error.message : "Failed to remove user");
        }
    };

    const handleUpdateUserRole = async (userId: number, role: BusinessUserRole) => {
        if (!record) return;

        try {
            const response = await fetch(
                `/api/v1/admin/businesses/${record.id}/users/${userId}`,
                {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${localStorage.getItem("token")}`,
                    },
                    body: JSON.stringify({ role }),
                }
            );

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || "Failed to update role");
            }

            message.success("Role updated successfully");
            invalidate({ resource: "businesses", invalidates: ["detail"], id: record.id });
        } catch (error) {
            message.error(error instanceof Error ? error.message : "Failed to update role");
        }
    };

    const userColumns = [
        {
            title: "ID",
            dataIndex: "id",
            key: "id",
            width: 80,
        },
        {
            title: "Name",
            dataIndex: "name",
            key: "name",
        },
        {
            title: "Email",
            dataIndex: "email",
            key: "email",
        },
        {
            title: "Role",
            dataIndex: "role",
            key: "role",
            width: 180,
            render: (role: BusinessUserRole, user: BusinessUser) => (
                <Select
                    value={role}
                    style={{ width: 150 }}
                    onChange={(newRole) => handleUpdateUserRole(user.id, newRole)}
                    options={[
                        { label: BUSINESS_USER_ROLE_LABELS.admin_manager, value: "admin_manager" },
                        { label: BUSINESS_USER_ROLE_LABELS.manager, value: "manager" },
                    ]}
                />
            ),
        },
        {
            title: "Actions",
            key: "actions",
            width: 100,
            render: (_: unknown, user: BusinessUser) => (
                <Button
                    danger
                    size="small"
                    onClick={() => {
                        Modal.confirm({
                            title: "Remove User",
                            content: `Are you sure you want to remove "${user.name}" from this business?`,
                            okText: "Remove",
                            okType: "danger",
                            onOk: () => handleRemoveUser(user.id),
                        });
                    }}
                >
                    Remove
                </Button>
            ),
        },
    ];

    return (
        <Show isLoading={isLoading}>
            <Tabs
                defaultActiveKey="details"
                items={[
                    {
                        key: "details",
                        label: "Details",
                        children: (
                            <Card>
                                <Descriptions bordered column={2}>
                                    <Descriptions.Item label="ID">{record?.id}</Descriptions.Item>
                                    <Descriptions.Item label="Status">
                                        <TagField
                                            value={record?.is_active ? "Active" : "Inactive"}
                                            color={record?.is_active ? "green" : "default"}
                                        />
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Name">{record?.name}</Descriptions.Item>
                                    <Descriptions.Item label="Slug">{record?.slug}</Descriptions.Item>
                                    <Descriptions.Item label="WhatsApp">
                                        {record?.has_whatsapp ? (
                                            <Space>
                                                <CheckCircleOutlined style={{ color: "green" }} />
                                                <span>Connected</span>
                                                {record?.whatsapp_phone_id && (
                                                    <Tag>{record.whatsapp_phone_id}</Tag>
                                                )}
                                            </Space>
                                        ) : (
                                            <Space>
                                                <CloseCircleOutlined style={{ color: "#ccc" }} />
                                                <span>Not configured</span>
                                            </Space>
                                        )}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Telegram">
                                        {record?.has_telegram ? (
                                            <Space>
                                                <CheckCircleOutlined style={{ color: "green" }} />
                                                <span>Connected</span>
                                            </Space>
                                        ) : (
                                            <Space>
                                                <CloseCircleOutlined style={{ color: "#ccc" }} />
                                                <span>Not configured</span>
                                            </Space>
                                        )}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Created">
                                        {record?.created_at
                                            ? new Date(record.created_at).toLocaleString()
                                            : "-"}
                                    </Descriptions.Item>
                                    <Descriptions.Item label="Updated">
                                        {record?.updated_at
                                            ? new Date(record.updated_at).toLocaleString()
                                            : "-"}
                                    </Descriptions.Item>
                                </Descriptions>
                            </Card>
                        ),
                    },
                    {
                        key: "users",
                        label: (
                            <span>
                                <UserOutlined /> Users ({record?.users?.length || 0})
                            </span>
                        ),
                        children: (
                            <Card
                                title="Business Users"
                                extra={
                                    <Button
                                        type="primary"
                                        icon={<PlusOutlined />}
                                        onClick={() => setIsAddUserModalOpen(true)}
                                    >
                                        Add User
                                    </Button>
                                }
                            >
                                <Table
                                    dataSource={record?.users || []}
                                    columns={userColumns}
                                    rowKey="id"
                                    pagination={false}
                                />
                            </Card>
                        ),
                    },
                    {
                        key: "gpt",
                        label: "GPT Configurations",
                        children: (
                            <Card title="GPT Configurations">
                                {record?.gpt_configurations &&
                                record.gpt_configurations.length > 0 ? (
                                    <Table
                                        dataSource={record.gpt_configurations}
                                        rowKey="id"
                                        pagination={false}
                                        columns={[
                                            { title: "Name", dataIndex: "name" },
                                            { title: "Model", dataIndex: "model" },
                                            {
                                                title: "Active",
                                                dataIndex: "is_active",
                                                render: (value: boolean) => (
                                                    <TagField
                                                        value={value ? "Active" : "Inactive"}
                                                        color={value ? "green" : "default"}
                                                    />
                                                ),
                                            },
                                        ]}
                                    />
                                ) : (
                                    <p>No GPT configurations found.</p>
                                )}
                            </Card>
                        ),
                    },
                ]}
            />

            <Modal
                title="Add User to Business"
                open={isAddUserModalOpen}
                onCancel={() => {
                    setIsAddUserModalOpen(false);
                    addUserForm.resetFields();
                }}
                footer={null}
            >
                <Form
                    form={addUserForm}
                    layout="vertical"
                    onFinish={handleAddUser}
                >
                    <Form.Item
                        label="Name"
                        name="name"
                        rules={[{ required: true, message: "Please enter name" }]}
                    >
                        <Input placeholder="John Doe" />
                    </Form.Item>

                    <Form.Item
                        label="Email"
                        name="email"
                        rules={[
                            { required: true, message: "Please enter email" },
                            { type: "email", message: "Please enter a valid email" },
                        ]}
                    >
                        <Input placeholder="john@example.com" />
                    </Form.Item>

                    <Form.Item
                        label="Password"
                        name="password"
                        rules={[
                            { required: true, message: "Please enter password" },
                            { min: 8, message: "Password must be at least 8 characters" },
                        ]}
                    >
                        <Input.Password placeholder="Enter password" />
                    </Form.Item>

                    <Form.Item
                        label="Confirm Password"
                        name="password_confirmation"
                        dependencies={["password"]}
                        rules={[
                            { required: true, message: "Please confirm password" },
                            ({ getFieldValue }) => ({
                                validator(_, value) {
                                    if (!value || getFieldValue("password") === value) {
                                        return Promise.resolve();
                                    }
                                    return Promise.reject(new Error("Passwords do not match"));
                                },
                            }),
                        ]}
                    >
                        <Input.Password placeholder="Confirm password" />
                    </Form.Item>

                    <Form.Item
                        label="Role"
                        name="role"
                        initialValue="manager"
                        rules={[{ required: true, message: "Please select a role" }]}
                    >
                        <Select
                            options={[
                                {
                                    label: BUSINESS_USER_ROLE_LABELS.admin_manager,
                                    value: "admin_manager",
                                },
                                {
                                    label: BUSINESS_USER_ROLE_LABELS.manager,
                                    value: "manager",
                                },
                            ]}
                        />
                    </Form.Item>

                    <Form.Item style={{ marginBottom: 0, textAlign: "right" }}>
                        <Space>
                            <Button
                                onClick={() => {
                                    setIsAddUserModalOpen(false);
                                    addUserForm.resetFields();
                                }}
                            >
                                Cancel
                            </Button>
                            <Button type="primary" htmlType="submit" loading={isSubmitting}>
                                Add User
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>
            </Modal>
        </Show>
    );
};
