import { Refine, Authenticated } from "@refinedev/core";
import {
    ErrorComponent,
    ThemedLayoutV2,
    useNotificationProvider,
} from "@refinedev/antd";
import routerBindings, {
    DocumentTitleHandler,
    NavigateToResource,
    UnsavedChangesNotifier,
} from "@refinedev/react-router-v6";
import {
    BrowserRouter,
    Routes,
    Route,
    Outlet,
} from "react-router-dom";
import { ConfigProvider, App as AntdApp } from "antd";
import {
    DashboardOutlined,
    OrderedListOutlined,
    MessageOutlined,
    SettingOutlined,
    BellOutlined,
    ShopOutlined,
    UserOutlined,
} from "@ant-design/icons";

import { authProvider } from "./providers/authProvider";
import { dataProvider } from "./providers/dataProvider";

import { DashboardPage } from "./pages/dashboard";
import { ActionList, ActionShow } from "./pages/actions";
import { ConversationList, ConversationShow } from "./pages/conversations";
import { GptConfigList, GptConfigCreate, GptConfigEdit } from "./pages/settings/gpt-config";
import { PromptList, PromptCreate, PromptEdit } from "./pages/settings/prompts";
import { NotificationChannelList } from "./pages/settings/notifications";
import { BusinessList, BusinessShow, BusinessCreate, BusinessEdit } from "./pages/businesses";
import { UserList, UserShow, UserCreate, UserEdit } from "./pages/users";
import { LoginPage } from "./pages/login";

import "@refinedev/antd/dist/reset.css";

function App() {
    return (
        <BrowserRouter>
            <ConfigProvider
                    theme={{
                        token: {
                            colorPrimary: "#1890ff",
                        },
                    }}
                >
                    <AntdApp>
                        <Refine
                            dataProvider={dataProvider}
                            authProvider={authProvider}
                            routerProvider={routerBindings}
                            notificationProvider={useNotificationProvider()}
                            resources={[
                                {
                                    name: "dashboard",
                                    list: "/dashboard",
                                    meta: {
                                        label: "Dashboard",
                                        icon: <DashboardOutlined />,
                                    },
                                },
                                {
                                    name: "businesses",
                                    list: "/businesses",
                                    show: "/businesses/:id",
                                    create: "/businesses/create",
                                    edit: "/businesses/:id/edit",
                                    meta: {
                                        label: "Businesses",
                                        icon: <ShopOutlined />,
                                    },
                                },
                                {
                                    name: "users",
                                    list: "/users",
                                    show: "/users/:id",
                                    create: "/users/create",
                                    edit: "/users/:id/edit",
                                    meta: {
                                        label: "Users",
                                        icon: <UserOutlined />,
                                    },
                                },
                                {
                                    name: "actions",
                                    list: "/actions",
                                    show: "/actions/:id",
                                    meta: {
                                        label: "Actions",
                                        icon: <OrderedListOutlined />,
                                    },
                                },
                                {
                                    name: "conversations",
                                    list: "/conversations",
                                    show: "/conversations/:id",
                                    meta: {
                                        label: "Conversations",
                                        icon: <MessageOutlined />,
                                    },
                                },
                                {
                                    name: "gpt-configs",
                                    list: "/settings/gpt-configs",
                                    create: "/settings/gpt-configs/create",
                                    edit: "/settings/gpt-configs/:id/edit",
                                    meta: {
                                        label: "GPT Config",
                                        parent: "settings",
                                        icon: <SettingOutlined />,
                                    },
                                },
                                {
                                    name: "prompts",
                                    list: "/settings/prompts",
                                    create: "/settings/prompts/create",
                                    edit: "/settings/prompts/:id/edit",
                                    meta: {
                                        label: "Prompts",
                                        parent: "settings",
                                    },
                                },
                                {
                                    name: "notification-channels",
                                    list: "/settings/notifications",
                                    meta: {
                                        label: "Notifications",
                                        parent: "settings",
                                        icon: <BellOutlined />,
                                    },
                                },
                                {
                                    name: "settings",
                                    meta: {
                                        label: "Settings",
                                        icon: <SettingOutlined />,
                                    },
                                },
                            ]}
                            options={{
                                syncWithLocation: true,
                                warnWhenUnsavedChanges: true,
                            }}
                        >
                            <Routes>
                                <Route
                                    element={
                                        <Authenticated
                                            key="authenticated-routes"
                                            fallback={<LoginPage />}
                                        >
                                            <ThemedLayoutV2
                                                Title={() => <span style={{ fontWeight: 'bold' }}>ServiceBot</span>}
                                            >
                                                <Outlet />
                                            </ThemedLayoutV2>
                                        </Authenticated>
                                    }
                                >
                                    <Route
                                        index
                                        element={<NavigateToResource resource="dashboard" />}
                                    />
                                    <Route path="/dashboard" element={<DashboardPage />} />
                                    <Route path="/businesses">
                                        <Route index element={<BusinessList />} />
                                        <Route path="create" element={<BusinessCreate />} />
                                        <Route path=":id" element={<BusinessShow />} />
                                        <Route path=":id/edit" element={<BusinessEdit />} />
                                    </Route>
                                    <Route path="/users">
                                        <Route index element={<UserList />} />
                                        <Route path="create" element={<UserCreate />} />
                                        <Route path=":id" element={<UserShow />} />
                                        <Route path=":id/edit" element={<UserEdit />} />
                                    </Route>
                                    <Route path="/actions">
                                        <Route index element={<ActionList />} />
                                        <Route path=":id" element={<ActionShow />} />
                                    </Route>
                                    <Route path="/conversations">
                                        <Route index element={<ConversationList />} />
                                        <Route path=":id" element={<ConversationShow />} />
                                    </Route>
                                    <Route path="/settings">
                                        <Route path="gpt-configs">
                                            <Route index element={<GptConfigList />} />
                                            <Route path="create" element={<GptConfigCreate />} />
                                            <Route path=":id/edit" element={<GptConfigEdit />} />
                                        </Route>
                                        <Route path="prompts">
                                            <Route index element={<PromptList />} />
                                            <Route path="create" element={<PromptCreate />} />
                                            <Route path=":id/edit" element={<PromptEdit />} />
                                        </Route>
                                        <Route path="notifications" element={<NotificationChannelList />} />
                                    </Route>
                                    <Route path="*" element={<ErrorComponent />} />
                                </Route>
                                <Route path="/login" element={<LoginPage />} />
                            </Routes>
                            <UnsavedChangesNotifier />
                            <DocumentTitleHandler />
                        </Refine>
                    </AntdApp>
                </ConfigProvider>
        </BrowserRouter>
    );
}

export default App;
