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
import { UserForm } from '@/components/users/user-form';
import { CheckCircle2Icon, XCircleIcon } from 'lucide-react';
import type { User } from '@/types';

interface UserActionDialogsProps {
  editingUser: User | null;
  activateDialog: { open: boolean; user: User | null };
  deactivateDialog: { open: boolean; user: User | null };
  onEditClose: () => void;
  onActivateClose: () => void;
  onDeactivateClose: () => void;
  onActivateConfirm: (userId: string) => void;
  onDeactivateConfirm: (userId: string) => void;
  onEditSuccess: () => void;
}

export function UserActionDialogs({
  editingUser,
  activateDialog,
  deactivateDialog,
  onEditClose,
  onActivateClose,
  onDeactivateClose,
  onActivateConfirm,
  onDeactivateConfirm,
  onEditSuccess,
}: UserActionDialogsProps) {
  return (
    <>
      <FormDialog
        open={!!editingUser}
        onOpenChange={open => !open && onEditClose()}
        title="Update User"
        description="Update user details."
      >
        {({ close }) =>
          editingUser && (
            <UserForm
              initialData={editingUser}
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
            <AlertDialogTitle>Activate User</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to activate <strong>{activateDialog.user?.username}</strong>?
            </AlertDialogDescription>
          </AlertDialogHeader>
          <Alert>
            <CheckCircle2Icon />
            <AlertTitle>User will be activated</AlertTitle>
            <AlertDescription>
              This user will be able to log in and access the system.
            </AlertDescription>
          </Alert>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (activateDialog.user) {
                  onActivateConfirm(String(activateDialog.user.id));
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
            <AlertDialogTitle>Deactivate User</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to deactivate <strong>{deactivateDialog.user?.username}</strong>
              ?
            </AlertDialogDescription>
          </AlertDialogHeader>
          <Alert variant="destructive">
            <XCircleIcon />
            <AlertTitle>User access will be revoked</AlertTitle>
            <AlertDescription>The user will no longer be able to log in.</AlertDescription>
          </Alert>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (deactivateDialog.user) {
                  onDeactivateConfirm(String(deactivateDialog.user.id));
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
