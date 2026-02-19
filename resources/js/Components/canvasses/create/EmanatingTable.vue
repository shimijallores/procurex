<script setup>
import { ref, computed } from "vue";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const props = defineProps({
    emanatings: Array,
    selectedEmanating: Object,
});

const emit = defineEmits(["select-emanating"]);

const searchQuery = ref("");
const officeFilter = ref("");

const offices = computed(() => {
    const unique = new Set(
        props.emanatings?.map((em) => em.project?.fund?.office?.name) || [],
    );
    return Array.from(unique).filter(Boolean).sort();
});

const filteredEmanatings = computed(() => {
    return (
        props.emanatings?.filter((em) => {
            const matchesSearch =
                searchQuery.value === "" ||
                em.pr_no
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                em.project?.name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase());

            const matchesOffice =
                officeFilter.value === "" ||
                em.project?.fund?.office?.name === officeFilter.value;

            return matchesSearch && matchesOffice;
        }) || []
    );
});

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Select Emanating Request</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Search and filter -->
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <Icon
                        icon="lucide:search"
                        class="h-4 w-4 text-muted-foreground"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by PR No or project name..."
                        class="flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                </div>

                <select
                    v-model="officeFilter"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                    <option value="">All Offices</option>
                    <option
                        v-for="office in offices"
                        :key="office"
                        :value="office"
                    >
                        {{ office }}
                    </option>
                </select>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto border rounded-lg">
                <table class="w-full text-sm">
                    <thead class="bg-muted">
                        <tr>
                            <th class="p-3 text-left font-medium">PR No</th>
                            <th class="p-3 text-left font-medium">Project</th>
                            <th class="p-3 text-left font-medium">Office</th>
                            <th class="p-3 text-left font-medium">FY</th>
                            <th class="p-3 text-center font-medium">Select</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="emanating in filteredEmanatings"
                            :key="emanating.id"
                            class="border-t hover:bg-muted/50"
                            :class="{
                                'bg-blue-50 dark:bg-blue-950':
                                    selectedEmanating?.id === emanating.id,
                            }"
                        >
                            <td class="p-3">{{ emanating.pr_no || "—" }}</td>
                            <td class="p-3 max-w-xs">
                                <div class="line-clamp-2">
                                    {{ emanating.project?.name }}
                                </div>
                            </td>
                            <td class="p-3 text-sm text-muted-foreground">
                                {{ emanating.project?.fund?.office?.name }}
                            </td>
                            <td class="p-3 text-sm text-muted-foreground">
                                FY {{ emanating.fiscal_year }}
                            </td>
                            <td class="p-3 text-center">
                                <Button
                                    v-if="
                                        selectedEmanating?.id === emanating.id
                                    "
                                    size="sm"
                                    variant="default"
                                    disabled
                                >
                                    <Icon icon="lucide:check" class="h-4 w-4" />
                                </Button>
                                <Button
                                    v-else
                                    size="sm"
                                    variant="outline"
                                    @click="emit('select-emanating', emanating)"
                                >
                                    <Icon icon="lucide:plus" class="h-4 w-4" />
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="filteredEmanatings.length === 0"
                class="text-center py-8"
            >
                <Icon
                    icon="lucide:inbox"
                    class="h-12 w-12 mx-auto text-muted-foreground mb-2"
                />
                <p class="text-muted-foreground">
                    No approved emanating requests available.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
