<template>
  <div class="h-full flex flex-column">
    <n-tabs
      v-model:value="activeMode"
      type="line"
      animated
      class="response-tabs"
      pane-class="response-tab-pane"
      :tabs-padding="20"
    >
      <n-tab-pane name="live" tab="Live">
        <div v-if="sendingRequest" class="flex-center tab-pane-content">
          <n-spin size="large" />
        </div>
        <div v-else-if="requestError" class="p-3 tab-pane-content">
          <n-alert type="error" :closable="false">
            {{ requestError }}
          </n-alert>
        </div>
        <div v-else-if="responseStatus === 0" class="flex-center tab-pane-content">
          <h3 class="empty-state">Send a request to see the response</h3>
        </div>
        <div v-else class="flex flex-column h-full">
          <!-- Toolbar -->
          <div class="response-toolbar">
            <n-space>
              <n-button
                :type="currentTab === 'body' ? 'primary' : 'default'"
                size="small"
                @click="currentTab = 'body'"
              >
                <template #icon>
                  <n-icon><CodeSlashOutline /></n-icon>
                </template>
                Body
              </n-button>
              <n-button
                :type="currentTab === 'headers' ? 'primary' : 'default'"
                size="small"
                @click="currentTab = 'headers'"
              >
                <template #icon>
                  <n-icon><ListOutline /></n-icon>
                </template>
                Headers
                <n-badge v-if="headersCount !== null" :value="headersCount" :type="headersCount === 0 ? 'error' : 'info'" class="ml-1" />
              </n-button>
              <n-button
                :type="currentTab === 'sql' ? 'primary' : 'default'"
                size="small"
                @click="currentTab = 'sql'"
              >
                <template #icon>
                  <n-icon><ServerOutline /></n-icon>
                </template>
                SQL
                <n-badge v-if="sqlCount !== null" :value="sqlCount" :type="sqlCount === 0 ? 'error' : 'info'" class="ml-1" />
              </n-button>
              <n-button
                :type="currentTab === 'logs' ? 'primary' : 'default'"
                size="small"
                @click="currentTab = 'logs'"
              >
                <template #icon>
                  <n-icon><DocumentTextOutline /></n-icon>
                </template>
                Logs
                <n-badge v-if="logsCount !== null" :value="logsCount" :type="logsCount === 0 ? 'error' : 'info'" class="ml-1" />
              </n-button>
              <n-button
                :type="currentTab === 'models' ? 'primary' : 'default'"
                size="small"
                @click="currentTab = 'models'"
              >
                <template #icon>
                  <n-icon><GitNetworkOutline /></n-icon>
                </template>
                Models
                <n-badge v-if="modelsCount !== null" :value="modelsCount" :type="modelsCount === 0 ? 'error' : 'info'" class="ml-1" />
              </n-button>
            </n-space>
          </div>

          <!-- Content -->
          <div class="response-content">
            <RequestBodyEditor
              v-if="currentTab === 'body'"
              :code="responseData"
              language="json"
              :readonly="true"
              @toggle-fullscreen="openFullscreen(responseData, 'json', 'Response Body')"
            />
            <RequestBodyEditor
              v-else-if="currentTab === 'headers'"
              :code="JSON.stringify(responseHeaders, null, 2)"
              language="json"
              :readonly="true"
              @toggle-fullscreen="openFullscreen(JSON.stringify(responseHeaders, null, 2), 'json', 'Response Headers')"
            />
            <RequestBodyEditor
              v-else-if="currentTab === 'sql'"
              :code="sqlData || 'No SQL queries recorded'"
              language="sql"
              :readonly="true"
              @toggle-fullscreen="openFullscreen(sqlData || 'No SQL queries recorded', 'sql', 'SQL Queries')"
            />
            <RequestBodyEditor
              v-else-if="currentTab === 'logs'"
              :code="logsData || 'No logs recorded'"
              language="text"
              :readonly="true"
              @toggle-fullscreen="openFullscreen(logsData || 'No logs recorded', 'text', 'Logs')"
            />
            <RequestBodyEditor
              v-else-if="currentTab === 'models'"
              :code="JSON.stringify(modelsData, null, 2)"
              language="json"
              :readonly="true"
              @toggle-fullscreen="openFullscreen(JSON.stringify(modelsData, null, 2), 'json', 'Models Events')"
            />
          </div>
        </div>
      </n-tab-pane>

      <n-tab-pane name="example" tab="Example">
        <div v-if="!selectedRouteDetails" class="flex-center tab-pane-content">
          <h3 class="empty-state">Select a route to see examples</h3>
        </div>
        <div v-else>
          <div v-if="!hasExamples" class="flex-center tab-pane-content">
            <h3 class="empty-state">No example responses configured for this route</h3>
          </div>
          <div v-else class="flex flex-column h-full">
            <div class="example-selector">
              <span class="selector-label">Example Response:</span>
              <n-space>
                <n-button
                  v-for="(_, status) in exampleResponses"
                  :key="status"
                  :type="getStatusType(Number(status))"
                  :ghost="selectedExampleStatus !== Number(status)"
                  size="small"
                  @click="selectExample(Number(status))"
                >
                  {{ status }}
                </n-button>
              </n-space>
            </div>
            <div v-if="currentExample" class="response-content">
              <RequestBodyEditor
                :code="JSON.stringify(currentExample.data, null, 2)"
                language="json"
                :readonly="true"
                @toggle-fullscreen="openFullscreen(JSON.stringify(currentExample.data, null, 2), 'json', 'Example Response Body')"
              />
            </div>
          </div>
        </div>
      </n-tab-pane>
    </n-tabs>

    <!-- Fullscreen Modal -->
    <n-modal
      v-model:show="showFullscreen"
      preset="card"
      :title="fullscreenTitle"
      style="width: 90vw; height: 90vh;"
      :bordered="false"
    >
      <RequestBodyEditor
        :code="fullscreenCode"
        :language="fullscreenLang"
        :readonly="true"
      />
    </n-modal>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useApiStore } from '@/stores/api';
