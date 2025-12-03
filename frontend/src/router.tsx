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
import AccountActivatePage from './pages/auth/account-activate';
import ServersPage from './pages/servers';
import ServerDetailPage from './pages/servers/detail';
import ModulesPage from './pages/modules';
import UsersPage from './pages/users';
import ProfilePage from './pages/profile';
import NotFoundPage from './pages/not-found';

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

const beforeLoadUnauthenticated = ({ context }: { context: RouterContext }) => {
  if (context.auth.isMfaPending) {
    throw redirect({ to: '/mfa' });
  }
  if (context.auth.isAuthenticated) {
    throw redirect({ to: '/' });
  }
};

const rootRoute = createRootRouteWithContext<RouterContext>()({
  component: () => <Outlet />,
  notFoundComponent: NotFoundPage,
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
  beforeLoad: beforeLoadUnauthenticated,
  component: ForgotPasswordPage,
});

const resetPasswordRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/password-reset/$token',
  beforeLoad: beforeLoadUnauthenticated,
  component: ResetPasswordPage,
});

const registerRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/register',
  beforeLoad: beforeLoadUnauthenticated,
  component: RegisterPage,
});

const accountRegistrationRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/account-registration/$token',
  beforeLoad: beforeLoadUnauthenticated,
  component: AccountRegistrationPage,
});

const accountActivateRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/account-activation/$token',
  beforeLoad: beforeLoadUnauthenticated,
  component: AccountActivatePage,
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

const serverDetailRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/servers/$serverId',
  beforeLoad: beforeLoadAuthenticated,
  component: ServerDetailPage,
});

const modulesRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/modules',
  beforeLoad: beforeLoadAuthenticated,
  component: ModulesPage,
});

const usersRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/users',
  beforeLoad: beforeLoadAuthenticated,
  component: UsersPage,
});

const profileRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '/profile',
  beforeLoad: beforeLoadAuthenticated,
  component: ProfilePage,
});

const notFoundRoute = createRoute({
  getParentRoute: () => rootRoute,
  path: '*',
  component: NotFoundPage,
});

const routeTree = rootRoute.addChildren([
  loginRoute,
  mfaRoute,
  forgotPasswordRoute,
  resetPasswordRoute,
  registerRoute,
  accountRegistrationRoute,
  accountActivateRoute,
  dashboardRoute,
  serversRoute,
  serverDetailRoute,
  modulesRoute,
  usersRoute,
  profileRoute,
  notFoundRoute,
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
