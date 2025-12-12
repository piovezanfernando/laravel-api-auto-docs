<template>
  <Dialog v-model:visible="dialogVisible" modal header="Search API Routes" :style="{ width: '50vw' }">
    <div class="p-input-icon-left p-fluid mb-3">
      <i class="pi pi-search" />
      <InputText ref="searchInput" type="text" v-model="searchText" placeholder="Search routes by name or URI" />
    </div>
    <div class="results-panel">
      <div v-if="searchResults.length === 0 && searchText" class="text-center p-3 text-color-secondary">
        No results found.
      </div>
      <div v-for="route in searchResults" :key="route.id" 
           class="route-item flex align-items-center p-2 cursor-pointer"
           @click="selectRoute(route)">
        <span :class="['method-tag', route.methods[0]]">{{ route.methods[0] }}</span>
        <span class="route-uri ml-2">{{ route.uri }}</span>
      </div>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useApiStore } from '@/stores/api';
import { storeToRefs } from 'pinia';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import type { IRouteInfo, IGroupedRoutes } from '@/types';

const props = defineProps<{
  visible: boolean;
}>();

const emit = defineEmits(['update:visible']);

const apiStore = useApiStore();
const { rawRoutes } = storeToRefs(apiStore);

const searchInput = ref<HTMLInputElement | null>(null);
const searchText = ref('');

const dialogVisible = computed({
  get: () => props.visible,
  set: (value) => emit('update:visible', value)
});

watch(dialogVisible, (newValue) => {
  if (newValue) {
    setTimeout(() => {
      searchInput.value?.focus();
    }, 100); // Delay to ensure dialog is rendered
  } else {
    searchText.value = ''; // Clear search on close
  }
});

const searchResults = computed(() => {
  if (!searchText.value) {
    return [];
  }
  const search = searchText.value.trim().toLowerCase();
  const flatRoutes: IRouteInfo[] = [];
  rawRoutes.value.forEach((group: IGroupedRoutes) => {
    group.routes.forEach((route: IRouteInfo) => {
      if (group.group.toLowerCase().includes(search) || route.uri.toLowerCase().includes(search)) {
        flatRoutes.push(route);
      }
    });
  });
  return flatRoutes.slice(0, 15); // Limit results for performance
});

const selectRoute = (route: IRouteInfo) => {
  apiStore.fetchRouteDetails(route.id);
  dialogVisible.value = false;
};
</script>

<style scoped>
.results-panel {
  max-height: 50vh;
  overflow-y: auto;
}
.route-item:hover {
  background-color: var(--surface-hover);
}
</style>
