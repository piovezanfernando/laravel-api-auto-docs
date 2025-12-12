<template>
  <div class="h-full flex flex-column p-3 surface-ground text-color">
    <div v-if="sendingRequest" class="flex justify-content-center align-items-center h-full">
      <ProgressSpinner />
    </div>
    <div v-else-if="requestError" class="mb-3">
      <Message severity="error" :closable="false">{{ requestError }}</Message>
    </div>
    <div v-else-if="responseStatus === 0" class="flex align-items-center justify-content-center h-full">
      <h3 class="text-xl text-color-secondary">Send a request to see the response</h3>
    </div>
    <div v-else class="flex flex-column h-full">
      <Toolbar class="mb-2 p-1">
        <template #start>
          <Button text @click="currentTab = 'responseBody'" icon="pi pi-code" label="Body" class="mr-1" :class="{ 'bg-primary text-white': currentTab === 'responseBody' }" />
          <Button text @click="currentTab = 'headers'" icon="pi pi-list" label="Headers" class="mr-1" :class="{ 'bg-primary text-white': currentTab === 'headers' }" />
          <Button text @click="currentTab = 'sql'" icon="pi pi-database" label="SQL" class="mr-1" :class="{ 'bg-primary text-white': currentTab === 'sql' }" />
          <Button text @click="currentTab = 'logs'" icon="pi pi-file" label="Logs" class="mr-1" :class="{ 'bg-primary text-white': currentTab === 'logs' }" />
          <Button text @click="currentTab = 'modelsEvents'" icon="pi pi-sitemap" label="Models" :class="{ 'bg-primary text-white': currentTab === 'modelsEvents' }" />
        </template>
        <template #end>
          <Tag :value="`${responseStatus} ${responseStatusText}`" :severity="responseTagSeverity" />
          <span class="ml-2 text-base text-color-secondary">{{ roundedTime }}ms</span>
          <span class="ml-2 text-base text-color-secondary">{{ responseSize }}</span>
        </template>
      </Toolbar>
      <div class="flex-grow overflow-hidden">
        <div v-if="currentTab === 'responseBody'" class="h-full">
          <MonacoEditor :modelValue="responseData" lang="json" :readOnly="true" height="100%" width="100%" />
        </div>
        <div v-if="currentTab === 'headers'" class="h-full">
          <MonacoEditor :modelValue="JSON.stringify(responseHeaders, null, 2)" lang="json" :readOnly="true" height="100%" width="100%" />
        </div>
        <div v-if="currentTab === 'sql'" class="h-full">
          <MonacoEditor :modelValue="sqlData" lang="sql" :readOnly="true" height="100%" width="100%" />
        </div>
        <div v-if="currentTab === 'logs'" class="h-full">
          <MonacoEditor :modelValue="logsData" lang="text" :readOnly="true" height="100%" width="100%" />
        </div>
        <div v-if="currentTab === 'modelsEvents'" class="h-full">
          <MonacoEditor :modelValue="JSON.stringify(modelsData, null, 2)" lang="json" :readOnly="true" height="100%" width="100%" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useApiStore } from '@/stores/api';
import { storeToRefs } from 'pinia';
import Toolbar from 'primevue/toolbar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import MonacoEditor from '@/components/elements/MonacoEditor.vue';
import { responsesText } from '@/constants';

const apiStore = useApiStore();
const {
  responseData, responseStatus, responseHeaders, sqlData, logsData,
  modelsData, timeTaken, requestError, sendingRequest
} = storeToRefs(apiStore);

const currentTab = ref('responseBody');

const responseStatusText = computed(() => responsesText[responseStatus.value.toString()] || "");
const responseTagSeverity = computed(() => {
  const status = responseStatus.value;
  if (status >= 200 && status < 300) return 'success';
  if (status >= 400 && status < 500) return 'warn';
  if (status >= 500) return 'danger';
  return 'info';
});

const responseSize = computed(() => {
  const bytes = responseData.value.length;
  if (bytes < 1024) return `${bytes} bytes`;
  return `${(bytes / 1024).toFixed(1)} KB`;
});

const roundedTime = computed(() => Math.round(timeTaken.value));

</script>

<style>
.p-tabview-panels {
  flex-grow: 1;
  overflow: auto;
}
</style>
