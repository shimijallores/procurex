<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
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

defineProps({
    supplier: Object,
    formatCurrency: Function,
});
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle>Master List Items</CardTitle>
                    <CardDescription
                        >Items supplied by {{ supplier.name }}</CardDescription
                    >
                </div>
                <Badge variant="outline">
                    {{ supplier.master_list_items.length }} items
                </Badge>
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
                <Link :href="route('master-list-items.create')" class="mt-3">
                    <Button size="sm" variant="outline">
                        <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
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
                        <TableHead class="text-right">Unit Price</TableHead>
                        <TableHead class="text-center">Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="item in supplier.master_list_items"
                        :key="item.id"
                        :class="item.is_phased_out ? 'opacity-50' : ''"
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
                            <Badge v-else variant="secondary">Active</Badge>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </CardContent>
    </Card>
</template>
