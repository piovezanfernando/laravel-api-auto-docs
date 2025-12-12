<template>
  <div :style="{ height: height, width: width }" class="monaco-editor-container">
    <MonacoEditor
      v-model:value="code"
      :language="lang"
      :options="editorOptions"
      :height="height"
      :width="width"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import MonacoEditor from '@guolao/vue-monaco-editor';

interface Props {
  modelValue: string;
  lang: string;
  readOnly: boolean;
  height: string;
  width: string;
}

const props = defineProps<Props>();

const emit = defineEmits(['update:modelValue']);

const code = ref(props.modelValue);

const editorOptions = computed(() => ({
  readOnly: props.readOnly,
  minimap: { enabled: false },
  fontSize: 14,
  wordWrap: 'on',
  automaticLayout: true,
  theme: 'vs-dark',
  scrollBeyondLastLine: false,
  renderWhitespace: 'selection',
}));

watch(
  () => props.modelValue,
  (newValue) => {
    code.value = newValue;
  }
);

watch(code, (newCode) => {
  emit('update:modelValue', newCode);
});
</script>

<style scoped>
.monaco-editor-container {
  border: 1px solid var(--surface-border);
  border-radius: var(--border-radius);
  overflow: hidden;
}
</style>
