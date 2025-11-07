<script lang="ts" setup>
const props = withDefaults(
    defineProps<{
        url: string;
        size?: number;
        alt?: string;
    }>(),
    {}
);

const url = computed(() => {
    const size = props.size ?? 300;
    return props.url.replace("{w}", (size * 2).toString()).replace("{h}", (size * 2).toString());
});
</script>

<template>
    <img class="rounded-lg w-full" :src="url" :alt="props.alt ?? ''" />
</template>
