<template>
  <div class="h-full flex flex-column">
    <div v-if="!selectedRouteDetails" class="flex-center">
      <h3 class="empty-state">Select a route to build your request</h3>
    </div>
    <div v-else class="flex flex-column h-full">
      <!-- Request Method and URL Bar -->
      <div class="request-bar">
        <div class="request-controls">
          <n-button-group size="medium">
            <n-button 
              :type="!apiStore.isExampleMode ? 'primary' : 'default'"
              @click="apiStore.setExampleMode(false)"
            >
              Live
            </n-button>
            <n-button 
              :type="apiStore.isExampleMode ? 'primary' : 'default'"
              @click="apiStore.setExampleMode(true)"
            >
              Example
            </n-button>
          </n-button-group>
          <n-select
            v-model:value="requestMethod"
            :options="methodOptions"
            :disabled="true"
            size="medium"
            style="width: 120px;"
          />
          <n-input
            :value="finalUrl"
            readonly
            placeholder="Request URL"
            class="url-input"
          />
          <n-button
            v-if="!apiStore.isExampleMode"
            type="primary"
            :loading="apiStore.sendingRequest"
            @click="sendCurrentRequest"
            class="send-button"
          >
            <template #icon>
              <n-icon><SendOutline /></n-icon>
            </template>
            Send
          </n-button>
        </div>
      </div>

      <!-- Request Tabs with Button Style -->
      <div class="request-tabs-container">
        <div class="tabs-toolbar">
          <n-space>
            <n-button
              :type="currentTab === 'params' ? 'primary' : 'default'"
              size="small"
              @click="currentTab = 'params'"
            >
              <template #icon>
                <n-icon><ListOutline /></n-icon>
              </template>
              Params
            </n-button>
            <n-button
              :type="currentTab === 'headers' ? 'primary' : 'default'"
              size="small"
              @click="currentTab = 'headers'"
            >
              <template #icon>
                <n-icon><CodeSlashOutline /></n-icon>
              </template>
              Headers
            </n-button>
            <n-button
              v-if="requestMethod !== 'GET' && requestMethod !== 'HEAD' && requestMethod !== 'DELETE'"
              :type="currentTab === 'body' ? 'primary' : 'default'"
              size="small"
              @click="currentTab = 'body'"
            >
              <template #icon>
                <n-icon><DocumentTextOutline /></n-icon>
              </template>
              Body
            </n-button>
            <n-button
              :type="currentTab === 'rules' ? 'primary' : 'default'"
              size="small"
              @click="currentTab = 'rules'"
            >
              <template #icon>
                <n-icon><ShieldCheckmarkOutline /></n-icon>
              </template>
              Validation
            </n-button>
          </n-space>
        </div>

        <div class="tab-content-area">
          <div v-if="currentTab === 'params'" class="tab-content-scroll">
            <div v-if="pathParams.length > 0" class="section">
              <h4 class="section-title">PATH PARAMETERS</h4>
              <KeyValueEditor v-model="pathParams" :key-readonly="true" />
            </div>
            <div class="section">
              <h4 class="section-title">QUERY PARAMETERS</h4>
              <KeyValueEditor v-model="queryParams" />
            </div>
          </div>

          <div v-else-if="currentTab === 'headers'" class="tab-content-scroll">
            <h4 class="section-title">REQUEST HEADERS</h4>
            <KeyValueEditor v-model="headers" />
          </div>

          <div v-else-if="currentTab === 'body'" class="tab-content-body">
            <RequestBodyEditor 
              v-model="requestBody"
              @toggle-fullscreen="openBodyFullscreen"
            />
          </div>

          <div v-else-if="currentTab === 'rules'" class="tab-content-validation">
            <RulesTable 
              v-if="selectedRouteDetails" 
              :rules="selectedRouteDetails.rules" 
              :field-info="selectedRouteDetails.field_info" 
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Fullscreen Body Editor -->
    <n-modal
      v-model:show="showBodyFullscreen"
      preset="card"
      title="Edit Request Body"
      style="width: 90vw; height: 90vh;"
      :bordered="false"
    >
      <div style="height: calc(90vh - 120px);">
        <RequestBodyEditor v-model="requestBody" />
      </div>
    </n-modal>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useApiStore } from '@/stores/api';
import { storeToRefs } from 'pinia';
import { NInput, NButton, NSelect, NIcon, NModal, NSpace, NButtonGroup } from 'naive-ui';
import { SendOutline, ListOutline, CodeSlashOutline, DocumentTextOutline, ShieldCheckmarkOutline } from '@vicons/ionicons5';
import RulesTable from '@/components/elements/RulesTable.vue';
import KeyValueEditor from '@/components/elements/KeyValueEditor.vue';
import RequestBodyEditor from '@/components/elements/RequestBodyEditor.vue';

