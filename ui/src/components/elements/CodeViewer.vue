<template>
  <div class="code-viewer">
    <div v-if="showCopy" class="code-header">
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
        v-if="allowFullscreen"
        size="tiny" 
        quaternary
        @click="$emit('toggle-fullscreen')"
        class="copy-button"
      >
        <template #icon>
          <n-icon><ExpandOutline /></n-icon>
        </template>
        Fullscreen
      </n-button>
    </div>
    <div class="code-content" ref="scrollContainer">
      <pre><code :class="`language-${language}`" v-html="highlightedCode"></code></pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { NButton, NIcon } from 'naive-ui';
import { CopyOutline, ExpandOutline } from '@vicons/ionicons5';
import { useClipboard } from '@/composables/useClipboard';
import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';
import sql from 'highlight.js/lib/languages/sql';
import typescript from 'highlight.js/lib/languages/typescript';

// Register only the languages we need
hljs.registerLanguage('json', json);
hljs.registerLanguage('sql', sql);
hljs.registerLanguage('typescript', typescript);
hljs.registerLanguage('text', () => ({ name: 'text', contains: [] }));

const props = withDefaults(defineProps<{
  code: string;
  language?: 'json' | 'sql' | 'typescript' | 'text' | 'html';
  showCopy?: boolean;
  allowFullscreen?: boolean;
}>(), {
  language: 'text',
  showCopy: true,
  allowFullscreen: false
});

defineEmits(['toggle-fullscreen']);

const { copy } = useClipboard();
const highlightedCode = ref('');
const scrollContainer = ref<HTMLElement>();

const updateHighlight = () => {
  if (!props.code) {
    highlightedCode.value = '';
    return;
  }

  try {
    const result = hljs.highlight(props.code, { language: props.language });
    highlightedCode.value = result.value;
  } catch (e) {
    // Fallback to escaped HTML if highlighting fails
    highlightedCode.value = escapeHtml(props.code);
  }
};

const escapeHtml = (text: string) => {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
};

const copyCode = () => {
  copy(props.code);
};

// Watch for code/language changes
watch(() => [props.code, props.language], updateHighlight, { immediate: true });
</script>

<style scoped>
.code-viewer {
  position: relative;
  background: #1e1e1e;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.1);
  height: 100%;
  display: flex;
  flex-direction: column;
}

.code-header {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  padding: 0.5rem;
  background: rgba(0, 0, 0, 0.2);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  flex-shrink: 0;
}

.code-content {
  flex: 1;
  overflow: auto;
  min-height: 0;
}

.code-content pre {
  margin: 0;
  padding: 1rem;
}

.code-content code {
  font-family: 'Fira Code', 'Courier New', Monaco, monospace;
  font-size: 0.875rem;
  line-height: 1.6;
  color: #d4d4d4;
}

/* Highlight.js VS Code Dark theme colors */
.code-content :deep(.hljs-keyword),
.code-content :deep(.hljs-built_in) {
  color: #569cd6;
}

.code-content :deep(.hljs-string) {
  color: #ce9178;
}

.code-content :deep(.hljs-number) {
  color: #b5cea8;
}

.code-content :deep(.hljs-comment) {
  color: #6a9955;
  font-style: italic;
}

.code-content :deep(.hljs-attr),
.code-content :deep(.hljs-attribute) {
  color: #9cdcfe;
}

.code-content :deep(.hljs-literal) {
  color: #569cd6;
}

.code-content :deep(.hljs-punctuation) {
  color: #d4d4d4;
}

.copy-button {
  opacity: 0.7;
  transition: opacity 0.2s ease;
}

.copy-button:hover {
  opacity: 1;
}
</style>
