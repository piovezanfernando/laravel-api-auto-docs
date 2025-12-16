<template>
  <n-modal
    v-model:show="dialogVisible"
    preset="card"
    title="Search API Routes"
    style="width: 600px; max-width: 90vw;"
    :bordered="false"
  >
    <n-input
      ref="searchInputRef"
      v-model:value="searchText"
      placeholder="Search routes by name or URI (Cmd/Ctrl + K)"
      clearable
      size="large"
    >
      <template #prefix>
        <n-icon><SearchOutline /></n-icon>
      </template>
    </n-input>
    
    <div class="results-panel">
      <div v-if="searchResults.length === 0 && searchText" class="empty-results">
        No results found.
      </div>
      <div 
        v-for="result in searchResults" 
        :key="result.route.id" 
        class="route-item"
        @click="selectRoute(result)"
      >
        <span :class="['method-tag', result.route.methods[0]]">{{ result.route.methods[0] }}</span>
        <span class="route-uri">{{ result.route.uri }}</span>
      </div>
    </div>
  </n-modal>
</template>

<script setup lang="ts">
import { ref, watch, computed, nextTick, inject } from 'vue';
import { useApiStore } from '@/stores/api';
import { storeToRefs } from 'pinia';
import { NModal, NInput, NIcon } from 'naive-ui';
import { SearchOutline } from '@vicons/ionicons5';
import type { IRouteInfo, IGroupedRoutes } from '@/types';

const props = defineProps<{
  visible: boolean;
}>();

const emit = defineEmits(['update:visible']);

const apiStore = useApiStore();
const { rawRoutes } = storeToRefs(apiStore);

// Inject sidebar ref to control accordion
const sidebarRef = inject<any>('sidebarRef', null);

const searchInputRef = ref();
const searchText = ref('');

const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
});

watch(dialogVisible, async (newValue) => {
  if (newValue) {
    await nextTick();
    searchInputRef.value?.focus();
  } else {
    searchText.value = ''; // Clear search on close
  }
});

const searchResults = computed(() => {
  if (!searchText.value) {
    return [];
  }
  const search = searchText.value.trim().toLowerCase();
  const results: Array<{ route: IRouteInfo; group: string }> = [];
  
  rawRoutes.value.forEach((group: IGroupedRoutes) => {
    group.routes.forEach((route: IRouteInfo) => {
      if (group.group.toLowerCase().includes(search) || route.uri.toLowerCase().includes(search)) {
        results.push({ route, group: group.group });
      }
    });
  });
  return results.slice(0, 15); // Limit results for performance
});

const selectRoute = (result: { route: IRouteInfo; group: string }) => {
  apiStore.fetchRouteDetails(result.route.id);
  
  // Expand the accordion group containing this route
  if (sidebarRef?.value?.expandGroup) {
    sidebarRef.value.expandGroup(result.group);
  }
  
  dialogVisible.value = false;
};

// Global keyboard shortcut: Cmd/Ctrl + K
if (typeof window !== 'undefined') {
  const handleKeyDown = (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
      e.preventDefault();
      dialogVisible.value = true;
    }
    if (e.key === 'Escape' && dialogVisible.value) {
      dialogVisible.value = false;
    }
  };
  window.addEventListener('keydown', handleKeyDown);
}
</script>

<style scoped>
.results-panel {
  max-height: 50vh;
  overflow-y: auto;
  margin-top: 1rem;
}

.empty-results {
  text-align: center;
  padding: 2rem;
  color: var(--n-text-color-disabled);
}

.route-item {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  cursor: pointer;
  border-radius: 6px;
  transition: background-color 0.2s ease;
  gap: 0.75rem;
}

.route-item:hover {
  background-color: rgba(255, 255, 255, 0.05);
}

.route-uri {
  font-size: 0.875rem;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
