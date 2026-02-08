<script setup>
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

defineProps({
    ppmps: Object,
});

const emit = defineEmits(["paginate"]);
</script>

<template>
    <Card v-if="ppmps?.data?.length > 0">
        <CardContent class="pt-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-muted-foreground">
                    Showing {{ ppmps.from }} to {{ ppmps.to }} of
                    {{ ppmps.total }} entries
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="ppmps.prev_page_url"
                        :href="ppmps.prev_page_url"
                        preserve-state
                    >
                        <Button variant="outline" size="sm">
                            <Icon
                                icon="lucide:chevron-left"
                                class="h-4 w-4"
                            />
                            Previous
                        </Button>
                    </Link>
                    <Link
                        v-if="ppmps.next_page_url"
                        :href="ppmps.next_page_url"
                        preserve-state
                    >
                        <Button variant="outline" size="sm">
                            Next
                            <Icon
                                icon="lucide:chevron-right"
                                class="ml-2 h-4 w-4"
                            />
                        </Button>
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
