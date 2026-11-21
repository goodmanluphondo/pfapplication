<script setup lang="ts">
defineProps<{
    message: string;
}>();
</script>

<template>
    <div
        role="status"
        class="grid gap-1 rounded-[14px] bg-[#e6f4ea] px-4 py-3 text-center text-sm leading-5 font-medium text-[#137333] dark:bg-[rgba(129,201,149,.16)] dark:text-[#81c995]"
    >
        {{ message }}
    </div>
</template>
