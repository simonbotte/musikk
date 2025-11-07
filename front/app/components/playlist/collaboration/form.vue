<script setup lang="ts">
import { z } from "zod";

const props = defineProps<{
    collaboration: Collaboration;
    onSaveCollaboration: () => void;
}>();

const schema = z.object({
    name: z.string().min(1, "Name is required"),
    email: z.string().email("Invalid email address"),
    withLimit: z.boolean(),
    addLimit: z.number().optional(),
});

const formState = reactive({
    name: props.collaboration.name,
    email: props.collaboration.email,
    withLimit: props.collaboration.withLimit,
    addLimit: props.collaboration.addLimit,
});

function handleSubmit({ data }: { data: typeof formState }) {
    props.collaboration.name = data.name;
    props.collaboration.email = data.email;
    props.collaboration.withLimit = data.withLimit;
    props.collaboration.addLimit = data.addLimit;

    props.onSaveCollaboration();
}
</script>

<template>
    <UForm :state="formState" :schema="schema" @submit="handleSubmit" class="flex flex-col gap-4 shrink-0">
        <UFormField label="Name" name="name">
            <UInput
                size="md"
                variant="translucent"
                name="search-song"
                placeholder="Your collaborator's name"
                class="w-full"
                v-model="formState.name"
            />
        </UFormField>

        <UFormField label="Email" name="email">
            <UInput
                size="md"
                variant="translucent"
                name="search-song"
                placeholder="Your collaborator's email"
                class="w-full"
                v-model="formState.email"
            />
        </UFormField>

        <UFormField label="Limit" name="withLimit" description="Limit the number of songs your collaborator can add">
            <USwitch v-model="formState.withLimit" />
            <div>
                <UInputNumber
                    v-if="formState.withLimit"
                    size="md"
                    variant="translucent"
                    name="search-song"
                    placeholder="Number of songs allowed"
                    class="mt-1 w-full"
                    v-model="formState.addLimit"
                />
            </div>
        </UFormField>
        <UFormField>
            <UButton size="xl" block color="neutral" type="submit">Send invitation</UButton>
        </UFormField>
    </UForm>
</template>
