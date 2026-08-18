<script setup lang="ts">
import type { ComboboxInputEmits, ComboboxInputProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { ChevronDown, Search } from '@lucide/vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxAnchor, ComboboxInput, ComboboxTrigger, useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<ComboboxInputProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<ComboboxInputEmits>();

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <ComboboxAnchor
        data-slot="combobox-anchor"
        class="border-input data-[placeholder]:text-muted-foreground focus-within:border-ring focus-within:ring-ring/50 aria-invalid:ring-destructive/20 dark:bg-input/30 flex h-9 w-full items-center gap-2 rounded-md border bg-transparent px-3 text-sm shadow-xs transition-[color,box-shadow] focus-within:ring-[3px]"
    >
        <Search class="text-muted-foreground size-4 shrink-0" />
        <ComboboxInput
            data-slot="combobox-input"
            v-bind="{ ...$attrs, ...forwarded }"
            :class="cn('placeholder:text-muted-foreground h-full flex-1 bg-transparent outline-none disabled:cursor-not-allowed disabled:opacity-50', props.class)"
        />
        <ComboboxTrigger data-slot="combobox-trigger">
            <ChevronDown class="text-muted-foreground size-4 shrink-0" />
        </ComboboxTrigger>
    </ComboboxAnchor>
</template>
