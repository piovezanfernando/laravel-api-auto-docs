<template>
  <div class="h-screen flex flex-column">
    <TopNavbar @open-search="isSearchVisible = true" />
    <Splitter style="height: calc(100vh - 4rem)" class="mb-5">
      <SplitterPanel :size="20" :minSize="10" class="flex align-items-center justify-content-center">
          <SidebarMenu class="w-full h-full"/>
      </SplitterPanel>
      <SplitterPanel :size="80" :minSize="30">
        <Splitter layout="vertical">
          <SplitterPanel :size="50" :minSize="20" class="flex align-items-center justify-content-center">
            <RequestPanel class="w-full h-full"/>
          </SplitterPanel>
          <SplitterPanel :size="50" :minSize="20" class="flex align-items-center justify-content-center">
            <ResponsePanel class="w-full h-full"/>
          </SplitterPanel>
        </Splitter>
      </SplitterPanel>
    </Splitter>
    <SearchPalette v-model:visible="isSearchVisible" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import TopNavbar from './components/TopNavbar.vue';
import SidebarMenu from './components/SidebarMenu.vue';
import RequestPanel from './components/RequestPanel.vue';
import ResponsePanel from './components/ResponsePanel.vue';
import SearchPalette from './components/SearchPalette.vue';
import Splitter from 'primevue/splitter';
import SplitterPanel from 'primevue/splitterpanel';
import { useApiStore } from './stores/api';

const apiStore = useApiStore();

const isSearchVisible = ref(false);

onMounted(async () => {
  await apiStore.fetchRoutes();
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
</style>
