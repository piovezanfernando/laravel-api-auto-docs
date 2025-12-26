export interface IAPIRule {
  [key: string]: string[];
}

export interface IConfig {
  title: string;
  default_headers: string[];
}

export interface IAPIExample {
  [key: string]: string[];
}

export interface IAPIFieldInfos {
  [key: string]: {
    description: string;
    example: string;
  };
}

export interface IAPIInfo {
  id?: string;
  uri: string;
  summary: string;
  description: string;
  middlewares: string[];
  controller: string;
  controller_full_path: string;
  method: string;
  http_method: string; // The specific HTTP method for this Doc item (e.g., GET)
  rules: IAPIRule;
  path_parameters: IAPIRule;
  doc_block: string;
  group: string;
  group_index: number;
  responses: any;
  examples: IAPIExample;
  field_info: IAPIFieldInfos;
  rules_order: string[];
  tag: string;
  translated_model_plural?: string;
  translated_model_singular?: string;
}

export interface IRouteInfo {
  id: string;
  uri: string;
  methods: string[]; // Methods array for the route (e.g., ["GET"])
}

export interface IGroupedRoutes {
  group: string;
  routes: IRouteInfo[];
}

// For Pinia store filter state
export interface FilterState {
  sortBy: 'asc' | 'desc';
  showMethod: {
    GET: boolean;
    POST: boolean;
    PUT: boolean;
    PATCH: boolean;
    DELETE: boolean;
    HEAD: boolean;
  };
}

export interface AutoDocResponse {
  data?: any; // The actual response data
  _auto_doc?: {
    queries?: any[];
    logs?: any[];
    models?: any[];
    modelsTimeline?: any[];
    memory?: string;
  };
}

