<template>
  <div class="h-full flex flex-column">
    <div v-if="sendingRequest" class="flex-center">
      <n-spin size="large" />
    </div>
    <div v-else-if="requestError" class="p-3">
      <n-alert type="error" :closable="false">
        {{ requestError }}
      </n-alert>
    </div>
    <div v-else-if="responseStatus === 0" class="flex-center">
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
        <n-space align="center">
          <n-tag :type="responseTagType" size="medium">
            {{ responseStatus }} {{ responseStatusText }}
          </n-tag>
          <span class="meta-text">{{ roundedTime }}ms</span>
          <span class="meta-text">{{ responseSize }}</span>
        </n-space>
      </div>
      
      <!-- Content -->
      <div class="response-content">
        <CodeViewer 
          v-if="currentTab === 'body'" 
          :code="responseData" 
          language="json"
          allow-fullscreen
          @toggle-fullscreen="openFullscreen(responseData, 'json', 'Response Body')"
        />
        <CodeViewer 
          v-else-if="currentTab === 'headers'" 
          :code="JSON.stringify(responseHeaders, null, 2)" 
          language="json"
          allow-fullscreen
          @toggle-fullscreen="openFullscreen(JSON.stringify(responseHeaders, null, 2), 'json', 'Response Headers')"
        />
        <CodeViewer 
          v-else-if="currentTab === 'sql'" 
          :code="sqlData || 'No SQL queries recorded'" 
          language="sql"
          allow-fullscreen
          @toggle-fullscreen="openFullscreen(sqlData || 'No SQL queries recorded', 'sql', 'SQL Queries')"
        />
        <CodeViewer 
          v-else-if="currentTab === 'logs'" 
          :code="logsData || 'No logs recorded'" 
          language="text"
          allow-fullscreen
          @toggle-fullscreen="openFullscreen(logsData || 'No logs recorded', 'text', 'Logs')"
        />
        <CodeViewer 
          v-else-if="currentTab === 'models'" 
          :code="JSON.stringify(modelsData, null, 2)" 
          language="json"
          allow-fullscreen
          @toggle-fullscreen="openFullscreen(JSON.stringify(modelsData, null, 2), 'json', 'Models Events')"
        />
      </div>
    </div>

    <!-- Fullscreen Modal -->
    <n-modal
      v-model:show="showFullscreen"
      preset="card"
      :title="fullscreenTitle"
      style="width: 90vw; height: 90vh;"
      :bordered="false"
    >
      <CodeViewer 
        :code="fullscreenCode"
        :language="fullscreenLang"
        :allow-fullscreen="false"
      />
    </n-modal>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useApiStore } from '@/stores/api';
import { storeToRefs } from 'pinia';
import { NButton, NTag, NSpace, NAlert, NSpin, NIcon, NBadge, NModal } from 'naive-ui';
import { 
  CodeSlashOutline, 
  ListOutline, 
  ServerOutline, 
  DocumentTextOutline, 
  GitNetworkOutline 
} from '@vicons/ionicons5';
import CodeViewer from '@/components/elements/CodeViewer.vue';
import { responsesText } from '@/constants';

const apiStore = useApiStore();
const {
  responseData, responseStatus, responseHeaders, sqlData, logsData,
  modelsData, timeTaken, requestError, sendingRequest
} = storeToRefs(apiStore);

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

.meta-text {
  font-size: 0.875rem;
  color: var(--n-text-color-disabled);
}

.response-content {
  flex: 1;
  min-height: 0;
  padding: 1rem;
  display: flex;
  flex-direction: column;
}
</style>
