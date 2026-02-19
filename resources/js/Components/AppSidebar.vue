<script setup>
import { computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
    useSidebar,
} from "@/components/ui/sidebar";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

const { isMobile } = useSidebar();
const page = usePage();

const user = computed(() => page.props.auth?.user);

// System role names
const SYSTEM_ROLES = [
    "Superadmin",
    "BAC Reso Admin",
    "Budgeting Admin",
    "Canvassing Admin",
    "PR Admin",
    "Quotation Admin",
];

const userRole = computed(() => user.value?.role?.name || "");

const isSystemRole = computed(() => SYSTEM_ROLES.includes(userRole.value));

const isSuperadmin = computed(() => userRole.value === "Superadmin");

const isBACSAdminOrAbove = computed(
    () =>
        userRole.value === "Superadmin" || userRole.value === "BAC Reso Admin",
);

const isBudgetingAdminOrAbove = computed(
    () =>
        userRole.value === "Superadmin" || userRole.value === "Budgeting Admin",
);

const isCanvassingAdminOrAbove = computed(
    () =>
        userRole.value === "Superadmin" ||
        userRole.value === "Canvassing Admin",
);

const isPRAdminOrAbove = computed(
    () => userRole.value === "Superadmin" || userRole.value === "PR Admin",
);

// Navigation items
const mainNavItems = computed(() => {
    // Access page.url to make this reactive to route changes
    const currentUrl = page.url;

    const items = [
        {
            title: "Dashboard",
            url: route("dashboard.index"),
            icon: "lucide:layout-dashboard",
            isActive: route().current("dashboard.*"),
            roles: ["all"], // Available to all
        },
        {
            title: "Users",
            url: route("users.index"),
            icon: "lucide:users",
            isActive: route().current("users.*"),
            roles: ["all"], // Available to all
        },
        {
            title: "Roles",
            url: route("roles.index"),
            icon: "lucide:shield",
            isActive: route().current("roles.*"),
            roles: ["Superadmin"], // Superadmin only
        },
        {
            title: "Offices",
            url: route("offices.index"),
            icon: "lucide:building-2",
            isActive: route().current("offices.*"),
            roles: ["Superadmin"], // Superadmin only
        },
        {
            title: "APPs",
            url: route("apps.index"),
            icon: "lucide:clipboard-check",
            isActive: route().current("apps.*"),
            roles: ["Superadmin", "BAC Reso Admin", "office"], // BAC Reso + office
        },
        {
            title: "Funds",
            url: route("funds.index"),
            icon: "lucide:wallet",
            isActive: route().current("funds.*"),
            roles: ["Superadmin", "office"], // Superadmin + office roles
        },
        {
            title: "PPMPs",
            url: route("ppmps.index"),
            icon: "lucide:clipboard-list",
            isActive: route().current("ppmps.*"),
            roles: ["Superadmin", "Budgeting Admin", "office"], // Budgeting + office
        },
        {
            title: "Emanatings",
            url: route("emanatings.index"),
            icon: "lucide:clipboard-minus",
            isActive: route().current("emanatings.*"),
            roles: ["Superadmin", "Budgeting Admin", "office"], // Budgeting + office
        },
        {
            title: "Canvassing",
            url: route("canvasses.index"),
            icon: "lucide:shopping-cart",
            isActive: route().current("canvasses.*"),
            roles: ["Superadmin", "Canvassing Admin"],
        },
        {
            title: "Suppliers",
            url: route("suppliers.index"),
            icon: "lucide:truck",
            isActive: route().current("suppliers.*"),
            roles: ["Superadmin", "Canvassing Admin"],
        },
        {
            title: "Master List",
            url: route("master-list-items.index"),
            icon: "lucide:list-checks",
            isActive:
                route().current("master-list-items.*") ||
                route().current("master-list-categories.*"),
            roles: ["Superadmin", "Canvassing Admin"],
        },
        {
            title: "Purchase Requests",
            url: route("purchase-requests.index"),
            icon: "lucide:file-plus-2",
            isActive: route().current("purchase-requests.*"),
            roles: ["Superadmin", "PR Admin"],
        },
        {
            title: "Budgeting",
            url: route("earmarks.index"),
            icon: "lucide:stamp",
            isActive: route().current("earmarks.*"),
            roles: ["Superadmin", "Budgeting Admin"],
        },
        {
            title: "Request for Quotation",
            url: route("rfqs.index"),
            icon: "lucide:file-text",
            isActive: route().current("rfqs.*"),
            roles: ["Superadmin", "Quotation Admin"],
        },
        {
            title: "Abstract of Quotation",
            url: route("aoqs.index"),
            icon: "lucide:file-spreadsheet",
            isActive: route().current("aoqs.*"),
            roles: ["Superadmin", "Quotation Admin"],
        },
        {
            title: "BAC Resolutions",
            url: route("bac-resolutions.index"),
            icon: "lucide:files",
            isActive: route().current("bac-resolutions.*"),
            roles: ["Superadmin", "BAC Reso Admin"],
        },
    ];

    // Filter items based on user role
    return items.filter((item) => {
        if (item.roles.includes("all")) return true;
        if (item.roles.includes("office") && !isSystemRole.value) return true;
        if (item.roles.includes(userRole.value)) return true;
        return false;
    });
});

