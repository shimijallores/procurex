<script setup>
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";

defineProps({
    pendingReviews: Object,
    prSearch: String,
});

defineEmits(["update:pr-search", "return-click"]);

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
};
</script>

<template>
    <Card>
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle class="flex items-center gap-2">
                        <Icon
                            icon="lucide:clock"
                            class="h-5 w-5 text-muted-foreground"
                        />
                        PRs Pending Budget Review
                    </CardTitle>
                    <CardDescription>
                        Purchase Requests awaiting budget office review for unit
                        of issue
                    </CardDescription>
                </div>
                <slot name="search" />
            </div>
        </CardHeader>
        <CardContent>
            <div class="relative w-full overflow-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                PR No. / Office
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Purpose
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                PR Date
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Total Amount
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="!pendingReviews?.data?.length">
                            <td
                                colspan="5"
                                class="p-8 text-center text-muted-foreground"
                            >
                                <Icon
                                    icon="lucide:check-circle"
                                    class="mx-auto mb-2 h-8 w-8 text-green-500 opacity-60"
                                />
                                <p class="text-sm">
                                    No PRs pending budget review.
                                </p>
                            </td>
                        </tr>

                        <tr
                            v-for="pr in pendingReviews?.data"
                            :key="pr.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{
                                        pr.pr_no
                                            ? `PR #${pr.pr_no}`
                                            : `#${pr.id}`
                                    }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ pr.office?.name || "—" }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="line-clamp-2 text-sm">{{
                                    pr.purpose || "—"
                                }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                {{ formatDate(pr.pr_date) }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium">
                                {{ formatCurrency(pr.total_amount) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <!-- Issue Earmark = approve -->
                                    <Link
                                        :href="
                                            route('earmarks.create', {
                                                pr_id: pr.id,
                                            })
                                        "
                                    >
                                        <Button size="sm" class="gap-1">
                                            <Icon
                                                icon="lucide:stamp"
                                                class="h-4 w-4"
                                            />
                                            Issue Earmark
                                        </Button>
                                    </Link>
                                    <!-- Return to office -->
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        class="gap-1"
                                        @click="$emit('return-click', pr)"
                                    >
                                        <Icon
                                            icon="lucide:undo-2"
                                            class="h-4 w-4"
                                        />
                                        Return
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="pendingReviews?.last_page > 1"
                class="mt-4 flex items-center justify-between border-t pt-4"
            >
                <p class="text-sm text-muted-foreground">
                    Showing {{ pendingReviews.from }}–{{ pendingReviews.to }} of
                    {{ pendingReviews.total }}
                </p>
                <div class="flex gap-1">
                    <Link
                        v-if="pendingReviews.prev_page_url"
                        :href="pendingReviews.prev_page_url"
                        preserve-scroll
                    >
                        <Button variant="outline" size="sm">
                            <Icon icon="lucide:chevron-left" class="h-4 w-4" />
                        </Button>
                    </Link>
                    <Link
                        v-if="pendingReviews.next_page_url"
                        :href="pendingReviews.next_page_url"
                        preserve-scroll
                    >
                        <Button variant="outline" size="sm">
                            <Icon icon="lucide:chevron-right" class="h-4 w-4" />
                        </Button>
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
