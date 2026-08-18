<script setup lang="ts">
import { computed } from 'vue';
import {
    Combobox,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxInput,
    ComboboxItem,
} from '@/components/ui/combobox';

export type SearchableComboBoxOption = {
    value: string | number;
    label: string;
};

const props = defineProps<{
    modelValue: SearchableComboBoxOption | null;
    options: SearchableComboBoxOption[];
    placeholder?: string;
    name?: string;
    id?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: SearchableComboBoxOption | null];
}>();

/**
 * The underlying Combobox primitive needs a plain, matchable value for its
 * own v-model - item matching/filtering, and the hidden input it renders
 * for native <form> submission, which can't serialize a whole object into
 * a form field. This is the only place that boundary is crossed; callers
 * only ever see the full option object.
 */
const primitiveValue = computed(() =>
    props.modelValue ? String(props.modelValue.value) : '',
);

function handleUpdate(raw: unknown) {
    if (raw === null || raw === undefined || raw === '') {
        emit('update:modelValue', null);

        return;
    }

    const selected =
        props.options.find((option) => String(option.value) === String(raw)) ??
        null;

    emit('update:modelValue', selected);
}
</script>

<template>
    <Combobox
        :model-value="primitiveValue"
        :name="name"
        @update:model-value="handleUpdate"
    >
        <ComboboxInput
            :id="id"
            :display-value="() => modelValue?.label ?? ''"
            :placeholder="placeholder"
        />
        <ComboboxContent>
            <ComboboxEmpty />
            <ComboboxItem
                v-for="option in options"
                :key="option.value"
                :value="String(option.value)"
            >
                {{ option.label }}
            </ComboboxItem>
        </ComboboxContent>
    </Combobox>
</template>
