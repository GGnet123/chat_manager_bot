import {
    List,
    useTable,
    ShowButton,
    FilterDropdown,
    TagField,
} from "@refinedev/antd";
import { Table, Select, Input, Tag, Space } from "antd";

export const ActionList = () => {
    const { tableProps } = useTable({
        syncWithLocation: true,
    });

    const statusColors: Record<string, string> = {
        pending: "orange",
        processing: "blue",
        completed: "green",
        failed: "red",
        cancelled: "default",
    };

    const actionColors: Record<string, string> = {
        reservation: "purple",
        order: "cyan",
        inquiry: "blue",
        complaint: "red",
        callback: "green",
    };

    return (
        <List>
            <Table {...tableProps} rowKey="id">
                <Table.Column dataIndex="id" title="ID" width={80} />
                <Table.Column
                    dataIndex="action"
                    title="Action"
                    render={(value) => (
                        <Tag color={actionColors[value] || "default"}>
                            {value}
                        </Tag>
                    )}
                    filterDropdown={(props) => (
                        <FilterDropdown {...props}>
                            <Select
                                style={{ width: 150 }}
                                placeholder="Select action"
                                options={[
                                    { label: "Reservation", value: "reservation" },
                                    { label: "Order", value: "order" },
                                    { label: "Inquiry", value: "inquiry" },
                                    { label: "Complaint", value: "complaint" },
                                    { label: "Callback", value: "callback" },
                                ]}
                            />
                        </FilterDropdown>
                    )}
                />
                <Table.Column
                    dataIndex="client_name"
                    title="Client"
                    filterDropdown={(props) => (
                        <FilterDropdown {...props}>
                            <Input placeholder="Search client" />
                        </FilterDropdown>
                    )}
                />
                <Table.Column dataIndex="client_phone" title="Phone" />
                <Table.Column
                    dataIndex="status"
                    title="Status"
                    render={(value) => (
                        <TagField
                            value={value}
                            color={statusColors[value] || "default"}
                        />
                    )}
                    filterDropdown={(props) => (
                        <FilterDropdown {...props}>
                            <Select
                                style={{ width: 150 }}
                                placeholder="Select status"
                                options={[
                                    { label: "Pending", value: "pending" },
                                    { label: "Processing", value: "processing" },
                                    { label: "Completed", value: "completed" },
                                    { label: "Failed", value: "failed" },
                                ]}
                            />
                        </FilterDropdown>
                    )}
                />
                <Table.Column
                    dataIndex="priority"
                    title="Priority"
                    render={(value) => {
                        const colors: Record<string, string> = {
                            low: "default",
                            normal: "blue",
                            high: "red",
                        };
                        return <Tag color={colors[value] || "default"}>{value}</Tag>;
                    }}
                />
                <Table.Column
                    dataIndex={["assigned_user", "name"]}
                    title="Assigned To"
                    render={(value) => value || "-"}
                />
                <Table.Column
                    dataIndex="created_at"
                    title="Created"
                    render={(value) => new Date(value).toLocaleString()}
                />
                <Table.Column
                    title="Actions"
                    render={(_, record: any) => (
                        <Space>
                            <ShowButton
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