const secondaryNavItems = computed(() => {
    const items = [
        {
            title: "Calendar",
            url: route("calendars.index"),
            icon: "lucide:calendar",
            isActive: route().current("calendars.*"),
            roles: ["all"], // Available to all
        },
    ];

    // Filter items based on user role
    return items.filter((item) => {
        if (item.roles.includes("all")) return true;
        if (item.roles.includes("office") && !isSystemRole.value) return true;
        if (item.roles.includes(userRole.value)) return true;
        return false;
    });
});

// Get user initials for avatar fallback
// Grouped navigation items
const navigationItems = computed(() => {
    return mainNavItems.value.filter((item) =>
        ["Dashboard", "Users", "Roles", "Offices"].includes(item.title),
    );
});

const submissionItems = computed(() => {
    return mainNavItems.value.filter((item) =>
        ["APPs", "Funds", "PPMPs", "Emanatings"].includes(item.title),
    );
});

const canvassingItems = computed(() => {
    return mainNavItems.value.filter((item) =>
        ["Canvassing", "Suppliers", "Master List"].includes(item.title),
    );
});

const purchaseRequestBudgetingItems = computed(() => {
    return mainNavItems.value.filter((item) =>
        ["Purchase Requests", "Budgeting"].includes(item.title),
    );
});

const quotationItems = computed(() => {
    return mainNavItems.value.filter((item) =>
        ["Request for Quotation", "Abstract of Quotation"].includes(item.title),
    );
});

const documentItems = computed(() => {
    return mainNavItems.value.filter((item) =>
        ["BAC Resolutions"].includes(item.title),
    );
});

// Get user initials for avatar fallback
const userInitials = computed(() => {
    if (!user.value?.name) return "U";
    return user.value.name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
});
</script>

