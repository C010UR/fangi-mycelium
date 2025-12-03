import type { MFAType } from '@/types';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api/v1';

class ApiRoutes {
  private static readonly BASE = API_BASE_URL;

  static readonly AUTH = {
    LOGIN: `${this.BASE}/login`,
    LOGOUT: `${this.BASE}/logout`,
    PROFILE: `${this.BASE}/profile`,
    UPDATE_PROFILE: `${this.BASE}/profile`,
  } as const;

  static readonly MFA = {
    VERIFY: `${this.BASE}/mfa/verify`,
    AVAILABLE: `${this.BASE}/mfa/available`,
    SEND_REQUEST: (method: MFAType) => `${this.BASE}/mfa/send-request/${method}`,
  } as const;

  static readonly PASSWORD_RESET = {
    REQUEST: `${this.BASE}/password-reset`,
    RESET: (token: string) => `${this.BASE}/password-reset/${token}`,
  } as const;

  static readonly REGISTER = {
    POST: `${this.BASE}/register`,
    CONFIRM: (token: string) => `${this.BASE}/register/${token}`,
  } as const;

  static readonly SERVER = {
    LIST: `${this.BASE}/servers`,
    LIST_ACTIVE: `${this.BASE}/servers/active`,
    CREATE: `${this.BASE}/servers/create`,
    GET: (id: string) => `${this.BASE}/servers/${id}`,
    UPDATE: (id: string) => `${this.BASE}/servers/${id}`,
    ACTIVATE: (id: string) => `${this.BASE}/servers/${id}/activate`,
    DEACTIVATE: (id: string) => `${this.BASE}/servers/${id}/deactivate`,
    GENERATE_SECRET: (id: string) => `${this.BASE}/servers/${id}/generate-secret`,
  } as const;

  static readonly SERVER_MODULES = {
    LIST: (serverId: string) => `${this.BASE}/servers/${serverId}/modules`,
    CAN_ADD_LIST: (serverId: string) => `${this.BASE}/servers/${serverId}/modules/can-add`,
    ADD: (serverId: string) => `${this.BASE}/servers/${serverId}/modules/add`,
    REMOVE: (serverId: string, moduleId: string) =>
      `${this.BASE}/servers/${serverId}/modules/${moduleId}/remove`,
  } as const;

  static readonly USERS = {
    LIST: `${this.BASE}/users`,
    CREATE: `${this.BASE}/users/create`,
    GET: (id: string) => `${this.BASE}/users/${id}`,
    UPDATE: (id: string) => `${this.BASE}/users/${id}`,
    ACTIVATE: (id: string) => `${this.BASE}/users/${id}/activate`,
    DEACTIVATE: (id: string) => `${this.BASE}/users/${id}/deactivate`,
    RESEND_ACTIVATION_EMAIL: (id: string) => `${this.BASE}/users/${id}/resend-activation-email`,
  } as const;

  static readonly MODULES = {
    LIST: `${this.BASE}/modules`,
    CREATE: `${this.BASE}/modules/create`,
    GET: (id: string) => `${this.BASE}/modules/${id}`,
    UPDATE: (id: string) => `${this.BASE}/modules/${id}`,
    ACTIVATE: (id: string) => `${this.BASE}/modules/${id}/activate`,
    DEACTIVATE: (id: string) => `${this.BASE}/modules/${id}/deactivate`,
  } as const;
}

export { ApiRoutes };
