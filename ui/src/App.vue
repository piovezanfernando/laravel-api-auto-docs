<template>
  <n-config-provider :theme="currentTheme" :theme-overrides="currentThemeOverrides">
    <n-message-provider>
      <n-notification-provider>
        <n-global-style />
        <div class="h-screen flex flex-column">
          <TopNavbar @open-search="isSearchVisible = true" @toggle-theme="toggleTheme" :is-dark="isDark" />
          <n-split 
            direction="horizontal" 
            :default-size="0.2"
            :min="0.1"
            :max="0.4"
            style="height: calc(100vh - 4rem)"
          >
            <template #1>
              <SidebarMenu ref="sidebarRef" class="w-full h-full"/>
            </template>
            <template #2>
              <n-split 
                direction="vertical"
                :default-size="0.5"
                :min="0.2"
                :max="0.8"
              >
                <template #1>
                  <RequestPanel class="w-full h-full"/>
                </template>
                <template #2>
                  <ResponsePanel class="w-full h-full"/>
                </template>
              </n-split>
            </template>
          </n-split>
          <SearchPalette v-model:visible="isSearchVisible" />
        </div>
      </n-notification-provider>
    </n-message-provider>
  </n-config-provider>
</template>

<script setup lang="ts">
import { ref, onMounted, provide, computed } from 'vue';
import { NConfigProvider, NMessageProvider, NNotificationProvider, NGlobalStyle, NSplit } from 'naive-ui';
import { darkTheme, customDarkTheme, customLightTheme } from './theme';
import TopNavbar from './components/TopNavbar.vue';
import SidebarMenu from './components/SidebarMenu.vue';
import RequestPanel from './components/RequestPanel.vue';
import ResponsePanel from './components/ResponsePanel.vue';
import SearchPalette from './components/SearchPalette.vue';
import { useApiStore } from './stores/api';
import { useLocalStorage } from '@vueuse/core';

const apiStore = useApiStore();

const isSearchVisible = ref(false);
const sidebarRef = ref();

// Theme management
const isDark = useLocalStorage('theme-dark', true);

const currentTheme = computed(() => isDark.value ? darkTheme : null);
const currentThemeOverrides = computed(() => isDark.value ? customDarkTheme : customLightTheme);

const toggleTheme = () => {
  isDark.value = !isDark.value;
};

// Provide sidebar ref and theme to children
provide('sidebarRef', sidebarRef);
provide('isDark', isDark);

onMounted(async () => {
  await apiStore.fetchRoutes();
  await apiStore.fetchConfig();
});
</script>

<style scoped>
.h-screen {
  height: 100vh;
}
.flex {
  display: flex;
}
.flex-column {
  flex-direction: column;
}
.w-full {
  width: 100%;
}
.h-full {
  height: 100%;
}
</style>
