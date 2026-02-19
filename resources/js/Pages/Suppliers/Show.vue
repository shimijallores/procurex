<script setup lang="ts">
import Layout from "@/Layout/Layout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Route } from "ziggy-js";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { Icon } from "@iconify/vue";
import { route } from "ziggy-js";

interface MasterListCategory {
    id: number;
    name: string;
}

interface MasterListItem {
    id: number;
    item_name: string;
    unit: string;
    unit_price: number;
    is_phased_out: boolean;
    master_list_category: MasterListCategory | null;
}

interface Supplier {
    id: number;
    name: string;
    contact_person: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    tin: string | null;
    is_active: boolean;
    master_list_items: MasterListItem[];
}

const props = defineProps<{
    supplier: Supplier;
}>();

const formatCurrency = (value: number) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value);
</script>

<template>
    <Layout>
        <Head :title="supplier.name" />

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Link :href="route('suppliers.index')">
                            <Button variant="outline" size="icon">
                                <Icon
                                    icon="lucide:arrow-left"
                                    class="h-4 w-4"
                                />
                            </Button>
                        </Link>
                        <div>
                            <h1
                                class="text-2xl font-bold text-gray-900 dark:text-white"
                            >
                                {{ supplier.name }}
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Supplier Profile
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge
                            :variant="
                                supplier.is_active ? 'default' : 'secondary'
                            "
                        >
                            {{ supplier.is_active ? "Active" : "Inactive" }}
                        </Badge>
                        <Link :href="route('suppliers.edit', supplier.id)">
                            <Button variant="outline" size="sm">
                                <Icon
                                    icon="lucide:pencil"
                                    class="mr-2 h-4 w-4"
                                />
                                Edit
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Supplier Info Card -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>Contact Information</CardTitle>
                        <CardDescription
                            >Supplier details and contact
                            information</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <dl
                            class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2"
                        >
                            <div>
                                <dt
                                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                >
                                    Contact Person
                                </dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 dark:text-white"
                                >
                                    {{ supplier.contact_person ?? "—" }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                >
                                    Email Address
                                </dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 dark:text-white"
                                >
                                    <a
                                        v-if="supplier.email"
                                        :href="`mailto:${supplier.email}`"
                                        class="text-blue-600 hover:underline dark:text-blue-400"
                                    >
                                        {{ supplier.email }}
                                    </a>
                                    <span v-else>—</span>
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                >
                                    Phone Number
                                </dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 dark:text-white"
                                >
                                    {{ supplier.phone ?? "—" }}
                                </dd>
                            </div>
                            <div>
                                <dt
                                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                >
                                    TIN
                                </dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 dark:text-white"
                                >
                                    {{ supplier.tin ?? "—" }}
                                </dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt
                                    class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                >
                                    Address
                                </dt>
                                <dd
                                    class="mt-1 text-sm text-gray-900 dark:text-white"
                                >
                                    {{ supplier.address ?? "—" }}
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>

                <!-- Master List Items Card -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Master List Items</CardTitle>
                                <CardDescription
                                    >Items supplied by
                                    {{ supplier.name }}</CardDescription
                                >
                            </div>
                            <Badge variant="outline"
                                >{{
                                    supplier.master_list_items.length
                                }}
                                items</Badge
                            >
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div
                            v-if="supplier.master_list_items.length === 0"
                            class="flex flex-col items-center justify-center py-12 text-center"
                        >
                            <Icon
                                icon="lucide:package-open"
                                class="mb-3 h-10 w-10 text-gray-300"
                            />
                            <p class="text-sm text-gray-500">
                                No items added for this supplier yet.
                            </p>
                            <Link
                                :href="route('master-list-items.create')"
                                class="mt-3"
                            >
                                <Button size="sm" variant="outline">
                                    <Icon
                                        icon="lucide:plus"
                                        class="mr-2 h-4 w-4"
                                    />
                                    Add Item
                                </Button>
                            </Link>
                        </div>

                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Item Name</TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead>Unit</TableHead>
                                    <TableHead class="text-right"
                                        >Unit Price</TableHead
                                    >
                                    <TableHead class="text-center"
                                        >Status</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="item in supplier.master_list_items"
                                    :key="item.id"
                                    :class="
                                        item.is_phased_out ? 'opacity-50' : ''
                                    "
                                >
                                    <TableCell class="font-medium">{{
                                        item.item_name
                                    }}</TableCell>
                                    <TableCell>{{
                                        item.master_list_category?.name ?? "—"
                                    }}</TableCell>
                                    <TableCell>{{ item.unit }}</TableCell>
                                    <TableCell class="text-right">{{
                                        formatCurrency(item.unit_price)
                                    }}</TableCell>
                                    <TableCell class="text-center">
                                        <Badge
                                            v-if="item.is_phased_out"
                                            variant="destructive"
                                            >Phased Out</Badge
                                        >
                                        <Badge v-else variant="secondary"
                                            >Active</Badge
                                        >
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </Layout>
</template>
