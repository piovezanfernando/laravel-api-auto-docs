<template>
  <div class="h-full flex flex-column bg-surface-card">
    <div class="p-3 flex-shrink-0">
        <span class="p-input-icon-left w-full">
            <i class="pi pi-search" />
            <InputText type="text" v-model="searchText" placeholder="Search routes" class="w-full" />
        </span>
    </div>

    <div class="flex-grow overflow-auto no-scrollbar">
      <div v-if="isLoadingRoutes" class="flex justify-content-center align-items-center h-full">
        <ProgressSpinner />
      </div>
      <div v-else-if="filteredAndSortedRoutes.length === 0" class="text-center text-color-secondary mt-5">
        No routes found.
      </div>
      <Accordion :value="0">
        <AccordionPanel v-for="group in filteredAndSortedRoutes" :key="group.group" :value="group.group">
          <AccordionHeader>{{group.group}}</AccordionHeader>
          <AccordionContent>
            <div class="p-0">
              <div v-for="route in group.routes" :key="route.id"
                   :class="['route-item flex align-items-center p-2 cursor-pointer w-full',
                            {'bg-primary-reverse text-primary font-semibold': route.id === selectedRouteId}]"
                   @click="selectRoute(route.id)">
                <span :class="['method-tag', route.methods[0]]">{{ route.methods[0] }}</span>
                <span class="route-uri ml-2">{{ route.uri }}</span>
              </div>
            </div>
          </AccordionContent>
        </AccordionPanel>
      </Accordion>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useApiStore } from '@/stores/api';
import { storeToRefs } from 'pinia';

import Accordion from 'primevue/accordion';
import AccordionPanel from 'primevue/accordionpanel';
import AccordionHeader from 'primevue/accordionheader';
import AccordionContent from 'primevue/accordioncontent';
import ProgressSpinner from 'primevue/progressspinner';
import InputText from 'primevue/inputtext';

const apiStore = useApiStore();
const { filteredAndSortedRoutes, isLoadingRoutes, selectedRouteId, searchText } = storeToRefs(apiStore);

onMounted(() => {
  apiStore.fetchRoutes();
});

const selectRoute = (id: string) => {
  apiStore.fetchRouteDetails(id);
};
</script>

<style>
.sidebar-accordion .p-accordion-header-link {
  padding: 0.75rem 1rem !important;
}
.sidebar-accordion .p-accordion-toggle-icon {
  transition: transform 0.2s;
}
.sidebar-accordion .p-accordion-header:not(.p-disabled).p-accordion-header-active .p-accordion-toggle-icon {
  transform: rotate(-180deg);
}
.sidebar-accordion .p-accordion-content {
  padding: 0.25rem 0 !important;
}

.route-item {
  border-radius: 4px;
  transition: background-color 0.2s;
  padding-left: 1rem !important;
}
.route-item:hover {
  background-color: var(--surface-hover);
}
.route-uri {
  font-size: 0.875rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  min-width: 0;
}
</style>
