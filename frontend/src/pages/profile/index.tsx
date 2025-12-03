import { useState, useRef } from 'react';
import { SidebarProvider, SidebarInset } from '@/components/ui/sidebar';
import { SidePanel } from '@/components/pages/side-panel';
import { PageHeader } from '@/components/pages/page-header';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { useAuth } from '@/lib/auth/context';
import type { UserRole, User } from '@/types';
import { Spinner } from '@/components/ui/spinner';
import { Button } from '@/components/ui/button';
import { Check, Edit, Loader2, X, Camera } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { ApiRoutes, fangiFetch, FetchError } from '@/lib/api';
import { toast } from 'sonner';

const ROLE_LABELS: Record<UserRole, string> = {
  ROLE_USER: 'User',
  ROLE_ADMIN: 'Admin',
};

export default function ProfilePage() {
  const { user, isLoading, refreshUser } = useAuth();

  const [isEditingUsername, setIsEditingUsername] = useState(false);
  const [username, setUsername] = useState('');
  const [isUpdatingUsername, setIsUpdatingUsername] = useState(false);

  const [isUpdatingImage, setIsUpdatingImage] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center">
        <Spinner />
      </div>
    );
  }

  if (!user) {
    return <div>User not found</div>;
  }

  // Initialize username state when entering edit mode
  const handleEditUsername = () => {
    setUsername(user.username);
    setIsEditingUsername(true);
  };

  const handleCancelEditUsername = () => {
    setIsEditingUsername(false);
    setUsername('');
  };

  const handleSaveUsername = async () => {
    if (!username || username.trim() === '' || username === user.username) {
      setIsEditingUsername(false);
      return;
    }

    setIsUpdatingUsername(true);
    try {
      await fangiFetch<User>({
        route: ApiRoutes.AUTH.UPDATE_PROFILE,
        method: 'POST',
        body: { username },
        useCredentials: true,
      });

      toast.success('Username updated successfully');
      refreshUser();
      setIsEditingUsername(false);
    } catch (error) {
      if (error instanceof FetchError) {
        toast.error(error.message || 'Failed to update username');
      } else {
        toast.error('An unexpected error occurred');
      }
    } finally {
      setIsUpdatingUsername(false);
    }
  };

  const handleImageClick = () => {
    fileInputRef.current?.click();
  };

  const handleImageChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    e.target.value = '';

    setIsUpdatingImage(true);
    try {
      await fangiFetch<User>({
        route: ApiRoutes.AUTH.UPDATE_PROFILE,
        method: 'POST',
        contentType: 'multipart/form-data',
        body: { image: file },
        useCredentials: true,
      });

      toast.success('Profile image updated successfully');
      refreshUser();
    } catch (error) {
      if (error instanceof FetchError) {
        toast.error(error.message || 'Failed to update profile image');
      } else {
        toast.error('An unexpected error occurred');
      }
    } finally {
      setIsUpdatingImage(false);
    }
  };

  return (
    <SidebarProvider>
      <SidePanel activeItem="profile" />
      <SidebarInset>
        <PageHeader title="Profile" />
        <div className="flex-1 p-4 flex flex-col gap-6">
          <div className="rounded-lg border p-4">
            <div className="flex items-start justify-between mb-4">
              <div className="flex items-start gap-4">
                <div className="relative group">
                  <Avatar
                    className="h-16 w-16 cursor-pointer"
                    onClick={!isUpdatingImage ? handleImageClick : undefined}
                  >
                    <AvatarImage
                      src={user.image_url || undefined}
                      alt={user.username}
                      className={isUpdatingImage ? 'opacity-50' : ''}
                    />
                    <AvatarFallback>{user.username.charAt(0).toUpperCase()}</AvatarFallback>
                  </Avatar>

                  {isUpdatingImage && (
                    <div className="absolute inset-0 flex items-center justify-center">
                      <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                    </div>
                  )}

                  {!isUpdatingImage && (
                    <div
                      className="absolute inset-0 flex items-center justify-center bg-black/40 rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer"
                      onClick={handleImageClick}
                    >
                      <Camera className="h-6 w-6 text-white" />
                    </div>
                  )}

                  <input
                    type="file"
                    ref={fileInputRef}
                    className="hidden"
                    accept="image/*"
                    onChange={handleImageChange}
                    disabled={isUpdatingImage}
                  />
                </div>

                <div className="flex flex-col gap-2 min-w-[200px]">
                  {isEditingUsername ? (
                    <div className="flex items-center gap-2">
                      <Input
                        value={username}
                        onChange={e => setUsername(e.target.value)}
                        className="h-8 w-40"
                        disabled={isUpdatingUsername}
                        autoFocus
                        onKeyDown={e => {
                          if (e.key === 'Enter') handleSaveUsername();
                          if (e.key === 'Escape') handleCancelEditUsername();
                        }}
                      />
                      <Button
                        size="icon"
                        variant="ghost"
                        className="h-8 w-8 text-green-600 hover:text-green-700 hover:bg-green-100"
                        onClick={handleSaveUsername}
                        disabled={isUpdatingUsername}
                      >
                        {isUpdatingUsername ? (
                          <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                          <Check className="h-4 w-4" />
                        )}
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        className="h-8 w-8 text-red-600 hover:text-red-700 hover:bg-red-100"
                        onClick={handleCancelEditUsername}
                        disabled={isUpdatingUsername}
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    </div>
                  ) : (
                    <div className="flex items-center gap-2 group">
                      <h2 className="text-xl font-bold">{user.username}</h2>
                      <Button
                        size="icon"
                        variant="ghost"
                        className="h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity"
                        onClick={handleEditUsername}
                      >
                        <Edit className="h-3 w-3 text-muted-foreground" />
                      </Button>
                    </div>
                  )}
                  <div className="flex flex-col gap-1 text-sm text-muted-foreground">
                    {user.email}
                  </div>
                </div>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="flex flex-col gap-3">
                <div className="grid grid-cols-[100px_1fr] items-start gap-2">
                  <span className="font-medium text-sm text-muted-foreground pt-1">Roles</span>
                  <div className="flex flex-wrap gap-2">
                    {user.roles.map(role => (
                      <Badge key={role} variant={role === 'ROLE_ADMIN' ? 'default' : 'secondary'}>
                        {ROLE_LABELS[role]}
                      </Badge>
                    ))}
                  </div>
                </div>

                <div className="grid grid-cols-[100px_1fr] items-center gap-2">
                  <span className="font-medium text-sm text-muted-foreground">Status</span>
                  <div className="flex gap-2">
                    <Badge variant={user.is_active ? 'default' : 'secondary'} className="w-fit">
                      {user.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                    {user.is_banned && <Badge variant="destructive">Banned</Badge>}
                  </div>
                </div>

                <div className="grid grid-cols-[100px_1fr] items-center gap-2">
                  <span className="font-medium text-sm text-muted-foreground">Created By</span>
                  <span className="text-sm">{user.created_by || '-'}</span>
                </div>
              </div>

              <div className="flex flex-col gap-3">
                <div className="grid grid-cols-[100px_1fr] items-center gap-2">
                  <span className="font-medium text-sm text-muted-foreground">Created At</span>
                  <span className="text-sm">{new Date(user.created_at).toLocaleString()}</span>
                </div>

                <div className="grid grid-cols-[100px_1fr] items-center gap-2">
                  <span className="font-medium text-sm text-muted-foreground">Updated At</span>
                  <span className="text-sm">{new Date(user.updated_at).toLocaleString()}</span>
                </div>

                <div className="grid grid-cols-[100px_1fr] items-center gap-2">
                  <span className="font-medium text-sm text-muted-foreground">Last Login</span>
                  <span className="text-sm">
                    {user.last_login_at ? new Date(user.last_login_at).toLocaleString() : '-'}
                  </span>
                </div>
              </div>
            </div>

            <div className="flex flex-col gap-2 mt-4 pt-4 border-t">
              <span className="font-medium text-sm text-muted-foreground">Servers</span>
              <div className="flex flex-wrap gap-2">
                {user.servers?.length > 0 ? (
                  user.servers.map(server => (
                    <div
                      key={server.id}
                      className="flex items-center gap-2 border rounded-md p-2 bg-background"
                    >
                      <Avatar className="h-6 w-6">
                        <AvatarImage src={server.image_url || undefined} alt={server.name} />
                        <AvatarFallback>{server.name.charAt(0).toUpperCase()}</AvatarFallback>
                      </Avatar>
                      <span className="text-sm font-medium">{server.name}</span>
                    </div>
                  ))
                ) : (
                  <span className="text-muted-foreground text-sm italic">No servers assigned</span>
                )}
              </div>
            </div>
          </div>
        </div>
      </SidebarInset>
    </SidebarProvider>
  );
}
