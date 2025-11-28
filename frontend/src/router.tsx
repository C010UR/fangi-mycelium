import {
  createRouter,
  createRootRouteWithContext,
  createRoute,
  redirect,
  Outlet,
} from '@tanstack/react-router';
import LoginPage from './pages/auth/login';
import MfaPage from './pages/auth/mfa';
import ForgotPasswordPage from './pages/auth/forgot-password';
import ResetPasswordPage from './pages/auth/forgot-password/reset';
import RegisterPage from './pages/auth/register';
import AccountRegistrationPage from './pages/auth/register/reset';
import ServersPage from './pages/servers';

interface AuthContext {
  isAuthenticated: boolean;
  isMfaPending: boolean;
}

interface RouterContext {
  auth: AuthContext;
}

const beforeLoadAuthenticated = ({ context }: { context: RouterContext }) => {
  if (context.auth.isMfaPending) {
    throw redirect({ to: '/mfa' });
  }
  if (!context.auth.isAuthenticated) {
    throw redirect({ to: '/login' });
  }
};

const rootRoute = createRootRouteWithContext<RouterContext>()({
  component: () => <Outlet />,
});

const loginRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/login',
  beforeLoad: ({ context }) => {
    if (context.auth.isMfaPending) {
      throw redirect({ to: '/mfa' });
    }
    if (context.auth.isAuthenticated) {
      throw redirect({ to: '/' });
    }
  },
  component: LoginPage,
});

const mfaRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/mfa',
  beforeLoad: ({ context }) => {
    if (!context.auth.isMfaPending) {
      throw redirect({ to: '/login' });
    }
  },
  component: MfaPage,
});

const forgotPasswordRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/forgot-password',
  beforeLoad: beforeLoadAuthenticated,
  component: ForgotPasswordPage,
});

const resetPasswordRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/password-reset/$token',
  beforeLoad: beforeLoadAuthenticated,
  component: ResetPasswordPage,
});

const registerRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/register',
  beforeLoad: beforeLoadAuthenticated,
  component: RegisterPage,
});

const accountRegistrationRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/account-registration/$token',
  beforeLoad: beforeLoadAuthenticated,
  component: AccountRegistrationPage,
});

const dashboardRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/',
  beforeLoad: () => {
    throw redirect({ to: '/servers' });
  },
});

const serversRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/servers',
  beforeLoad: beforeLoadAuthenticated,
  component: ServersPage,
});

const routeTree = rootRoute.addChildren([
  loginRoute,
  mfaRoute,
  forgotPasswordRoute,
  resetPasswordRoute,
  registerRoute,
  accountRegistrationRoute,
  dashboardRoute,
  serversRoute,
]);

export const createAppRouter = (context: RouterContext) =>
  createRouter({
    routeTree,
    context,
  });

export type AppRouter = ReturnType<typeof createAppRouter>;

declare module '@tanstack/react-router' {
  interface Register {
    router: AppRouter;
  }
}
