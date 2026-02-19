<script setup>
import { computed, watch } from "vue";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    eligibleResolutions: Array,
});

defineEmits(["submit"]);

const selectedResolution = computed(() =>
    props.eligibleResolutions?.find(
        (resolution) =>
            String(resolution.id) === String(props.form.bac_resolution_id),
    ),
);

watch(
    () => props.form.bac_resolution_id,
    (id) => {
        const resolution = props.eligibleResolutions?.find(
            (entry) => String(entry.id) === String(id),
        );

        if (!resolution) return;

        props.form.noa_no = resolution.aoq?.rfq?.svp_no || "";
        props.form.noa_date = resolution.aoq?.rfq?.rfq_date || "";
    },
);
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:link" class="h-4 w-4 text-primary" />
                    Source BAC Resolution
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="bac_resolution_id">BAC Resolution</Label>
                    <select
                        id="bac_resolution_id"
                        :value="form.bac_resolution_id"
                        @change="form.bac_resolution_id = $event.target.value"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Select BAC Resolution —</option>
                        <option
                            v-for="resolution in eligibleResolutions"
                            :key="resolution.id"
                            :value="resolution.id"
                        >
                            {{ resolution.resolution_no }} —
                            {{ resolution.project_name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors?.bac_resolution_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.bac_resolution_id }}
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="noa_no">NOA Number</Label>
                        <input
                            id="noa_no"
                            v-model="form.noa_no"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p class="text-xs text-muted-foreground">
                            Default comes from RFQ SVP No.
                        </p>
                        <p
                            v-if="form.errors?.noa_no"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.noa_no }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="noa_date">NOA Date</Label>
                        <input
                            id="noa_date"
                            v-model="form.noa_date"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p class="text-xs text-muted-foreground">
                            Default comes from RFQ date and remains editable.
                        </p>
                        <p
                            v-if="form.errors?.noa_date"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.noa_date }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card v-if="selectedResolution">
            <CardHeader>
                <CardTitle class="text-base">Preview</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-3 text-sm md:grid-cols-2">
                <div>
                    <p class="text-muted-foreground">SVP No.</p>
                    <p class="font-medium">
                        {{ selectedResolution.aoq?.rfq?.svp_no || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">RFQ Date</p>
                    <p class="font-medium">
                        {{ selectedResolution.aoq?.rfq?.rfq_date || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Project</p>
                    <p class="font-medium">
                        {{ selectedResolution.project_name || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Winner Supplier</p>
                    <p class="font-medium">
                        {{ selectedResolution.winner_supplier_name || "—" }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="form.reset()"
                >Reset</Button
            >
            <Button type="submit" :disabled="form.processing">
                <Icon
                    v-if="form.processing"
                    icon="lucide:loader-2"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                Create NOA
            </Button>
        </div>
    </form>
</template>
