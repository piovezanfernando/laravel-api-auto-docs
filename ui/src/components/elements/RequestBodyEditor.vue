<template>
  <div class="request-body-editor">
    <div class="editor-toolbar">
      <n-button 
        size="tiny" 
        quaternary
        @click="copyCode"
        class="copy-button"
      >
        <template #icon>
          <n-icon><CopyOutline /></n-icon>
        </template>
        Copy
      </n-button>
      <n-button 
        size="tiny" 
        quaternary
        @click="formatJson"
        class="format-button"
      >
        <template #icon>
          <n-icon><ColorWandOutline /></n-icon>
        </template>
        Format
      </n-button>
      <n-button 
        size="tiny" 
        quaternary
        @click="$emit('toggle-fullscreen')"
        class="fullscreen-button"
      >
        <template #icon>
          <n-icon><ExpandOutline /></n-icon>
        </template>
        Fullscreen
      </n-button>
    </div>
    <div class="editor-wrapper">
      <textarea
        :value="modelValue"
        @input="updateValue"
        class="json-editor"
        placeholder='{ "key": "value" }'
        spellcheck="false"
      />
      <div class="syntax-highlight" :style="{ whiteSpace: 'pre' }" v-html="highlightedCode"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { NButton, NIcon } from 'naive-ui';
import { CopyOutline, ColorWandOutline, ExpandOutline } from '@vicons/ionicons5';
import { useClipboard } from '@/composables/useClipboard';
import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';

// Register JSON language
hljs.registerLanguage('json', json);

const props = defineProps<{
  modelValue: string;
}>();

const emit = defineEmits(['update:modelValue', 'toggle-fullscreen']);

const { copy } = useClipboard();

const highlightedCode = ref('');

const updateHighlight = () => {
  if (!props.modelValue) {
    highlightedCode.value = '';
    return;
  }

  try {
    const result = hljs.highlight(props.modelValue, { language: 'json' });
    highlightedCode.value = result.value;
  } catch (e) {
    highlightedCode.value = escapeHtml(props.modelValue);
  }
};

const escapeHtml = (text: string) => {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
};

const copyCode = () => {
  copy(props.modelValue);
};

const formatJson = () => {
  try {
    const parsed = JSON.parse(props.modelValue);
    emit('update:modelValue', JSON.stringify(parsed, null, 2));
  } catch (e) {
    // Invalid JSON, do nothing
  }
};

const updateValue = (e: Event) => {
  const target = e.target as HTMLTextAreaElement;
  emit('update:modelValue', target.value);
};

// Watch for value changes
watch(() => props.modelValue, updateHighlight, { immediate: true });
</script>

<style scoped>
.request-body-editor {
  position: relative;
  background: #1e1e1e;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.editor-toolbar {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 0.5rem;
  background: rgba(0, 0, 0, 0.2);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  flex-shrink: 0;
}

.copy-button,
.format-button,
.fullscreen-button {
  opacity: 0.7;
  transition: opacity 0.2s ease;
}

.copy-button:hover,
.format-button:hover,
.fullscreen-button:hover {
  opacity: 1;
}

.editor-wrapper {
  position: relative;
  flex: 1;
  overflow: hidden;
  min-height: 0;
}

.json-editor {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: transparent;
  color: transparent;
  caret-color: #d4d4d4;
  border: none;
  outline: none;
  font-family: 'Fira Code', 'Courier New', Monaco, monospace;
  font-size: 0.875rem;
  line-height: 1.6;
  padding: 1rem;
  resize: none;
  overflow: auto;
  z-index: 2;
  tab-size: 2;
  white-space: pre;
}

.syntax-highlight {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  padding: 1rem;
  overflow: auto;
  pointer-events: none;
  z-index: 1;
  font-family: 'Fira Code', 'Courier New', Monaco, monospace;
  font-size: 0.875rem;
  line-height: 1.6;
  color: #d4d4d4;
}

/* Highlight.js VS Code Dark theme colors */
.syntax-highlight :deep(.hljs-keyword),
.syntax-highlight :deep(.hljs-built_in) {
  color: #569cd6;
}

.syntax-highlight :deep(.hljs-string) {
  color: #ce9178;
}

.syntax-highlight :deep(.hljs-number) {
  color: #b5cea8;
}

.syntax-highlight :deep(.hljs-comment) {
  color: #6a9955;
  font-style: italic;
}

.syntax-highlight :deep(.hljs-attr),
.syntax-highlight :deep(.hljs-attribute) {
  color: #9cdcfe;
}

.syntax-highlight :deep(.hljs-literal) {
  color: #569cd6;
}

.syntax-highlight :deep(.hljs-punctuation) {
  color: #d4d4d4;
}
</style>
