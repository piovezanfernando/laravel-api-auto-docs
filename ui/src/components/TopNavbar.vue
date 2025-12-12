<template>
  <div class="bg-gray-800 text-white p-3 flex align-items-center justify-content-between">
    <div class="flex align-items-center">
      <span class="text-xl font-bold mr-5">API Auto-Docs</span>
    </div>

    <div>
      <Button
        icon="pi pi-search"
        label="Search"
        class="p-button-sm p-button-secondary mr-3"
        @click="$emit('open-search')"
      />
      <Button
        icon="pi pi-shield"
        label="Auth"
        class="p-button-sm p-button-secondary mr-3"
        @click="showAuthDialog = true"
      />
      <Button 
        icon="pi pi-cog" 
        class="p-button-rounded p-button-text p-button-sm mr-3" 
        @click="toggleSettings"
      />
      <Button icon="pi pi-question-circle" class="p-button-rounded p-button-text p-button-sm" />
    </div>
  </div>

  <!-- Global Auth Dialog -->
  <Dialog v-model:visible="showAuthDialog" modal header="Global Authentication" :style="{ width: '30vw' }">
    <div class="p-fluid">
      <label for="authToken" class="mb-2">Bearer Token</label>
      <InputText id="authToken" v-model="globalAuthInput" type="text" placeholder="Enter Bearer Token (e.g., Bearer YOUR_TOKEN)" />
    </div>
    <template #footer>
      <Button label="Clear" icon="pi pi-trash" class="p-button-text p-button-danger" @click="clearAuthToken" />
      <Button label="Save" icon="pi pi-check" @click="saveAuthToken" />
    </template>
  </Dialog>
  
  <!-- Settings Overlay Panel -->
  <OverlayPanel ref="op">
    <div class="p-4" style="width: 250px;">
      <h4 class="text-lg font-bold mb-2">API Host URL</h4>
      <div class="p-fluid mb-4">
        <InputText type="text" v-model="apiStore.apiHost" placeholder="e.g., http://localhost:8000" />
      </div>

      <h4 class="text-lg font-bold mb-2">Sort By</h4>
      <div class="flex flex-column gap-2">
        <div class="flex align-items-center">
            <RadioButton v-model="apiStore.filters.sortBy" inputId="sortAsc" name="sort" value="asc" @change="apiStore.updateFilter('sortBy', 'asc')" />
            <label for="sortAsc" class="ml-2"> Ascending </label>
        </div>
        <div class="flex align-items-center">
            <RadioButton v-model="apiStore.filters.sortBy" inputId="sortDesc" name="sort" value="desc" @change="apiStore.updateFilter('sortBy', 'desc')" />
            <label for="sortDesc" class="ml-2"> Descending </label>
        </div>
      </div>
      
      <h4 class="text-lg font-bold mt-4 mb-2">Method Filters</h4>
      <div class="flex flex-column gap-2">
        <div v-for="item in methodFilterItems" :key="item.name" class="flex align-items-center">
          <InputSwitch :inputId="item.name" v-model="item.active" @change="apiStore.updateFilter(item.name, item.active)" />
          <label :for="item.name" class="ml-2"> {{ item.name }} </label>
        </div>
      </div>
    </div>
  </OverlayPanel>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import OverlayPanel from 'primevue/overlaypanel';
import RadioButton from 'primevue/radiobutton';
import InputSwitch from 'primevue/inputswitch';
import { useApiStore } from '@/stores/api';
import { storeToRefs } from 'pinia';
import type { FilterState } from '@/types';

const emit = defineEmits(['open-search']);
const apiStore = useApiStore();
const { globalAuthToken } = storeToRefs(apiStore);

const showAuthDialog = ref(false);
const globalAuthInput = ref(globalAuthToken.value || '');

const op = ref();
const toggleSettings = (event: any) => {
    op.value.toggle(event);
};

const saveAuthToken = () => {
  apiStore.setGlobalAuthToken(globalAuthInput.value);
  showAuthDialog.value = false;
};

const clearAuthToken = () => {
  globalAuthInput.value = '';
  apiStore.setGlobalAuthToken(null);
  showAuthDialog.value = false;
};

// Sync globalAuthInput with store's globalAuthToken when dialog opens
onMounted(() => {
  globalAuthInput.value = globalAuthToken.value || '';
});

// Watch globalAuthToken in store to update local input if cleared from elsewhere
watch(globalAuthToken, (newValue: string | null) => {
  if (newValue === null) {
    globalAuthInput.value = '';
  }
});

const methodFilterItems = computed(() => {
  return (Object.keys(apiStore.filters.showMethod) as Array<keyof FilterState['showMethod']>).map(name => ({
    name: name,
    active: apiStore.filters.showMethod[name]
  }));
});
</script>

<style scoped>
/* Adjustments for PrimeVue components */
.p-button.p-button-text.p-button-sm {
  width: 2rem;
  height: 2rem;
  padding: 0;
}
</style>
