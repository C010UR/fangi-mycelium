import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { fangiFetch } from '@/lib/api/fetch';
import { ApiRoutes } from '@/lib/api';

interface UseUserActionsOptions {
  onActivateSuccess?: () => void;
  onDeactivateSuccess?: () => void;
}

export function useUserActions(options: UseUserActionsOptions = {}) {
  const queryClient = useQueryClient();

  const activateUser = useMutation({
    mutationFn: (id: string) =>
      fangiFetch({
        route: ApiRoutes.USERS.ACTIVATE(id),
        method: 'GET',
        useCredentials: true,
      }),
    onSuccess: () => {
      toast.success('User activated successfully');
      queryClient.invalidateQueries({ queryKey: ['users'] });
      options.onActivateSuccess?.();
    },
    onError: () => {
      toast.error('Failed to activate user');
    },
  });

  const deactivateUser = useMutation({
    mutationFn: (id: string) =>
      fangiFetch({
        route: ApiRoutes.USERS.DEACTIVATE(id),
        method: 'GET',
        useCredentials: true,
      }),
    onSuccess: () => {
      toast.success('User deactivated successfully');
      queryClient.invalidateQueries({ queryKey: ['users'] });
      options.onDeactivateSuccess?.();
    },
    onError: () => {
      toast.error('Failed to deactivate user');
    },
  });

  return {
    activateUser: activateUser.mutate,
    deactivateUser: deactivateUser.mutate,
    isActivating: activateUser.isPending,
    isDeactivating: deactivateUser.isPending,
  };
}
