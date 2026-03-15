<script setup>
import { Link, Form } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { computed, ref } from "vue";
import Layout from "@/Layout/Layout.vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Users", href: route("users.index") },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    user: Object,
    roles: Array,
    offices: Array,
    systemRoles: Array,
});

const selectedRoleIds = ref(
    props.user.roles?.map((role) => String(role.id)) ?? [],
);

const requiresOffice = computed(() => {
    const selectedRoles = props.roles?.filter((role) =>
        selectedRoleIds.value.includes(String(role.id)),
    );

    return selectedRoles?.some((role) => !role.is_system_role);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('users.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Edit User
                </h1>
                <p class="text-muted-foreground">Update user information</p>
            </div>
        </div>

        <!-- Form Card -->
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>User Details</CardTitle>
                <CardDescription>
                    Modify the user information below
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    :action="route('users.update', user.id)"
                    method="put"
                    class="space-y-6"
                    #default="{ errors, processing }"
                >
                    <div class="space-y-2">
                        <Label for="name">Name</Label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            :defaultValue="user.name"
                            placeholder="Enter user name"
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

                    <div class="space-y-2">
                        <Label for="email">Email</Label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            :defaultValue="user.email"
                            placeholder="Enter email address"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                errors.email ? 'border-destructive' : '',
                            ]"
                        />
                        <p v-if="errors.email" class="text-sm text-destructive">
                            {{ errors.email }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="role_ids">Roles</Label>
                        <select
                            id="role_ids"
                            name="role_ids[]"
                            multiple
                            @change="
                                (event) =>
                                    (selectedRoleIds = Array.from(
                                        event.target.selectedOptions,
                                        (option) => option.value,
                                    ))
                            "
                            :class="[
                                'flex min-h-28 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                errors.role_ids ? 'border-destructive' : '',
                            ]"
                        >
                            <option
                                v-for="role in roles"
                                :key="role.id"
                                :value="role.id"
                                :selected="
                                    selectedRoleIds.includes(String(role.id))
                                "
                            >
                                {{ role.name }}
                            </option>
                        </select>
                        <p class="text-xs text-muted-foreground">
                            Hold Ctrl or Cmd to select multiple roles.
                        </p>
                        <p
                            v-if="errors.role_ids"
                            class="text-sm text-destructive"
                        >
                            {{ errors.role_ids }}
                        </p>
                        <p
                            v-if="errors['role_ids.0']"
                            class="text-sm text-destructive"
                        >
                            {{ errors["role_ids.0"] }}
                        </p>
                    </div>

                    <div v-if="requiresOffice" class="space-y-2">
                        <Label for="office_id">Office</Label>
                        <select
                            id="office_id"
                            name="office_id"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                errors.office_id ? 'border-destructive' : '',
                            ]"
                        >
                            <option value="">Select an office</option>
                            <option
                                v-for="office in offices"
                                :key="office.id"
                                :value="office.id"
                                :selected="user.office_id === office.id"
                            >
                                {{ office.name }}
                            </option>
                        </select>
                        <p
                            v-if="errors.office_id"
                            class="text-sm text-destructive"
                        >
                            {{ errors.office_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="password"
                            >Password (Leave blank to keep current)</Label
                        >
                        <input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Enter new password"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                errors.password ? 'border-destructive' : '',
                            ]"
                        />
                        <p
                            v-if="errors.password"
                            class="text-sm text-destructive"
                        >
                            {{ errors.password }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="password_confirmation"
                            >Confirm Password</Label
                        >
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            placeholder="Confirm new password"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            ]"
                        />
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
                                icon="lucide:save"
                                class="mr-2 h-4 w-4"
                            />
                            Save Changes
                        </Button>
                        <Link :href="route('users.index')">
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </Link>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
