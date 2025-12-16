<template>
  <div class="h-full flex flex-column">
    <div class="flex-grow overflow-auto no-scrollbar" ref="scrollContainer">
      <div v-if="isLoadingRoutes" class="flex justify-content-center align-items-center h-full">
        <n-spin size="medium" />
      </div>
      <div v-else-if="filteredAndSortedRoutes.length === 0" class="text-center p-5" style="color: var(--n-text-color-disabled);">
        No routes found.
      </div>
      <n-collapse v-else v-model:expanded-names="expandedGroupNames">
        <n-collapse-item 
          v-for="group in filteredAndSortedRoutes" 
          :key="group.group" 
          :name="group.group"
          :title="group.group"
        >
          <div class="routes-list">
            <div 
              v-for="route in group.routes" 
              :key="route.id"
              :ref="(el) => { if (route.id === selectedRouteId && el) selectedRouteRef = el as HTMLElement }"
              :class="['route-item', { 'route-item-active': route.id === selectedRouteId }]"
              @click="selectRoute(route.id)"
            >
              <span :class="['method-tag', route.methods[0]]">{{ route.methods[0] }}</span>
              <span class="route-uri ml-2">{{ route.uri }}</span>
            </div>
          </div>
        </n-collapse-item>
      </n-collapse>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue';
import { NCollapse, NCollapseItem, NSpin } from 'naive-ui';
import { useApiStore } from '@/stores/api';
import { storeToRefs } from 'pinia';

const apiStore = useApiStore();
const { filteredAndSortedRoutes, isLoadingRoutes, selectedRouteId } = storeToRefs(apiStore);

const expandedGroupNames = ref<string[]>([]); // Start with all closed
const scrollContainer = ref<HTMLElement>();
const selectedRouteRef = ref<HTMLElement>();

onMounted(() => {
  apiStore.fetchRoutes();
});

const selectRoute = (id: string) => {
  apiStore.fetchRouteDetails(id);
};

// Watch for selectedRouteId changes and scroll to it
watch(selectedRouteId, async (newId) => {
  if (newId && selectedRouteRef.value && scrollContainer.value) {
    await nextTick();
    // Wait a bit for collapse animation
    setTimeout(() => {
      if (selectedRouteRef.value && scrollContainer.value) {
        selectedRouteRef.value.scrollIntoView({ 
          behavior: 'smooth', 
          block: 'center' 
        });
      }
    }, 300);
  }
});

// Expose method to programmatically expand a group (for search functionality)
defineExpose({ 
  expandGroup: (groupName: string) => {
    if (!expandedGroupNames.value.includes(groupName)) {
      expandedGroupNames.value.push(groupName);
    }
  }
});
</script>

<style scoped>
.flex {
  display: flex;
}

.flex-column {
  flex-direction: column;
}

.flex-grow {
  flex: 1;
}

.h-full {
  height: 100%;
}

.overflow-auto {
  overflow: auto;
}

.justify-content-center {
  justify-content: center;
}

.align-items-center {
  align-items: center;
}

.text-center {
  text-align: center;
}

.p-5 {
  padding: 1.25rem;
}

.ml-2 {
  margin-left: 0.5rem;
}

.routes-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.route-item {
  display: flex;
  align-items: center;
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.route-item:hover {
  background-color: rgba(255, 255, 255, 0.05);
}

.route-item-active {
  background-color: rgba(99, 102, 241, 0.2);
  border-left: 3px solid #6366f1;
  padding-left: calc(0.75rem - 3px);
}

.route-uri {
  font-size: 0.875rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
  min-width: 0;
}
</style>