import { storeToRefs } from 'pinia';
import { NButton, NTag, NSpace, NAlert, NSpin, NIcon, NBadge, NModal, NTabs, NTabPane } from 'naive-ui';
import {
  CodeSlashOutline,
  ListOutline,
  ServerOutline,
  DocumentTextOutline,
  GitNetworkOutline
} from '@vicons/ionicons5';
import RequestBodyEditor from '@/components/elements/RequestBodyEditor.vue';
import { responsesText } from '@/constants';

const apiStore = useApiStore();
const {
  responseData, responseStatus, responseHeaders, sqlData, logsData,
  modelsData, timeTaken, requestError, sendingRequest,
  selectedRouteDetails, isExampleMode, selectedExampleStatus
} = storeToRefs(apiStore);


// Two-way binding between local tab state and pinia store
const activeMode = ref(isExampleMode.value ? 'example' : 'live');
watch(activeMode, (newMode) => {
  apiStore.setExampleMode(newMode === 'example');
});
watch(isExampleMode, (newIsExample) => {
  activeMode.value = newIsExample ? 'example' : 'live';
});


const exampleResponses = computed(() => {
  return (apiStore.config?.responses || {}) as Record<string, any>;
});

const hasExamples = computed(() => {
  return exampleResponses.value && Object.keys(exampleResponses.value).length > 0;
});

const getSubMethodAndMessage = (httpMethod: string, uri: string) => {
  const hasId = /\{[^}]+\}/.test(uri); // Check if URI has parameters like {id}
  switch (httpMethod.toUpperCase()) {
    case 'GET':
      return hasId ? { subMethod: 'show', messageKey: 'retrieved', messageText: 'recuperado com sucesso', usePlural: false } : { subMethod: 'index', messageKey: 'retrieved', messageText: 'recuperado com sucesso', usePlural: true };
    case 'POST':
      return { subMethod: 'store', messageKey: 'saved', messageText: 'salvo com sucesso', usePlural: false };
    case 'PUT':
    case 'PATCH':
      return { subMethod: 'update', messageKey: 'updated', messageText: 'atualizado com sucesso', usePlural: false };
    case 'DELETE':
      return { subMethod: 'destroy', messageKey: 'deleted', messageText: 'deletado com sucesso', usePlural: false };
    default:
      return { subMethod: 'index', messageKey: 'retrieved', messageText: 'recuperado com sucesso', usePlural: true };
  }
};

