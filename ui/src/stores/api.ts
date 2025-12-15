import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { IAPIInfo, IGroupedRoutes, FilterState, AutoDocResponse } from '../types';
import { useLocalStorage } from '@/composables/useLocalStorage';

export const useApiStore = defineStore('api', () => {
  // --- State ---
  const apiHost = useLocalStorage('apiHost', window.location.origin);
  const rawRoutes = ref<IGroupedRoutes[]>([]);
  const fullRoutes = ref<IAPIInfo[]>([]);
  const selectedRouteId = ref<string | null>(null);
  const selectedRouteDetails = ref<IAPIInfo | null>(null);
  const isLoadingRoutes = ref<boolean>(false);
  const isLoadingDetails = ref<boolean>(false);
  const globalAuthToken = useLocalStorage<string | null>('globalAuthToken', null);
  const searchText = ref<string>('');

  const filters = ref<FilterState>({
    sortBy: 'asc',
    showMethod: {
      GET: true,
      POST: true,
      PUT: true,
      PATCH: true,
      DELETE: true,
      HEAD: false,
    },
  });

  // Response related state
  const sendingRequest = ref<boolean>(false);
  const requestError = ref<string | null>(null);
  const responseData = ref<string>('');
  const responseHeaders = ref<any>({});
  const responseStatus = ref<number>(0);
  const timeTaken = ref<number>(0);
  const sqlData = ref<string>('');
  const logsData = ref<string>('');
  const modelsData = ref<any>({});
  const serverMemory = ref<string>('');

  // --- Getters ---
  const filteredAndSortedRoutes = computed(() => {
    let result = [...rawRoutes.value];

    const search = searchText.value.trim().toLowerCase();
    if (search) {
      const searchFiltered: IGroupedRoutes[] = [];
      result.forEach(group => {
        const matchingRoutes = group.routes.filter(route =>
          group.group.toLowerCase().includes(search) || route.uri.toLowerCase().includes(search)
        );
        if (matchingRoutes.length > 0) {
          searchFiltered.push({ group: group.group, routes: matchingRoutes });
        }
      });
      result = searchFiltered;
    }

    const activeMethods = new Set(Object.entries(filters.value.showMethod)
      .filter(([, isActive]) => isActive)
      .map(([method]) => method));

    const methodFiltered: IGroupedRoutes[] = [];
    result.forEach(group => {
      const matchingRoutes = group.routes.filter(route => activeMethods.has(route.methods[0]));
      if (matchingRoutes.length > 0) {
        methodFiltered.push({ group: group.group, routes: matchingRoutes });
      }
    });
    result = methodFiltered;

    if (filters.value.sortBy === 'asc') {
      result.sort((a, b) => a.group.localeCompare(b.group));
    } else if (filters.value.sortBy === 'desc') {
      result.sort((a, b) => b.group.localeCompare(a.group));
    }

    return result;
  });

  // --- Actions ---
  async function fetchRoutes() {
    isLoadingRoutes.value = true;
    try {
      // Use explicit string check because the env var might be a string "true" or "false"
      // thanks to the defines in vite.config.ts
      const isDemo = import.meta.env.VITE_IS_DEMO === 'true';
      const url = isDemo ? '/sample.json' : `${apiHost.value}/docs-api/routes`;
      const response = await fetch(url);
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();
      if (isDemo) {
        fullRoutes.value = data;
        // Group by controller for demo
        const grouped: { [key: string]: IAPIInfo[] } = {};
        data.forEach((route: IAPIInfo) => {
          const group = route.controller || 'Others';
          if (!grouped[group]) grouped[group] = [];
          grouped[group].push(route);
        });
        rawRoutes.value = Object.keys(grouped).map(group => ({
          group,
          routes: grouped[group].map(r => ({ id: r.uri, uri: r.uri, methods: [r.http_method] }))
        }));
      } else {
        rawRoutes.value = data;
      }
    } catch (err: any) {
      requestError.value = `Failed to fetch routes: ${err.message}. Check API Host setting.`;
      console.error(requestError.value);
    } finally {
      isLoadingRoutes.value = false;
    }
  }

  async function fetchRouteDetails(id: string) {
    isLoadingDetails.value = true;
    selectedRouteId.value = id;
    // Clear previous response
    clearResponse();
    try {
      const isDemo = import.meta.env.VITE_IS_DEMO === 'true';
      if (isDemo) {
        selectedRouteDetails.value = fullRoutes.value.find(r => r.uri === id) || null;
      } else {
        const response = await fetch(`${apiHost.value}/docs-api/routes/${id}`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        selectedRouteDetails.value = await response.json();
      }
    } catch (err: any) {
      requestError.value = `Failed to fetch route details for ${id}: ${err.message}`;
      console.error(requestError.value);
      selectedRouteDetails.value = null;
    } finally {
      isLoadingDetails.value = false;
    }
  }

  function setGlobalAuthToken(token: string | null) {
    globalAuthToken.value = token;
  }

  function updateFilter(key: 'sortBy' | keyof FilterState['showMethod'], value: any) {
    if (key === 'sortBy') {
      filters.value.sortBy = value;
    } else if (key in filters.value.showMethod) {
      filters.value.showMethod[key as keyof FilterState['showMethod']] = value;
    }
  }

  function setSearchText(text: string) {
    searchText.value = text;
  }

  async function sendRequest(method: string, url: string, body: string, headers: string, queryParams: string) {
    sendingRequest.value = true;
    requestError.value = null;

    try {
      const fullUrl = `${apiHost.value}/${url}${queryParams}`;
      const parsedHeaders = JSON.parse(headers);
      if (globalAuthToken.value) {
        parsedHeaders['Authorization'] = globalAuthToken.value;
      }
      parsedHeaders['X-Auto-Doc'] = true;

      let requestBodyContent: any = method.match(/GET|HEAD/) ? undefined : body;

      const startTime = performance.now();
      const response = await fetch(fullUrl, {
        method,
        headers: parsedHeaders,
        body: requestBodyContent,
      });

      timeTaken.value = performance.now() - startTime;
      responseStatus.value = response.status;
      responseHeaders.value = Object.fromEntries(response.headers.entries());

      const dataString = await response.text();
      let data: AutoDocResponse | any;
      try {
        data = JSON.parse(dataString);
        const wasWrapped = data && data._auto_doc;
        if (wasWrapped) {
          sqlData.value = data._auto_doc.queries?.map((q: any) => `Time: ${q.time}ms\n${q.sql}`).join('\n\n') || '';
          logsData.value = data._auto_doc.logs?.map((l: any) => `${l.level}: ${l.message}`).join('\n') || '';
          modelsData.value = data._auto_doc.models || {};
          serverMemory.value = data._auto_doc.memory || '';
          responseData.value = JSON.stringify(data.data, null, 2);
        } else {
          responseData.value = JSON.stringify(data, null, 2);
        }
      } catch {
        responseData.value = dataString;
      }
    } catch (err: any) {
      requestError.value = `Request failed: ${err.message}`;
    } finally {
      sendingRequest.value = false;
    }
  }

  function clearResponse() {
    responseData.value = '';
    responseStatus.value = 0;
    responseHeaders.value = {};
    sqlData.value = '';
    logsData.value = '';
    modelsData.value = {};
    timeTaken.value = 0;
    requestError.value = '';
  }

  function clearAllFilters() {
    filters.value = { sortBy: 'asc', showMethod: { GET: true, POST: true, PUT: true, PATCH: true, DELETE: true, HEAD: false } };
    searchText.value = '';
    apiHost.value = window.location.origin;
    setGlobalAuthToken(null);
  }

  return {
    apiHost, rawRoutes, selectedRouteId, selectedRouteDetails, isLoadingRoutes, isLoadingDetails, globalAuthToken, filters, searchText,
    sendingRequest, requestError, responseData, responseHeaders, responseStatus, timeTaken, sqlData, logsData, modelsData, serverMemory,
    filteredAndSortedRoutes,
    fetchRoutes, fetchRouteDetails, setGlobalAuthToken, updateFilter, setSearchText, sendRequest, clearResponse, clearAllFilters,
  };
});