<script setup>
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import {
    Table,
    TableHeader,
    TableRow,
    TableHead,
    TableBody,
    TableCell,
} from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Icon } from "@iconify/vue";

defineProps({
    emanating: Object,
    comparison: Object,
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center justify-between">
                <span class="flex items-center">
                    <Icon icon="lucide:git-compare" class="mr-2 h-5 w-5" />
                    Comparison
                </span>
                <Badge
                    v-if="comparison"
                    :class="
                        comparison.status === 'all_matched'
                            ? 'bg-green-500/10 text-green-700'
                            : 'bg-orange-500/10 text-orange-700'
                    "
                >
                    {{ comparison.total_matched_items }}/{{
                        comparison.total_emanating_items
                    }}
                    Items Matched
                </Badge>
            </CardTitle>
        </CardHeader>
        <CardContent>
            <div
                v-if="!emanating.ppmp_category"
                class="text-center py-8 text-muted-foreground"
            >
                <Icon
                    icon="lucide:alert-triangle"
                    class="mx-auto h-12 w-12 mb-4"
                />
                <p>No PPMP category linked to this emanating request.</p>
            </div>

            <div v-else class="space-y-6 overflow-x-hidden">
                <div>
                    <h3 class="text-lg font-semibold mb-3 flex items-center">
                        <Icon icon="lucide:file-text" class="mr-2 h-5 w-5" />
                        Emanating Items
                    </h3>
                    <div class="border rounded-lg overflow-hidden">
                        <Table class="table-fixed w-full">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-10">#</TableHead>
                                    <TableHead class="whitespace-normal"
                                        >Description</TableHead
                                    >
                                    <TableHead>Quantity</TableHead>
                                    <TableHead>Unit</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="w-72 whitespace-normal"
                                        >Findings</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="(item, index) in comparison?.items"
                                    :key="index"
                                    :class="
                                        item.matched
                                            ? 'bg-green-50 dark:bg-green-950/30'
                                            : 'bg-orange-50 dark:bg-orange-950/30'
                                    "
                                >
                                    <TableCell class="w-10">{{
                                        index + 1
                                    }}</TableCell>
                                    <TableCell
                                        class="font-medium whitespace-normal break-words align-top"
                                    >
                                        {{ item.emanating_item?.name || "N/A" }}
                                    </TableCell>
                                    <TableCell>{{
                                        item.emanating_item?.quantity || "-"
                                    }}</TableCell>
                                    <TableCell>{{
                                        item.emanating_item?.unit || "-"
                                    }}</TableCell>
                                    <TableCell>
                                        <Badge
                                            :class="
                                                item.matched
                                                    ? 'bg-green-500/10 text-green-700 dark:bg-green-500/20 dark:text-green-400'
                                                    : 'bg-orange-500/10 text-orange-700 dark:bg-orange-500/20 dark:text-orange-400'
                                            "
                                        >
                                            <Icon
                                                :icon="
                                                    item.matched
                                                        ? 'lucide:check'
                                                        : 'lucide:x'
                                                "
                                                class="mr-1 h-3 w-3"
                                            />
                                            {{
                                                item.matched
                                                    ? "Matched"
                                                    : "Flagged"
                                            }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell
                                        :class="
                                            item.matched
                                                ? 'w-72 text-sm whitespace-pre-line break-words align-top font-semibold text-green-700 dark:text-green-300'
                                                : 'w-72 text-sm whitespace-pre-line break-words align-top font-semibold text-orange-700 dark:text-orange-300'
                                        "
                                    >
                                        {{
                                            item.mismatch_reason || "No issues"
                                        }}
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-if="
                                        !comparison?.items ||
                                        comparison.items.length === 0
                                    "
                                >
                                    <TableCell
                                        colspan="6"
                                        class="text-center text-muted-foreground py-8"
                                    >
                                        No emanating items found
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-3 flex items-center">
                        <Icon icon="lucide:database" class="mr-2 h-5 w-5" />
                        PPMP Items ({{ emanating.ppmp_category?.name }})
                    </h3>
                    <div class="border rounded-lg overflow-hidden">
                        <Table class="table-fixed w-full">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-10">#</TableHead>
                                    <TableHead class="whitespace-normal"
                                        >Item Description</TableHead
                                    >
                                    <TableHead>Quantity</TableHead>
                                    <TableHead>Unit</TableHead>
                                    <TableHead>Mode of Procurement</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="(
                                        item, index
                                    ) in comparison?.ppmp_items"
                                    :key="item.id"
                                >
                                    <TableCell class="w-10">{{
                                        index + 1
                                    }}</TableCell>
                                    <TableCell
                                        class="font-medium whitespace-normal break-words align-top"
                                        >{{ item.name }}</TableCell
                                    >
                                    <TableCell>{{ item.quantity }}</TableCell>
                                    <TableCell>{{ item.unit }}</TableCell>
                                    <TableCell>
                                        <Badge variant="outline">{{
                                            item.mode_of_procurement
                                        }}</Badge>
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-if="
                                        !comparison?.ppmp_items ||
                                        comparison.ppmp_items.length === 0
                                    "
                                >
                                    <TableCell
                                        colspan="5"
                                        class="text-center text-muted-foreground py-8"
                                    >
                                        No PPMP items found in this category
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-3 flex items-center">
                        <Icon icon="lucide:layout-list" class="mr-2 h-5 w-5" />
                        APP Items
                    </h3>
                    <div class="border rounded-lg overflow-hidden">
                        <Table class="table-fixed w-full">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-10">#</TableHead>
                                    <TableHead class="whitespace-normal"
                                        >Item Description</TableHead
                                    >
                                    <TableHead class="text-right"
                                        >Estimated Budget</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="(
                                        item, index
                                    ) in comparison?.app_items"
                                    :key="item.id"
                                >
                                    <TableCell class="w-10">{{
                                        index + 1
                                    }}</TableCell>
                                    <TableCell
                                        class="font-medium whitespace-normal break-words align-top"
                                        >{{ item.name }}</TableCell
                                    >
                                    <TableCell class="text-right">{{
                                        Number(
                                            item.estimated_budget || 0,
                                        ).toLocaleString("en-PH", {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        })
                                    }}</TableCell>
                                </TableRow>
                                <TableRow
                                    v-if="
                                        !comparison?.app_items ||
                                        comparison.app_items.length === 0
                                    "
                                >
                                    <TableCell
                                        colspan="3"
                                        class="text-center text-muted-foreground py-8"
                                    >
                                        No APP items found
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <div v-if="comparison?.is_project_fund">
                    <h3 class="text-lg font-semibold mb-3 flex items-center">
                        <Icon
                            icon="lucide:file-spreadsheet"
                            class="mr-2 h-5 w-5"
                        />
                        Work Program Items
                    </h3>
                    <div class="border rounded-lg overflow-hidden">
                        <Table class="table-fixed w-full">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-10">#</TableHead>
                                    <TableHead class="whitespace-normal"
                                        >Item Name</TableHead
                                    >
                                    <TableHead class="whitespace-normal"
                                        >Quantity / Unit</TableHead
                                    >
                                    <TableHead class="text-right"
                                        >Amount</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="(
                                        item, index
                                    ) in comparison?.work_program_items"
                                    :key="item.id"
                                >
                                    <TableCell class="w-10">{{
                                        index + 1
                                    }}</TableCell>
                                    <TableCell
                                        class="font-medium whitespace-normal break-words align-top"
                                        >{{ item.item_name }}</TableCell
                                    >
                                    <TableCell
                                        class="whitespace-normal break-words align-top"
                                        >{{
                                            item.quantity !== null
                                                ? `${Number(
                                                      item.quantity,
                                                  ).toLocaleString("en-PH", {
                                                      minimumFractionDigits: 0,
                                                      maximumFractionDigits: 2,
                                                  })}${
                                                      item.unit
                                                          ? ` ${item.unit}`
                                                          : ""
                                                  }`
                                                : item.unit || "-"
                                        }}</TableCell
                                    >
                                    <TableCell class="text-right">{{
                                        item.amount !== null
                                            ? Number(
                                                  item.amount,
                                              ).toLocaleString("en-PH", {
                                                  minimumFractionDigits: 2,
                                                  maximumFractionDigits: 2,
                                              })
                                            : "-"
                                    }}</TableCell>
                                </TableRow>
                                <TableRow
                                    v-if="
                                        !comparison?.work_program_items ||
                                        comparison.work_program_items.length ===
                                            0
                                    "
                                >
                                    <TableCell
                                        colspan="4"
                                        class="text-center text-muted-foreground py-8"
                                    >
                                        No Work Program items found
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
