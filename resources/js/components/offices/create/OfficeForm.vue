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
    office: Object,
    errors: Object,
    processing: Boolean,
});

const isEdit = props.action === "edit";
</script>

<template>
    <Card class="max-w-2xl">
        <CardHeader>
            <CardTitle>Office Details</CardTitle>
            <CardDescription>
                {{
                    isEdit
                        ? "Modify the office information below"
                        : "Enter the information for the new office"
                }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <Form
                :action="route"
                :method="isEdit ? 'put' : 'post'"
                class="space-y-6"
                #default="{ errors: formErrors, processing: formProcessing }"
            >
                <div class="space-y-2">
                    <Label for="name">Office Name</Label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        :defaultValue="office?.name ?? ''"
                        placeholder="Enter office name"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                            'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            formErrors.name ? 'border-destructive' : '',
                        ]"
                    />
                    <p v-if="formErrors.name" class="text-sm text-destructive">
                        {{ formErrors.name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="code">Office Code</Label>
                    <input
                        id="code"
                        name="code"
                        type="text"
                        :defaultValue="office?.code ?? ''"
                        placeholder="Enter office code"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                            'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            formErrors.code ? 'border-destructive' : '',
                        ]"
                    />
                    <p v-if="formErrors.code" class="text-sm text-destructive">
                        {{ formErrors.code }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="acronym">Acronym</Label>
                    <input
                        id="acronym"
                        name="acronym"
                        type="text"
                        :defaultValue="office?.acronym ?? ''"
                        placeholder="Enter office acronym (optional)"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                            'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            formErrors.acronym ? 'border-destructive' : '',
                        ]"
                    />
                    <p
                        v-if="formErrors.acronym"
                        class="text-sm text-destructive"
                    >
                        {{ formErrors.acronym }}
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="formProcessing">
                        <Icon
                            v-if="formProcessing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Icon
                            v-else
                            :icon="isEdit ? 'lucide:save' : 'lucide:plus'"
                            class="mr-2 h-4 w-4"
                        />
                        {{ isEdit ? "Save Changes" : "Create Office" }}
                    </Button>
                    <Link :href="returnRoute">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
