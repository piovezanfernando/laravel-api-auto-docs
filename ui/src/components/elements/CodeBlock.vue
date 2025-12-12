<template>
  <pre><code class="hljs" :class="language" v-html="highlightedCode"></code></pre>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import hljs from 'highlight.js/lib/core';
import json from 'highlight.js/lib/languages/json';
import 'highlight.js/styles/atom-one-dark.css';

// Register the language
hljs.registerLanguage('json', json);

interface Props {
  code: string;
  language: string;
}

const props = withDefaults(defineProps<Props>(), {
  code: '',
  language: 'json',
});

const highlightedCode = computed(() => {
  if (!props.code) {
    return '';
  }
  try {
    // Ensure the code is formatted nicely before highlighting
    const formattedCode = JSON.stringify(JSON.parse(props.code), null, 2);
    return hljs.highlight(formattedCode, { language: props.language }).value;
  } catch (e) {
    // If it's not valid JSON, return the plain text
    return props.code;
  }
});
</script>

<style>
.hljs {
  border-radius: 4px;
  padding: 1em !important;
  background: #282c34; /* atom-one-dark background */
}
</style>
