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

const user = computed(() => page.props.auth?.user.data);

// Navigation items
const mainNavItems = computed(() => {
    // Access page.url to make this reactive to route changes
    const currentUrl = page.url;

    return [
        {
            title: "Dashboard",
            url: route("dashboard.index"),
            icon: "lucide:layout-dashboard",
            isActive: route().current("dashboard.*"),
        },
        {
            title: "Users",
            url: route("users.index"),
            icon: "lucide:users",
            isActive: route().current("users.*"),
        },
        {
            title: "Roles",
            url: route("roles.index"),
            icon: "lucide:shield",
            isActive: route().current("roles.*"),
        },
        {
            title: "Offices",
            url: route("offices.index"),
            icon: "lucide:building-2",
            isActive: route().current("offices.*"),
        },
        {
            title: "Funds",
            url: route("funds.index"),
            icon: "lucide:wallet",
            isActive: route().current("funds.*"),
        },
        {
            title: "APPs",
            url: route("apps.index"),
            icon: "lucide:clipboard-check",
            isActive: route().current("apps.*"),
        },
        {
            title: "PPMPs",
            url: route("ppmps.index"),
            icon: "lucide:clipboard-list",
            isActive: route().current("ppmps.*"),
        },
        {
            title: "Emanatings",
            url: route("emanatings.index"),
            icon: "lucide:clipboard-minus",
            isActive: route().current("emanatings.*"),
        },
    ];
});

const secondaryNavItems = [
    {
        title: "Calendar",
        url: route("calendars.index"),
        icon: "lucide:calendar",
        isActive: route().current("calendars.*"),
    },
    {
        title: "Reports",
        url: "#",
        icon: "lucide:bar-chart-3",
        isActive: false,
    },
    {
        title: "Settings",
        url: "#",
        icon: "lucide:settings",
        isActive: false,
    },
];

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
            <!-- Main Navigation -->
            <SidebarGroup>
                <SidebarGroupLabel>Navigation</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem
                            v-for="item in mainNavItems"
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
                                        user?.name ?? "User"
                                    }}</span>
                                    <span
                                        class="truncate text-xs text-muted-foreground"
                                        >{{ user?.email ?? "" }}</span
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
                                            user?.name ?? "User"
                                        }}</span>
                                        <span
                                            class="truncate text-xs text-muted-foreground"
                                            >{{ user?.email ?? "" }}</span
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
