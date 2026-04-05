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
    "SuperAdmin",
    "Checking Admin",
    "Canvassing Admin",
    "PR Admin",
    "RFQ Admin",
    "Abstract Admin",
    "Resolution Admin",
    "NOA Admin",
    "PO Admin",
    "Inspection Admin",
];

const userRoles = computed(() =>
    (user.value?.roles ?? []).map((role) => role.name),
);

const isSystemRole = computed(() =>
    userRoles.value.some((roleName) => SYSTEM_ROLES.includes(roleName)),
);

const hasAccess = (allowedRoles) => {
    if (allowedRoles.includes("all")) {
        return true;
    }

    if (allowedRoles.includes("office") && !isSystemRole.value) {
        return true;
    }

    return userRoles.value.some((roleName) => allowedRoles.includes(roleName));
};

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
            title: "Procurement Map (Beta)",
            url: route("procurement-map.index"),
            icon: "lucide:workflow",
            isActive: route().current("procurement-map.*"),
            roles: ["all"],
        },
        {
            title: "Users",
            url: route("users.index"),
            icon: "lucide:users",
            isActive: route().current("users.*"),
            roles: ["SuperAdmin"], // Available to all
        },
        {
            title: "Roles",
            url: route("roles.index"),
            icon: "lucide:shield",
            isActive: route().current("roles.*"),
            roles: ["SuperAdmin"], // Superadmin only
        },
        {
            title: "Offices & General Codes",
            url: route("offices.index"),
            icon: "lucide:building-2",
            isActive: route().current("offices.*"),
            roles: ["SuperAdmin"], // Superadmin only
        },
        {
            title: "Project Codes",
            url: route("project-codes.index"),
            icon: "lucide:tags",
            isActive: route().current("project-codes.*"),
            roles: ["SuperAdmin"],
        },
        {
            title: "Accounts",
            url: route("accounts.index"),
            icon: "lucide:book-marked",
            isActive: route().current("accounts.*"),
            roles: ["SuperAdmin"],
        },
        {
            title: "APPs",
            url: route("apps.index"),
            icon: "lucide:clipboard-check",
            isActive: route().current("apps.*"),
            roles: ["SuperAdmin", "Checking Admin", "office"],
        },
        {
            title: "Funds",
            url: route("funds.index"),
            icon: "lucide:wallet",
            isActive: route().current("funds.*"),
            roles: ["SuperAdmin", "Checking Admin", "office"],
        },
        {
            title: "PPMPs",
            url: route("ppmps.index"),
            icon: "lucide:clipboard-list",
            isActive: route().current("ppmps.*"),
            roles: ["SuperAdmin", "Checking Admin", "office"],
        },
        {
            title: "Emanatings",
            url: route("emanatings.index"),
            icon: "lucide:clipboard-minus",
            isActive: route().current("emanatings.*"),
            roles: ["SuperAdmin", "Checking Admin", "office"],
        },
        {
            title: "Canvassing",
            url: route("canvasses.index"),
            icon: "lucide:shopping-cart",
            isActive: route().current("canvasses.*"),
            roles: ["SuperAdmin", "Canvassing Admin"],
        },
        {
            title: "Suppliers",
            url: route("suppliers.index"),
            icon: "lucide:truck",
            isActive: route().current("suppliers.*"),
            roles: ["SuperAdmin", "Canvassing Admin"],
        },
        {
            title: "Master List",
            url: route("master-list-items.index"),
            icon: "lucide:list-checks",
            isActive:
                route().current("master-list-items.*") ||
                route().current("master-list-categories.*"),
            roles: ["SuperAdmin", "Canvassing Admin"],
        },
        {
            title: "Purchase Requests",
            url: route("purchase-requests.index"),
            icon: "lucide:file-plus-2",
            isActive: route().current("purchase-requests.*"),
            roles: ["SuperAdmin", "PR Admin"],
        },
        {
            title: "PR Matrix",
            url: route("purchase-request-matrix.index"),
            icon: "lucide:table-properties",
            isActive: route().current("purchase-request-matrix.*"),
            roles: ["SuperAdmin", "PR Admin"],
        },
        {
            title: "SVP Matrix",
            url: route("svp-matrix.index"),
            icon: "lucide:grid-2x2",
            isActive: route().current("svp-matrix.*"),
            roles: [
                "SuperAdmin",
                "PO Admin",
                "Inspection Admin",
                "RFQ Admin",
                "Abstract Admin",
                "Resolution Admin",
                "NOA Admin",
            ],
        },
        {
            title: "Request for Quotation",
            url: route("rfqs.index"),
            icon: "lucide:file-text",
            isActive: route().current("rfqs.*"),
            roles: ["SuperAdmin", "RFQ Admin"],
        },
        {
            title: "Abstract of Quotation",
            url: route("aoqs.index"),
            icon: "lucide:file-spreadsheet",
            isActive: route().current("aoqs.*"),
            roles: ["SuperAdmin", "Abstract Admin"],
        },
        {
            title: "BAC Resolutions",
            url: route("bac-resolutions.index"),
            icon: "lucide:files",
            isActive: route().current("bac-resolutions.*"),
            roles: ["SuperAdmin", "Resolution Admin"],
        },
        {
            title: "Notice of Award",
            url: route("noas.index"),
            icon: "lucide:file-badge",
            isActive: route().current("noas.*"),
            roles: ["SuperAdmin", "NOA Admin"],
        },
        {
            title: "Purchase Order",
            url: route("purchase-orders.index"),
            icon: "lucide:file-signature",
            isActive: route().current("purchase-orders.*"),
            roles: ["SuperAdmin", "PO Admin"],
        },
        {
            title: "PO Transmittal",
            url: route("po-transmittals.index"),
            icon: "lucide:files",
            isActive: route().current("po-transmittals.*"),
            roles: ["SuperAdmin", "PO Admin"],
        },
        {
            title: "Acceptance & Inspection",
            url: route("acceptance-inspections.index"),
            icon: "lucide:clipboard-check",
            isActive: route().current("acceptance-inspections.*"),
            roles: ["SuperAdmin", "PO Admin", "Inspection Admin"],
        },
        {
            title: "COA Inspection",
            url: route("coa-inspections.index"),
            icon: "lucide:file-check-2",
            isActive: route().current("coa-inspections.*"),
            roles: ["SuperAdmin", "PO Admin", "Inspection Admin"],
        },
    ];

    // Filter items based on user role
    return items.filter((item) => hasAccess(item.roles));
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
    return items.filter((item) => hasAccess(item.roles));
});

