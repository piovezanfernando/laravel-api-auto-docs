<template>
  <div class="navbar">
    <div class="navbar-left">
      <span class="navbar-title">API Auto-Docs</span>
    </div>

    <div class="navbar-right">
      <n-button
        size="small"
        secondary
        @click="$emit('open-search')"
      >
        <template #icon>
          <n-icon><SearchOutline /></n-icon>
        </template>
        Search
      </n-button>
      
      <n-button
        size="small"
        secondary
        @click="showAuthDialog = true"
        class="ml-2"
      >
        <template #icon>
          <n-icon><ShieldOutline /></n-icon>
        </template>
        Auth
      </n-button>
      
      <n-button
        size="small"
        quaternary
        circle
        @click="toggleSettings"
        class="ml-2"
      >
        <template #icon>
          <n-icon><SettingsOutline /></n-icon>
        </template>
      </n-button>
      
      <n-button
        size="small"
        quaternary
        circle
        @click="$emit('toggle-theme')"
        class="ml-2"
      >
        <template #icon>
          <n-icon><component :is="isDark ? SunnyOutline : MoonOutline" /></n-icon>
        </template>
      </n-button>
      
      <n-button
        size="small"
        quaternary
        circle
        tag="a"
        href="https://github.com/piovezanfernando/laravel-api-auto-docs"
        target="_blank"
        class="ml-2"
      >
        <template #icon>
          <n-icon><HelpCircleOutline /></n-icon>
        </template>
      </n-button>
    </div>
  </div>

  <!-- Global Auth Dialog -->
  <n-modal
    v-model:show="showAuthDialog"
    preset="dialog"
    title="Global Authentication"
    positive-text="Save"
    negative-text="Clear"
    @positive-click="saveAuthToken"
    @negative-click="clearAuthToken"
  >
    <div class="dialog-content">
      <label class="label">Bearer Token</label>
      <n-input
        v-model:value="globalAuthInput"
        type="text"
        placeholder="Enter Bearer Token (e.g., Bearer YOUR_TOKEN)"
      />
    </div>
  </n-modal>
  
  <!-- Settings Popover -->
  <n-popover
    ref="settingsPopover"
    trigger="manual"
    :show="showSettings"
    @clickoutside="showSettings = false"
    placement="bottom-end"
    style="width: 280px;"
  >
    <template #trigger>
      <div ref="settingsTrigger"></div>
    </template>
    
    <div class="settings-panel">
      <h4 class="settings-title">API Host URL</h4>
      <n-input 
        v-model:value="apiStore.apiHost" 
        placeholder="e.g., http://localhost:8000"
        size="small"
      />

      <h4 class="settings-title">Sort By</h4>
      <n-radio-group v-model:value="apiStore.filters.sortBy" @update:value="(val) => apiStore.updateFilter('sortBy', val)">
        <n-space vertical>
          <n-radio value="asc">Ascending</n-radio>
          <n-radio value="desc">Descending</n-radio>
        </n-space>
      </n-radio-group>
      
      <h4 class="settings-title">Method Filters</h4>
      <n-space vertical>
        <div v-for="(value, key) in apiStore.filters.showMethod" :key="key" class="filter-item">
          <n-switch 
            :value="value" 
            @update:value="(val) => apiStore.updateFilter(key, val)"
            size="small"
          />
          <span class="filter-label">{{ key }}</span>
        </div>
      </n-space>
    </div>
  </n-popover>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { NButton, NModal, NInput, NPopover, NRadioGroup, NRadio, NSpace, NSwitch, NIcon } from 'naive-ui';
import { SearchOutline, ShieldOutline, SettingsOutline, HelpCircleOutline, MoonOutline, SunnyOutline } from '@vicons/ionicons5';
import { useApiStore } from '@/stores/api';
import { storeToRefs } from 'pinia';

defineProps<{
  isDark: boolean;
}>();

const emit = defineEmits(['open-search', 'toggle-theme']);
const apiStore = useApiStore();
const { globalAuthToken } = storeToRefs(apiStore);

const showAuthDialog = ref(false);
const globalAuthInput = ref(globalAuthToken.value || '');

const showSettings = ref(false);
const settingsTrigger = ref<HTMLElement>();

const toggleSettings = () => {
  showSettings.value = !showSettings.value;
};

const saveAuthToken = () => {
  apiStore.setGlobalAuthToken(globalAuthInput.value);
  showAuthDialog.value = false;
  return true; // for n-modal positive-click
};

const clearAuthToken = () => {
  globalAuthInput.value = '';
  apiStore.setGlobalAuthToken(null);
  return true; // for n-modal negative-click  
};

// Watch globalAuthToken in store to update local input if cleared elsewhere
watch(globalAuthToken, (newValue: string | null) => {
  if (newValue === null) {
    globalAuthInput.value = '';
  } else {
    globalAuthInput.value = newValue;
  }
});
</script>

<style scoped>
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: var(--n-color);
  border-bottom: 1px solid var(--n-border-color);
  height: 4rem;
}

.navbar-left {
  display: flex;
  align-items: center;
}

.navbar-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--n-text-color);
}

.navbar-right {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.ml-2 {
  margin-left: 0.5rem;
}

.dialog-content {
  padding: 1rem 0;
}

.label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--n-text-color);
}

.settings-panel {
  padding: 0.5rem;
}

.settings-title {
  font-size: 0.875rem;
  font-weight: 600;
  margin: 1rem 0 0.5rem 0;
  color: var(--n-text-color);
}

.settings-title:first-child {
  margin-top: 0;
}

.filter-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-label {
  font-size: 0.875rem;
  color: var(--n-text-color);
}
</style>