const currentExample = computed(() => {
  if (!hasExamples.value) return null;
  const examples = exampleResponses.value;
  const status = selectedExampleStatus.value;
  if (examples[status]) {
    // Deep copy the object to avoid mutating the original state
    let data = JSON.parse(JSON.stringify(examples[status]));
    if (status === 200 && selectedRouteDetails.value) {
      const { subMethod, usePlural } = getSubMethodAndMessage(selectedRouteDetails.value.http_method, selectedRouteDetails.value.uri);
      if (data[subMethod]) {
        data = data[subMethod];
        // Replace :model with translated model name
        if (data.message && data.message.includes(':model')) {
          const modelName = usePlural ? selectedRouteDetails.value.translated_model_plural : selectedRouteDetails.value.translated_model_singular;
          data.message = data.message.replace(':model', modelName);
        }
      }
    }
    return { status, data };
  }
  // Fallback to first available
  const firstStatus = Object.keys(examples)[0];
  return { status: Number(firstStatus), data: examples[Number(firstStatus)] };
});

const selectExample = (status: number) => {
  apiStore.setExampleStatus(status);
};

const getStatusType = (status: number) => {
  if (status >= 200 && status < 300) return 'success';
  if (status >= 400 && status < 500) return 'warning';
  if (status >= 500) return 'error';
  return 'info';
};

const currentTab = ref('body');

// Fullscreen state
const showFullscreen = ref(false);
const fullscreenCode = ref('');
const fullscreenLang = ref<'json' | 'sql' | 'text'>('json');
const fullscreenTitle = ref('');

const openFullscreen = (code: string, lang: 'json' | 'sql' | 'text', title: string) => {
  fullscreenCode.value = code;
  fullscreenLang.value = lang;
  fullscreenTitle.value = title;
  showFullscreen.value = true;
};

const responseStatusText = computed(() => responsesText[responseStatus.value.toString()] || "");

const responseTagType = computed(() => {
  const status = responseStatus.value;
  if (status >= 200 && status < 300) return 'success';
  if (status >= 400 && status < 500) return 'warning';
  if (status >= 500) return 'error';
  return 'info';
});

const responseSize = computed(() => {
  const bytes = responseData.value.length;
  if (bytes < 1024) return `${bytes} bytes`;
  return `${(bytes / 1024).toFixed(1)} KB`;
});

const roundedTime = computed(() => Math.round(timeTaken.value));

// Count items for badges
const headersCount = computed(() => {
  if (!responseHeaders.value || responseStatus.value === 0) return null;
  return Object.keys(responseHeaders.value).length;
});

const sqlCount = computed(() => {
  if (!sqlData.value || responseStatus.value === 0) return null;
  // Count number of SQL queries by splitting on "Time:" prefix
  const matches = sqlData.value.match(/Time:/g);
  return matches ? matches.length : 0;
});

const logsCount = computed(() => {
  if (!logsData.value || responseStatus.value === 0) return null;
  // Count number of log lines
  return logsData.value.split('\n').filter(line => line.trim()).length;
});

const modelsCount = computed(() => {
  if (!modelsData.value || responseStatus.value === 0) return null;
  if (typeof modelsData.value === 'object') {
    return Object.keys(modelsData.value).length;
  }
  return 0;
});
</script>

<style scoped>
.h-full {
  height: 100%;
}

.flex {
  display: flex;
}

.flex-column {
  flex-direction: column;
}

.flex-center {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

.p-3 {
  padding: 0.75rem;
}

.ml-1 {
  margin-left: 0.25rem;
}

.empty-state {
  font-size: 1.25rem;
  color: var(--n-text-color-disabled);
}

.response-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 1rem;
  border-bottom: 1px solid var(--n-border-color);
  flex-shrink: 0;
  background: var(--n-color);
}

.response-content {
  flex: 1;
  min-height: 0;
  padding: 1rem;
  display: flex;
  flex-direction: column;
}

.example-selector {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--n-border-color);
  display: flex;
  align-items: center;
  gap: 1rem;
  background: var(--n-color);
}

.selector-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--n-text-color);
}

.response-tabs {
  height: 100%;
  display: flex;
  flex-direction: column;
}

:deep(.n-tabs-pane-wrapper) {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

:deep(.n-tab-pane) {
  height: 100%;
  display: flex;
  flex-direction: column;
  padding-top: 1rem;
}

.tab-pane-content {
  height: 100%;
}
</style>