<template>
  <div class="key-value-editor">
    <div v-for="(item, index) in modelValue" :key="index" class="kv-row">
      <n-input
        placeholder="Key"
        :value="item.key"
        :readonly="keyReadonly"
        @update:value="updateItem(index, 'key', $event || '')"
        size="small"
      />
      <n-input
        placeholder="Value"
        :value="item.value"
        @update:value="updateItem(index, 'value', $event || '')"
        size="small"
      />
      <n-button
        quaternary
        type="error"
        @click="removeItem(index)"
        :disabled="keyReadonly"
        size="small"
      >
        <template #icon>
          <n-icon><TrashOutline /></n-icon>
        </template>
      </n-button>
    </div>
    <n-button
      text
      size="small"
      @click="addItem"
      :disabled="keyReadonly"
      class="add-button"
    >
      <template #icon>
        <n-icon><AddOutline /></n-icon>
      </template>
      Add Row
    </n-button>
  </div>
</template>

<script setup lang="ts">
import { NInput, NButton, NIcon } from 'naive-ui';
import { TrashOutline, AddOutline } from '@vicons/ionicons5';

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
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.kv-row {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 0.5rem;
  align-items: center;
}

.add-button {
  margin-top: 0.5rem;
}
</style>
