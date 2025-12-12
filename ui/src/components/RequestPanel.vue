<template>
  <div class="h-full flex flex-column surface-ground text-color">
    <div v-if="!selectedRouteDetails" class="flex align-items-center justify-content-center h-full">
        <h3 class="text-xl text-color-secondary">Select a route to build your request</h3>
    </div>
    <div v-else class="flex flex-column h-full overflow-hidden">
      <!-- Request Method and URL Bar -->
      <div class="flex-shrink-0 p-3 pb-0">
        <div class="flex align-items-center gap-2 mb-3">
          <Dropdown
            v-model="requestMethod"
            :options="['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD']"
            placeholder="Method"
            class="w-8rem"
            :class="`method-${requestMethod}`"
            disabled
          />
          <InputText :value="finalUrl" class="flex-1" placeholder="Request URL" readonly />
          <Button icon="pi pi-send" label="Send" :loading="apiStore.sendingRequest" @click="sendCurrentRequest" />
        </div>
      </div>

      <!-- Request Tabs -->
      <div class="flex-1 request-tabs-container">
        <TabView class="h-full">
          <TabPanel header="Params" value="params">
              <div class="tab-content-scroll">
                  <div v-if="pathParams.length > 0" class="mb-4">
                      <h4 class="text-sm font-semibold mb-3 text-color-secondary">PATH PARAMETERS</h4>
                      <KeyValueEditor v-model="pathParams" key-readonly />
                  </div>
                  <div>
                      <h4 class="text-sm font-semibold mb-3 text-color-secondary">QUERY PARAMETERS</h4>
                      <KeyValueEditor v-model="queryParams" />
                  </div>
              </div>
          </TabPanel>
          <TabPanel header="Headers" value="headers">
              <div class="tab-content-scroll">
                  <h4 class="text-sm font-semibold mb-3 text-color-secondary">REQUEST HEADERS</h4>
                  <KeyValueEditor v-model="headers" />
              </div>
          </TabPanel>
          <TabPanel v-if="requestMethod !== 'GET' && requestMethod !== 'HEAD' && requestMethod !== 'DELETE'" header="Body" value="body">
              <div class="tab-content-scroll">
                  <h4 class="text-sm font-semibold mb-3 text-color-secondary">REQUEST BODY (JSON)</h4>
                  <div class="code-editor-wrapper">
                    <MonacoEditor v-model="requestBody" lang="json" :readOnly="false" height="500px" width="100%" />
                  </div>
              </div>
          </TabPanel>
          <TabPanel header="Validation" value="rules">
              <div class="tab-content-scroll">
                  <RulesTable v-if="selectedRouteDetails" :rules="selectedRouteDetails.rules" :field-info="selectedRouteDetails.field_info" />
              </div>
          </TabPanel>
        </TabView>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useApiStore } from '@/stores/api';
import { storeToRefs } from 'pinia';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import RulesTable from '@/components/elements/RulesTable.vue';
import MonacoEditor from '@/components/elements/MonacoEditor.vue';
import KeyValueEditor from '@/components/elements/KeyValueEditor.vue';

type KeyValuePair = { key: string; value: string; };

const apiStore = useApiStore();
const { selectedRouteDetails } = storeToRefs(apiStore);

const requestUrl = ref('');
const requestMethod = ref('');
const requestBody = ref('');
const headers = ref<KeyValuePair[]>([]);
const queryParams = ref<KeyValuePair[]>([]);
const pathParams = ref<KeyValuePair[]>([]);

const finalUrl = computed(() => {
    let url = requestUrl.value;
    pathParams.value.forEach(param => {
        if (param.value) {
            url = url.replace(`{${param.key}}`, param.value);
        }
    });
    return url;
});

watch(selectedRouteDetails, (newDetails) => {
  if (newDetails) {
    requestUrl.value = newDetails.uri;
    requestMethod.value = newDetails.http_method;

    pathParams.value = (newDetails.uri.match(/\{(\w+)\}/g)?.map(p => ({ key: p.slice(1, -1), value: '' })) || []);

    headers.value = [{ key: 'Content-Type', value: 'application/json' }, { key: 'Accept', value: 'application/json' }];

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
</script>

<style scoped>
.request-tabs-container {
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.request-tabs-container :deep(.p-tabview) {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.request-tabs-container :deep(.p-tabview-panels) {
  flex: 1;
  overflow: hidden;
  padding: 0;
}

.request-tabs-container :deep(.p-tabview-panel) {
  height: 100%;
}

.tab-content-scroll {
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 1.5rem;
}

.code-editor-wrapper {
  border: 1px solid var(--surface-border);
  border-radius: 6px;
  overflow: hidden;
}
</style>
