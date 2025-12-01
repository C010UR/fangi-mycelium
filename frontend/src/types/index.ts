export type MFAType = 'email';

export type UserRole = 'ROLE_USER' | 'ROLE_ADMIN';

export interface MFAMethod {
  method: MFAType;
  recipient: string;
}

export interface User {
  id: number;
  email: string;
  username: string;
  image_url: string | null;
  roles: UserRole[];
  is_active: boolean;
  is_banned: boolean;
  is_activated: boolean;
  created_by: string | null;
  created_at: string;
  updated_at: string;
  last_login_at: string | null;
}

export interface ApiError {
  error: boolean;
  status_code: number;
  error_description: string;
}

export interface AuthResponse {
  token: string;
  mfa_required: boolean;
  priority_mfa_method: MFAMethod | null;
  available_mfa_methods: MFAMethod[] | null;
}

export interface MfaAvailableResponse {
  priority_mfa_method: MFAMethod;
  available_mfa_methods: MFAMethod[];
}

export interface MfaSendRequestResponse {
  sent_at: string;
  next_request_available_in: number;
}

export interface MfaVerifyRequest {
  code: string;
}

export interface RegistrationForm {
  email: string;
  username: string;
  password: string;
  image: File | null;
}

export interface RegistrationResponse {
  error: boolean;
  status_code: number;
  message?: string;
  error_description?: string;
}

export type FilterOperator =
  | 'eq'
  | 'ne'
  | 'gt'
  | 'gte'
  | 'lt'
  | 'lte'
  | 'like'
  | 'in'
  | 'nin'
  | 'null'
  | 'notnull'
  | 'true'
  | 'false';

export interface Filter {
  field: string;
  operator: FilterOperator;
  value: string | string[] | null;
}

export type OrderDirection = 'asc' | 'desc';

export interface Order {
  field: string;
  order: OrderDirection;
}

export interface ListMeta {
  additional_data: Record<string, unknown> | null;
  filters: Filter[];
  orders: Order[];
  search: string;
  page: number;
  page_size: number;
  total_items: number;
  total_pages: number;
}

export interface ListResult<T> {
  meta: ListMeta;
  data: T[];
}

export interface Server {
  id: number;
  name: string;
  image_url: string | null;
  urls: string[];
  url: string;
  client_id: string;
  is_active: boolean;
  is_banned: boolean;
  created_by: string | null;
  created_at: string;
  updated_at: string;
}

export interface ServerForm {
  name: string;
  image: File | null;
  urls: string[] | null;
}
