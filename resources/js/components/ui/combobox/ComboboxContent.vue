<script setup lang="ts">
import type { ComboboxContentEmits, ComboboxContentProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxContent, ComboboxPortal, ComboboxViewport, useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<ComboboxContentProps & { class?: HTMLAttributes['class'] }>(),
    {
        position: 'popper',
    },
);
const emits = defineEmits<ComboboxContentEmits>();

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
    <ComboboxPortal>
        <ComboboxContent
            data-slot="combobox-content"
            v-bind="{ ...$attrs, ...forwarded }"
            :class="
                cn(
                    'bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 relative z-50 max-h-72 min-w-[8rem] overflow-x-hidden overflow-y-auto rounded-md border shadow-md',
                    position === 'popper' &&
                        'data-[side=bottom]:translate-y-1 data-[side=left]:-translate-x-1 data-[side=right]:translate-x-1 data-[side=top]:-translate-y-1',
                    props.class,
                )
            "
        >
            <ComboboxViewport
                :class="cn('p-1', position === 'popper' && 'w-full min-w-[var(--reka-combobox-trigger-width)] scroll-my-1')"
            >
                <slot />
            </ComboboxViewport>
        </ComboboxContent>
    </ComboboxPortal>
</template>
