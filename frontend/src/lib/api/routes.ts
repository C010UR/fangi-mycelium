import type { MFAType } from '@/types';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api/v1';

class ApiRoutes {
  private static readonly BASE = API_BASE_URL;

  static readonly AUTH = {
    LOGIN: `${this.BASE}/login`,
    LOGOUT: `${this.BASE}/logout`,
    PROFILE: `${this.BASE}/profile`,
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

  static readonly MODULES = {
    LIST: `${this.BASE}/modules`,
  } as const;
}

export { ApiRoutes };
