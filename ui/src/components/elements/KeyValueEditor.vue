<template>
  <div class="key-value-editor">
    <div v-for="(item, index) in modelValue" :key="index" class="flex gap-2 mb-3">
      <InputText
        placeholder="Key"
        :model-value="item.key"
        :readonly="keyReadonly"
        @update:modelValue="updateItem(index, 'key', $event || '')"
        class="flex-1"
      />
      <InputText
        placeholder="Value"
        :model-value="item.value"
        @update:modelValue="updateItem(index, 'value', $event || '')"
        class="flex-1"
      />
      <Button
        icon="pi pi-trash"
        severity="danger"
        outlined
        @click="removeItem(index)"
        :disabled="keyReadonly"
      />
    </div>
    <Button
      label="Add Row"
      icon="pi pi-plus"
      size="small"
      text
      @click="addItem"
      :disabled="keyReadonly"
    />
  </div>
</template>

<script setup lang="ts">
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';

interface KeyValuePair {
  key: string;
  value: string;
}

const props = defineProps<{
  modelValue: KeyValuePair[];
  keyReadonly?: boolean;
}>();

const emit = defineEmits(['update:modelValue']);

const updateItem = (index: number, field: 'key' | 'value', value: string) => {
  const newItems = [...props.modelValue];
  newItems[index] = { ...newItems[index], [field]: value };
  emit('update:modelValue', newItems);
};

const addItem = () => {
  const newItems = [...props.modelValue, { key: '', value: '' }];
  emit('update:modelValue', newItems);
};

const removeItem = (index: number) => {
  const newItems = props.modelValue.filter((_, i) => i !== index);
  emit('update:modelValue', newItems);
};
</script>

<style scoped>
.key-value-editor {
  padding: 0.5rem;
}
</style>
