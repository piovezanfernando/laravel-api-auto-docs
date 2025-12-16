<template>
  <div class="rules-tree-container">
    <n-tree
      :data="treeData"
      :default-expanded-keys="defaultExpandedKeys"
      block-line
      class="rules-tree"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { NTree, type TreeOption } from 'naive-ui';
import { ruleTranslations } from '@/constants';

interface Props {
  rules: { [key: string]: string[] };
  fieldInfo: { [key: string]: { description: string; example: string } };
}

const props = defineProps<Props>();

const translateRule = (rule: string) => {
  // Handle rules with parameters, e.g., max:255
  if (rule.includes(':')) {
    const [ruleName, params] = rule.split(':');
    const translated = ruleTranslations[ruleName] || ruleName;
    return `${translated}: ${params}`;
  }
  return ruleTranslations[rule] || rule;
};

function buildTree(rules: { parameter: string; rules: string[]; description: string }[]): TreeOption[] {
  const root: { [key: string]: any } = {};

  rules.forEach(rule => {
    const parts = rule.parameter.replace(/\*/g, '').split('.');
    let current = root;
    parts.forEach((part, index) => {
      if (!current[part]) {
        current[part] = { children: {} };
      }
      if (index === parts.length - 1) {
        current[part].data = rule;
      }
      current = current[part].children;
    });
  });

  function toTreeNodes(obj: { [key: string]: any }, parentKey = ''): TreeOption[] {
    return Object.keys(obj).map(key => {
      const node = obj[key];
      const fullKey = parentKey ? `${parentKey}.${key}` : key;
      const nodeChildren = node.children ? toTreeNodes(node.children, fullKey) : [];
      const dataChildren: TreeOption[] = node.data ? [
        ...node.data.rules.map((r: string, idx: number) => ({
          key: `${fullKey}-rule-${idx}`,
          label: `🏷️ ${translateRule(r)}`,
          isLeaf: true
        })),
        ...(node.data.description ? [{
          key: `${fullKey}-desc`,
          label: `ℹ️ ${node.data.description}`,
          isLeaf: true
        }] : [])
      ] : [];
      
      return {
        key: fullKey,
        label: `📁 ${key}`,
        children: [...nodeChildren, ...dataChildren].length > 0 
          ? [...nodeChildren, ...dataChildren] 
          : undefined
      };
    });
  }

  return toTreeNodes(root);
}

const treeData = computed(() => {
  if (!props.rules) return [];
  const flatRules = Object.keys(props.rules).map(key => {
    const rawRules = props.rules[key];
    const flattenedRules = rawRules.flatMap(rule => typeof rule === 'string' ? rule.split('|') : [rule]);
    return {
      parameter: key,
      rules: flattenedRules,
      description: props.fieldInfo?.[key]?.description || '',
    };
  });
  return buildTree(flatRules);
});

const defaultExpandedKeys = ref<string[]>([]);
</script>

<style scoped>
.rules-tree-container {
  width: 100%;
  height: 100%;
  overflow-y: auto;
  background: #1e1e1e;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.5rem;
}

.rules-tree {
  min-height: 0;
}
</style>
