<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        href?: string;
        iconOnly?: boolean;
        icon?: string;
        actionFunction?: () => void;
    }>(),
    {
        iconOnly: false,
    }
);
</script>
<template>
    <component
        :is="href ? 'NuxtLink' : 'span'"
        :to="href"
        v-on:click="actionFunction"
        class="p-2 bg-white/40 border border-white text-white flex items-center"
        :class="{ 'px-4': icon && !iconOnly, 'gap-1': icon }"
    >
        <Icon v-if="icon" :name="icon" />
        <span v-if="!iconOnly" class="font-medium [text-box:trim-both_cap_alphabetic]">
            <slot></slot>
        </span>
    </component>
</template>
