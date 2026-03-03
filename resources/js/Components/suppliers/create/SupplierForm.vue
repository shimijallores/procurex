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
    supplier: Object,
    errors: Object,
    processing: Boolean,
});

const isEdit = props.action === "edit";
</script>

<template>
    <Card class="max-w-2xl">
        <CardHeader>
            <CardTitle>Supplier Details</CardTitle>
            <CardDescription>{{
                isEdit
                    ? "Update the supplier's information"
                    : "Enter the supplier's information"
            }}</CardDescription>
        </CardHeader>
        <CardContent>
            <Form
                :action="route"
                :method="isEdit ? 'put' : 'post'"
                #default="{ errors: formErrors, processing }"
            >
                <div class="space-y-2">
                    <Label for="name"
                        >Supplier Name
                        <span class="text-destructive">*</span></Label
                    >
                    <input
                        id="name"
                        name="name"
                        type="text"
                        :defaultValue="supplier?.name ?? ''"
                        placeholder="e.g. ABC Office Supplies"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <p v-if="formErrors?.name" class="text-sm text-destructive">
                        {{ formErrors.name }}
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="proprietor">Proprietor</Label>
                        <input
                            id="proprietor"
                            name="proprietor"
                            type="text"
                            :defaultValue="supplier?.proprietor ?? ''"
                            placeholder="Proprietor name"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="authorized_representative"
                            >Authorized Representative</Label
                        >
                        <input
                            id="authorized_representative"
                            name="authorized_representative"
                            type="text"
                            :defaultValue="
                                supplier?.authorized_representative ?? ''
                            "
                            placeholder="Authorized representative"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="owner">Owner</Label>
                        <input
                            id="owner"
                            name="owner"
                            type="text"
                            :defaultValue="supplier?.owner ?? ''"
                            placeholder="Owner name"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="contact_person">Contact Person</Label>
                        <input
                            id="contact_person"
                            name="contact_person"
                            type="text"
                            :defaultValue="supplier?.contact_person ?? ''"
                            placeholder="Full name"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="contact_number">Contact Number</Label>
                        <input
                            id="contact_number"
                            name="contact_number"
                            type="text"
                            :defaultValue="supplier?.contact_number ?? ''"
                            placeholder="+63 XXX XXX XXXX"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            :defaultValue="supplier?.email ?? ''"
                            placeholder="supplier@example.com"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                        <p
                            v-if="formErrors?.email"
                            class="text-sm text-destructive"
                        >
                            {{ formErrors.email }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="tin">TIN</Label>
                        <input
                            id="tin"
                            name="tin"
                            type="text"
                            :defaultValue="supplier?.tin ?? ''"
                            placeholder="000-000-000-000"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="address">Address</Label>
                    <textarea
                        id="address"
                        name="address"
                        rows="2"
                        placeholder="Complete address"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >{{ supplier?.address ?? "" }}</textarea
                    >
                </div>

                <div class="space-y-2">
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        name="remarks"
                        rows="2"
                        placeholder="Optional notes"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >{{ supplier?.remarks ?? "" }}</textarea
                    >
                </div>

                <div class="flex items-center gap-2">
                    <input
                        id="is_active"
                        name="is_active"
                        type="checkbox"
                        value="1"
                        :defaultChecked="supplier?.is_active ?? true"
                        class="h-4 w-4 rounded border-input"
                    />
                    <Label for="is_active">Active supplier</Label>
                </div>

                <div class="flex gap-3 pt-2">
                    <Button type="submit" :disabled="processing">
                        <Icon icon="lucide:save" class="mr-2 h-4 w-4" />
                        {{ isEdit ? "Update" : "Save" }} Supplier
                    </Button>
                    <Link :href="returnRoute">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