// Get user initials for avatar fallback
// Grouped navigation items
const navigationItems = computed(() => {
    return mainNavItems.value.filter((item) =>
        [
            "Dashboard",
            "Procurement Map (Beta)",
            "Users",
            "Roles",
            "Offices & General Codes",
            "Project Codes",
            "Accounts",
        ].includes(item.title),
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
        ["Purchase Requests"].includes(item.title),
    );
});

const matrixItems = computed(() => {
    return mainNavItems.value.filter((item) =>
        ["PR Matrix", "SVP Matrix"].includes(item.title),
    );
});

const quotationItems = computed(() => {
    return mainNavItems.value.filter((item) =>
        ["Request for Quotation", "Abstract of Quotation"].includes(item.title),
    );
});

const documentItems = computed(() => {
    return mainNavItems.value.filter((item) =>
        [
            "BAC Resolutions",
            "Notice of Award",
            "Purchase Order",
            "PO Transmittal",
        ].includes(item.title),
    );
});

const inspectionItems = computed(() => {
    return mainNavItems.value.filter((item) =>
        ["Acceptance & Inspection", "COA Inspection"].includes(item.title),
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
                                class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-lg border bg-background"
                            >
                                <img
                                    src="/images/batangas-seal.png"
                                    alt="Province of Batangas Seal"
                                    class="size-7 object-contain"
                                />
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

            <!-- Purchase Request Group -->
            <SidebarGroup v-if="purchaseRequestBudgetingItems.length > 0">
                <SidebarGroupLabel>Purchase Requests</SidebarGroupLabel>
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

            <!-- Matrix Group -->
            <SidebarGroup v-if="matrixItems.length > 0">
                <SidebarGroupLabel>Matrix</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in matrixItems"
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

            <!-- Inspection Group -->
            <SidebarGroup v-if="inspectionItems.length > 0">
                <SidebarGroupLabel>Inspection</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in inspectionItems"
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
