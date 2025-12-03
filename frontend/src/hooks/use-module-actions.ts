import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { fangiFetch } from '@/lib/api/fetch';
import { ApiRoutes } from '@/lib/api';

interface UseModuleActionsOptions {
  onActivateSuccess?: () => void;
  onDeactivateSuccess?: () => void;
}

export function useModuleActions(options: UseModuleActionsOptions = {}) {
  const queryClient = useQueryClient();

  const activateModule = useMutation({
    mutationFn: (id: string) =>
      fangiFetch({
        route: ApiRoutes.MODULES.ACTIVATE(id),
        method: 'GET',
        useCredentials: true,
      }),
    onSuccess: () => {
      toast.success('Module activated successfully');
      queryClient.invalidateQueries({ queryKey: ['modules'] });
      options.onActivateSuccess?.();
    },
    onError: () => {
      toast.error('Failed to activate module');
    },
  });

  const deactivateModule = useMutation({
    mutationFn: (id: string) =>
      fangiFetch({
        route: ApiRoutes.MODULES.DEACTIVATE(id),
        method: 'GET',
        useCredentials: true,
      }),
    onSuccess: () => {
      toast.success('Module deactivated successfully');
      queryClient.invalidateQueries({ queryKey: ['modules'] });
      options.onDeactivateSuccess?.();
    },
    onError: () => {
      toast.error('Failed to deactivate module');
    },
  });

  return {
    activateModule: activateModule.mutate,
    deactivateModule: deactivateModule.mutate,
    isActivating: activateModule.isPending,
    isDeactivating: deactivateModule.isPending,
  };
}
