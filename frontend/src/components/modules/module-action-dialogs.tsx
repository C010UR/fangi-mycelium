import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { FormDialog } from '@/components/ui/form-dialog';
import { ModuleForm } from '@/components/modules/module-form';
import { CheckCircle2Icon, XCircleIcon } from 'lucide-react';
import type { Module } from '@/types';

interface ModuleActionDialogsProps {
  editingModule: Module | null;
  activateDialog: { open: boolean; module: Module | null };
  deactivateDialog: { open: boolean; module: Module | null };
  onEditClose: () => void;
  onActivateClose: () => void;
  onDeactivateClose: () => void;
  onActivateConfirm: (moduleId: string) => void;
  onDeactivateConfirm: (moduleId: string) => void;
  onEditSuccess: () => void;
}

export function ModuleActionDialogs({
  editingModule,
  activateDialog,
  deactivateDialog,
  onEditClose,
  onActivateClose,
  onDeactivateClose,
  onActivateConfirm,
  onDeactivateConfirm,
  onEditSuccess,
}: ModuleActionDialogsProps) {
  return (
    <>
      <FormDialog
        open={!!editingModule}
        onOpenChange={open => !open && onEditClose()}
        title="Update Module"
        description="Update module details."
      >
        {({ close }) =>
          editingModule && (
            <ModuleForm
              initialData={editingModule}
              onSuccess={() => {
                close();
                onEditSuccess();
              }}
            />
          )
        }
      </FormDialog>

      <AlertDialog open={activateDialog.open} onOpenChange={open => !open && onActivateClose()}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Activate Module</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to activate <strong>{activateDialog.module?.name}</strong>?
            </AlertDialogDescription>
          </AlertDialogHeader>
          <Alert>
            <CheckCircle2Icon />
            <AlertTitle>Module will be activated</AlertTitle>
            <AlertDescription>
              This module will be available for servers to use in your application.
            </AlertDescription>
          </Alert>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (activateDialog.module) {
                  onActivateConfirm(String(activateDialog.module.id));
                }
              }}
              className="bg-green-600 hover:bg-green-700"
            >
              Activate
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <AlertDialog open={deactivateDialog.open} onOpenChange={open => !open && onDeactivateClose()}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Deactivate Module</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to deactivate <strong>{deactivateDialog.module?.name}</strong>?
            </AlertDialogDescription>
          </AlertDialogHeader>
          <Alert variant="destructive">
            <XCircleIcon />
            <AlertTitle>Module access will be revoked</AlertTitle>
            <AlertDescription>Servers will no longer be able to use this module.</AlertDescription>
          </Alert>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (deactivateDialog.module) {
                  onDeactivateConfirm(String(deactivateDialog.module.id));
                }
              }}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              Deactivate
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}
