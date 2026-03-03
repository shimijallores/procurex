<script setup>
import { Form, Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

const props = defineProps({
    action: String,
    route: String,
    returnRoute: String,
    offices: Array,
    projectCode: Object,
});

const isEdit = props.action === "edit";

const formatOfficeLabel = (office) => {
    if (!office) {
        return "";
    }

    if (office.acronym) {
        return `${office.code} - ${office.acronym} (${office.name})`;
    }

    return `${office.code} - ${office.name}`;
};
</script>

<template>
    <Card class="max-w-2xl">
        <CardHeader>
            <CardTitle>Project Code Details</CardTitle>
            <CardDescription>
                {{
                    isEdit
                        ? "Update the project code information"
                        : "Enter the project code information"
                }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Form
                :action="route"
                :method="isEdit ? 'put' : 'post'"
                class="space-y-6"
                #default="{ errors, processing }"
            >
                <div class="space-y-2">
                    <Label for="office_id">Office</Label>
                    <select
                        id="office_id"
                        name="office_id"
                        :defaultValue="projectCode?.office_id ?? ''"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            errors.office_id ? 'border-destructive' : '',
                        ]"
                    >
                        <option value="">Select office</option>
                        <option
                            v-for="office in offices"
                            :key="office.id"
                            :value="office.id"
                        >
                            {{ formatOfficeLabel(office) }}
                        </option>
                    </select>
                    <p v-if="errors.office_id" class="text-sm text-destructive">
                        {{ errors.office_id }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="code">Project Code</Label>
                    <input
                        id="code"
                        name="code"
                        type="text"
                        :defaultValue="projectCode?.code ?? ''"
                        placeholder="Enter project code"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                            'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            errors.code ? 'border-destructive' : '',
                        ]"
                    />
                    <p v-if="errors.code" class="text-sm text-destructive">
                        {{ errors.code }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="name">Project Name</Label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        :defaultValue="projectCode?.name ?? ''"
                        placeholder="Enter project name"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                            'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            errors.name ? 'border-destructive' : '',
                        ]"
                    />
                    <p v-if="errors.name" class="text-sm text-destructive">
                        {{ errors.name }}
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="processing">
                        <Icon
                            v-if="processing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Icon
                            v-else
                            :icon="isEdit ? 'lucide:save' : 'lucide:plus'"
                            class="mr-2 h-4 w-4"
                        />
                        {{ isEdit ? "Save Changes" : "Create Project Code" }}
                    </Button>
                    <Link :href="returnRoute">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
