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
    account: Object,
});

const isEdit = props.action === "edit";

const mainCategories = [
    "Personal Services",
    "MOOE",
    "Repair and Maintenance",
    "Capital Outlay",
];
</script>

<template>
    <Card class="max-w-2xl">
        <CardHeader>
            <CardTitle>Account Details</CardTitle>
            <CardDescription>
                {{
                    isEdit
                        ? "Update the account information"
                        : "Enter the account information"
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
                    <Label for="main_category">Main Category</Label>
                    <select
                        id="main_category"
                        name="main_category"
                        :defaultValue="account?.main_category ?? ''"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            errors.main_category ? 'border-destructive' : '',
                        ]"
                    >
                        <option value="">Select main category</option>
                        <option
                            v-for="category in mainCategories"
                            :key="category"
                            :value="category"
                        >
                            {{ category }}
                        </option>
                    </select>
                    <p
                        v-if="errors.main_category"
                        class="text-sm text-destructive"
                    >
                        {{ errors.main_category }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="subcategory">Subcategory</Label>
                    <input
                        id="subcategory"
                        name="subcategory"
                        type="text"
                        :defaultValue="account?.subcategory ?? ''"
                        placeholder="Enter subcategory (optional)"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                            'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            errors.subcategory ? 'border-destructive' : '',
                        ]"
                    />
                    <p
                        v-if="errors.subcategory"
                        class="text-sm text-destructive"
                    >
                        {{ errors.subcategory }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="code">Account Code</Label>
                    <input
                        id="code"
                        name="code"
                        type="text"
                        :defaultValue="account?.code ?? ''"
                        placeholder="e.g. 5 01 01 010"
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
                    <Label for="name">Account Name</Label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        :defaultValue="account?.name ?? ''"
                        placeholder="Enter account name"
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
                        {{ isEdit ? "Save Changes" : "Create Account" }}
                    </Button>
                    <Link :href="returnRoute">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
