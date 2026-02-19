<script setup>
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

defineProps({
    supplier: Object,
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Contact Information</CardTitle>
            <CardDescription
                >Supplier details and contact information</CardDescription
            >
        </CardHeader>
        <CardContent>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Contact Person
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ supplier.contact_person ?? "—" }}
                    </dd>
                </div>
                <div>
                    <dt
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Email Address
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        <a
                            v-if="supplier.email"
                            :href="`mailto:${supplier.email}`"
                            class="text-blue-600 hover:underline dark:text-blue-400"
                        >
                            {{ supplier.email }}
                        </a>
                        <span v-else>—</span>
                    </dd>
                </div>
                <div>
                    <dt
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Phone Number
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ supplier.phone ?? "—" }}
                    </dd>
                </div>
                <div>
                    <dt
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        TIN
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ supplier.tin ?? "—" }}
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt
                        class="text-sm font-medium text-gray-500 dark:text-gray-400"
                    >
                        Address
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ supplier.address ?? "—" }}
                    </dd>
                </div>
            </dl>
        </CardContent>
    </Card>
</template>
