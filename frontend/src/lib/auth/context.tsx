import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import {
  ApiRoutes,
  setAuthToken,
  clearAuthToken,
  getAuthToken,
  setMfaPending,
  clearMfaPending,
  isMfaPending as checkMfaPending,
  getMfaToken,
  fangiFetch,
} from '@/lib/api';
import type {
  AuthResponse,
  MfaAvailableResponse,
  MfaSendRequestResponse,
  MfaVerifyRequest,
  MFAType,
  User,
} from '@/types';

interface LoginCredentials {
  username: string;
  password: string;
}

export interface AuthState {
  isAuthenticated: boolean;
  isMfaPending: boolean;
  isLoading: boolean;
  user: User | null;
  login: (credentials: LoginCredentials) => Promise<AuthResponse>;
  logout: () => Promise<void>;
  verifyMfa: (data: MfaVerifyRequest) => Promise<AuthResponse>;
  fetchMfaAvailable: () => Promise<MfaAvailableResponse>;
  sendMfaRequest: (method: MFAType) => Promise<MfaSendRequestResponse>;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthState | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [isMfaPending, setIsMfaPending] = useState(checkMfaPending());
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    const validateSession = async () => {
      const token = getAuthToken();
      const mfaPending = checkMfaPending();

      if (mfaPending) {
        setIsMfaPending(true);
        setIsLoading(false);
        return;
      }

      if (token) {
        try {
          const userData: User = await fangiFetch({
            route: ApiRoutes.AUTH.PROFILE,
            useCredentials: true,
          });
          setUser(userData);
          setIsAuthenticated(true);
        } catch {
          clearAuthToken();
          setIsAuthenticated(false);
          setUser(null);
        }
      }

      setIsLoading(false);
    };

    validateSession();
  }, []);

  const login = useCallback(async (credentials: LoginCredentials): Promise<AuthResponse> => {
    const data: AuthResponse = await fangiFetch({
      route: ApiRoutes.AUTH.LOGIN,
      method: 'POST',
      body: credentials,
    });

    if (data.mfa_required) {
      setMfaPending(data.token);
      setIsMfaPending(true);
    } else {
      clearMfaPending();
      setAuthToken(data.token);
      setIsMfaPending(false);
      setIsAuthenticated(true);

      try {
        const userData: User = await fangiFetch({
          route: ApiRoutes.AUTH.PROFILE,
          useCredentials: true,
        });
        setUser(userData);
      } catch {
        // Profile fetch failed, but login succeeded
      }
    }

    return data;
  }, []);

  const logout = useCallback(async () => {
    try {
      await fangiFetch({
        route: ApiRoutes.AUTH.LOGOUT,
        method: 'GET',
        useCredentials: true,
      });
    } catch {
      // Logout request failed, but we still clear local state
    } finally {
      clearAuthToken();
      clearMfaPending();
      setUser(null);
      setIsAuthenticated(false);
      setIsMfaPending(false);
    }
  }, []);

  const verifyMfa = useCallback(async (data: MfaVerifyRequest): Promise<AuthResponse> => {
    const mfaToken = getMfaToken();
    const headers: Record<string, string> = {};

    if (mfaToken) {
      headers['Authorization'] = `Bearer ${mfaToken}`;
    }

    const authData: AuthResponse = await fangiFetch({
      route: ApiRoutes.MFA.VERIFY,
      method: 'POST',
      body: data,
      headers,
    });

    if (authData.mfa_required) {
      setMfaPending(authData.token);
      setIsMfaPending(true);
    } else {
      clearMfaPending();
      setAuthToken(authData.token);
      setIsMfaPending(false);
      setIsAuthenticated(true);

      try {
        const userData: User = await fangiFetch({
          route: ApiRoutes.AUTH.PROFILE,
          useCredentials: true,
        });
        setUser(userData);
      } catch {
        // Profile fetch failed, but MFA verification succeeded
      }
    }

    return authData;
  }, []);

  const fetchMfaAvailable = useCallback(async (): Promise<MfaAvailableResponse> => {
    const mfaToken = getMfaToken();
    const headers: Record<string, string> = {};

    if (mfaToken) {
      headers['Authorization'] = `Bearer ${mfaToken}`;
    }

    return fangiFetch({
      route: ApiRoutes.MFA.AVAILABLE,
      method: 'GET',
      headers,
    });
  }, []);

  const sendMfaRequest = useCallback(async (method: MFAType): Promise<MfaSendRequestResponse> => {
    const mfaToken = getMfaToken();
    const headers: Record<string, string> = {};

    if (mfaToken) {
      headers['Authorization'] = `Bearer ${mfaToken}`;
    }

    return fangiFetch({
      route: ApiRoutes.MFA.SEND_REQUEST(method),
      method: 'POST',
      headers,
    });
  }, []);

  const refreshUser = useCallback(async () => {
    try {
      const userData: User = await fangiFetch({
        route: ApiRoutes.AUTH.PROFILE,
        useCredentials: true,
      });
      setUser(userData);
    } catch {
      // If refresh fails, we might want to logout or just ignore if it's transient
    }
  }, []);

  return (
    <AuthContext.Provider
      value={{
        isAuthenticated,
        isMfaPending,
        isLoading,
        user,
        login,
        logout,
        verifyMfa,
        fetchMfaAvailable,
        sendMfaRequest,
        refreshUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthState {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