<template>
    <Sidebar collapsible="icon">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard.index')">
                            <div
                                class="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                            >
                                <Icon icon="lucide:boxes" class="size-4" />
                            </div>
                            <div
                                class="grid flex-1 text-left text-sm leading-tight"
                            >
                                <span class="truncate font-semibold"
                                    >Procurex</span
                                >
                                <span
                                    class="truncate text-xs text-muted-foreground"
                                    >Procurement System</span
                                >
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <!-- Navigation Group -->
            <SidebarGroup v-if="navigationItems.length > 0">
                <SidebarGroupLabel>Navigation</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in navigationItems"
                            :key="item.title"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="item.isActive"
                            >
                                <Link :href="item.url">
                                    <Icon :icon="item.icon" class="size-4" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Submission Group -->
            <SidebarGroup v-if="submissionItems.length > 0">
                <SidebarGroupLabel>Submissions</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in submissionItems"
                            :key="item.title"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="item.isActive"
                            >
                                <Link :href="item.url">
                                    <Icon :icon="item.icon" class="size-4" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Canvassing & Pricing Group -->
            <SidebarGroup v-if="canvassingItems.length > 0">
                <SidebarGroupLabel>Canvassing & Pricing</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in canvassingItems"
                            :key="item.title"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="item.isActive"
                            >
                                <Link :href="item.url">
                                    <Icon :icon="item.icon" class="size-4" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Purchase Request & Budgeting Group -->
            <SidebarGroup v-if="purchaseRequestBudgetingItems.length > 0">
                <SidebarGroupLabel
                    >Purchase Request &amp; Budgeting</SidebarGroupLabel
                >
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in purchaseRequestBudgetingItems"
                            :key="item.title"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="item.isActive"
                            >
                                <Link :href="item.url">
                                    <Icon :icon="item.icon" class="size-4" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- RFQ Group -->
            <SidebarGroup v-if="quotationItems.length > 0">
                <SidebarGroupLabel>Quotations</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in quotationItems"
                            :key="item.title"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="item.isActive"
                            >
                                <Link :href="item.url">
                                    <Icon :icon="item.icon" class="size-4" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Documents Group -->
            <SidebarGroup v-if="documentItems.length > 0">
                <SidebarGroupLabel>Documents</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in documentItems"
                            :key="item.title"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="item.isActive"
                            >
                                <Link :href="item.url">
                                    <Icon :icon="item.icon" class="size-4" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <!-- Secondary Navigation -->
            <SidebarGroup>
                <SidebarGroupLabel>System</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in secondaryNavItems"
                            :key="item.title"
                        >
                            <SidebarMenuButton
                                as-child
                                :is-active="item.isActive"
                            >
                                <Link :href="item.url">
                                    <Icon :icon="item.icon" class="size-4" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <SidebarMenuButton
                                size="lg"
                                class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                            >
                                <div
                                    class="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground text-xs font-medium"
                                >
                                    {{ userInitials }}
                                </div>
                                <div
                                    class="grid flex-1 text-left text-sm leading-tight"
                                >
                                    <span class="truncate font-semibold">{{
                                        $page.props.auth.user?.name ?? "User"
                                    }}</span>
                                    <span
                                        class="truncate text-xs text-muted-foreground"
                                        >{{
                                            $page.props.auth.user?.email ?? ""
                                        }}</span
                                    >
                                </div>
                                <Icon
                                    icon="lucide:chevrons-up-down"
                                    class="ml-auto size-4"
                                />
                            </SidebarMenuButton>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            class="w-[--reka-popper-anchor-width] min-w-56 rounded-lg"
                            :side="isMobile ? 'bottom' : 'right'"
                            align="end"
                            :side-offset="4"
                        >
                            <DropdownMenuLabel class="p-0 font-normal">
                                <div
                                    class="flex items-center gap-2 px-1 py-1.5 text-left text-sm"
                                >
                                    <div
                                        class="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground text-xs font-medium"
                                    >
                                        {{ userInitials }}
                                    </div>
                                    <div
                                        class="grid flex-1 text-left text-sm leading-tight"
                                    >
                                        <span class="truncate font-semibold">{{
                                            $page.props.auth.user?.name ??
                                            "User"
                                        }}</span>
                                        <span
                                            class="truncate text-xs text-muted-foreground"
                                            >{{
                                                $page.props.auth.user?.email ??
                                                ""
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem>
                                <Icon icon="lucide:user" class="mr-2 size-4" />
                                Profile
                            </DropdownMenuItem>
                            <DropdownMenuItem>
                                <Icon
                                    icon="lucide:settings"
                                    class="mr-2 size-4"
                                />
                                Settings
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <Link
                                    :href="route('logout')"
                                    class="text-destructive focus:text-destructive"
                                >
                                    <Icon
                                        icon="lucide:log-out"
                                        class="mr-2 size-4"
                                    />
                                    Log out
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarFooter>
        <SidebarRail />
    </Sidebar>
</template>