type KeyValuePair = { key: string; value: string; };

const apiStore = useApiStore();
const { selectedRouteDetails } = storeToRefs(apiStore);

const requestUrl = ref('');
const requestMethod = ref('');
const requestBody = ref('');
const headers = ref<KeyValuePair[]>([]);
const queryParams = ref<KeyValuePair[]>([]);
const pathParams = ref<KeyValuePair[]>([]);

const showBodyFullscreen = ref(false);
const currentTab = ref('params');

const methodOptions = [
  { label: 'GET', value: 'GET' },
  { label: 'POST', value: 'POST' },
  { label: 'PUT', value: 'PUT' },
  { label: 'PATCH', value: 'PATCH' },
  { label: 'DELETE', value: 'DELETE' },
  { label: 'HEAD', value: 'HEAD' }
];

const finalUrl = computed(() => {
  let url = requestUrl.value;
  pathParams.value.forEach(param => {
    if (param.value) {
      url = url.replace(`{${param.key}}`, param.value);
    }
  });
  return url;
});

const openBodyFullscreen = () => {
  showBodyFullscreen.value = true;
};

watch(selectedRouteDetails, (newDetails) => {
  if (newDetails) {
    requestUrl.value = newDetails.uri;
    requestMethod.value = newDetails.http_method;

    pathParams.value = (newDetails.uri.match(/\{(\w+)\}/g)?.map(p => ({ key: p.slice(1, -1), value: '' })) || []);

    headers.value = [
      { key: 'Content-Type', value: 'application/json' }, 
      { key: 'Accept', value: 'application/json' }
    ];

    const examples = newDetails.examples;
    if (examples && Object.keys(examples).length > 0) {
      requestBody.value = JSON.stringify(examples, null, 2);
    } else {
      requestBody.value = buildBodyFromRules(newDetails.rules);
    }

    queryParams.value = buildParamsFromRules(newDetails.rules, 'query.');
  }
}, { immediate: true, deep: true });

function buildParamsFromRules(rules: any, prefix: string): KeyValuePair[] {
  if (!rules) return [];
  return Object.keys(rules)
    .filter(key => key.startsWith(prefix))
    .map(key => ({ key: key.replace(prefix, ''), value: '' }));
}

function buildBodyFromRules(rules: any): string {
  if (!rules) return '{}';
  const body: { [key: string]: any } = {};
  Object.keys(rules).forEach(key => {
    if (!key.includes('query.')) {
      body[key] = '';
    }
  });
  return JSON.stringify(body, null, 2);
}

function arrayToQueryString(params: KeyValuePair[]): string {
  const searchParams = new URLSearchParams();
  params.forEach(p => {
    if (p.key) searchParams.append(p.key, p.value);
  });
  const queryString = searchParams.toString();
  return queryString ? `?${queryString}` : '';
}

function arrayToHeadersObject(params: KeyValuePair[]): { [key: string]: string } {
  return params.reduce((acc, p) => {
    if (p.key) acc[p.key] = p.value;
    return acc;
  }, {} as { [key: string]: string });
}

const sendCurrentRequest = async () => {
  if (!selectedRouteDetails.value) return;

  apiStore.sendRequest(
    requestMethod.value,
    finalUrl.value,
    requestBody.value,
    JSON.stringify(arrayToHeadersObject(headers.value)),
    arrayToQueryString(queryParams.value)
  );
};

// Keyboard shortcut: Ctrl/Cmd + Enter to send
if (typeof window !== 'undefined') {
  const handleKeyDown = (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      sendCurrentRequest();
    }
  };
  window.addEventListener('keydown', handleKeyDown);
}
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

.empty-state {
  font-size: 1.25rem;
  color: var(--n-text-color-disabled);
}

.request-bar {
  padding: 1rem;
  border-bottom: 1px solid var(--n-border-color);
  flex-shrink: 0;
}

.request-controls {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.url-input {
  flex: 1;
}

.send-button {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
  transition: all 0.3s ease;
}

.send-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.request-tabs-container {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.tabs-toolbar {
  padding: 0.5rem 1rem;
  border-bottom: 1px solid var(--n-border-color);
  flex-shrink: 0;
  background: var(--n-color);
}

.tab-content-area {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
}

.tab-content-scroll {
  overflow-y: auto;
  padding: 1.5rem;
  flex: 1;
  min-height: 0;
}

.tab-content-body {
  height: 100%;
  padding: 1rem 1.5rem;
  display: flex;
  flex-direction: column;
}

.tab-content-validation {
  height: 100%;
  padding: 1rem;
  display: flex;
  flex-direction: column;
}

.section {
  margin-bottom: 2rem;
}

.section:last-child {
  margin-bottom: 0;
}

.section-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--n-text-color-disabled);
  margin-bottom: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
</style>
