import { Link } from '@tanstack/react-router';
import { LogOut, ChevronsUpDown, Building2, Users, Puzzle, User } from 'lucide-react';

import { useAuth } from '@/lib/auth/context';
import type { UserRole } from '@/types';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
  Sidebar,
  SidebarHeader,
  SidebarContent,
  SidebarMenu,
  SidebarMenuItem,
  SidebarMenuButton,
  SidebarFooter,
} from '@/components/ui/sidebar';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePermissions } from '@/hooks/use-permissions';

const ROLE_LABELS: Record<UserRole, string> = {
  ROLE_USER: 'User',
  ROLE_ADMIN: 'Admin',
};

interface SidePanelProps {
  activeItem?: 'servers' | 'modules' | 'users' | 'settings' | 'profile';
}

function SidePanel({ activeItem = 'servers' }: SidePanelProps) {
  const { isAdmin } = usePermissions();

  return (
    <Sidebar variant="inset">
      <SidebarHeader className="border-b p-4">
        <div className="flex items-center gap-2">
          <img src="/logo.svg" alt="Fangi logo" className="h-12 w-12" />
          <span className="text-2xl font-semibold tracking-tight text-foreground">Fangi</span>
        </div>
      </SidebarHeader>
      <SidebarContent>
        <SidebarMenu className="p-2">
          <SidebarMenuItem>
            <SidebarMenuButton asChild isActive={activeItem === 'servers'} tooltip="Servers">
              <Link to="/servers">
                <Building2 />
                <span>Servers</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <SidebarMenuItem>
            <SidebarMenuButton asChild isActive={activeItem === 'modules'} tooltip="Modules">
              <Link to="/modules">
                <Puzzle />
                <span>Modules</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
          {isAdmin && (
            <SidebarMenuItem>
              <SidebarMenuButton asChild isActive={activeItem === 'users'} tooltip="Users">
                <Link to="/users">
                  <Users />
                  <span>Users</span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          )}
        </SidebarMenu>
      </SidebarContent>
      <SidebarFooter className="border-t p-3">
        <SidebarUserProfile />
      </SidebarFooter>
    </Sidebar>
  );
}

function SidebarUserProfile() {
  const { user, logout } = useAuth();

  const getUserInitials = (username: string): string => {
    return username
      .split(/[\s._-]+/)
      .slice(0, 2)
      .map(part => part.charAt(0).toUpperCase())
      .join('');
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <button className="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sidebar-ring">
          <Avatar className="size-9">
            {user?.image_url && <AvatarImage src={user.image_url} alt={user.username} />}
            <AvatarFallback className="bg-sidebar-primary text-sidebar-primary-foreground text-xs font-medium">
              {user?.username ? getUserInitials(user.username) : '?'}
            </AvatarFallback>
          </Avatar>
          <div className="flex-1 overflow-hidden">
            <p className="truncate text-sm font-medium">{user?.username ?? 'User'}</p>
            <p className="truncate text-xs text-sidebar-foreground/60">{user?.email ?? ''}</p>
          </div>
          <ChevronsUpDown className="size-4 shrink-0 text-sidebar-foreground/60" />
        </button>
      </DropdownMenuTrigger>
      <DropdownMenuContent side="top" align="start" className="w-54">
        <DropdownMenuLabel className="font-normal">
          <div className="flex flex-col space-y-2">
            <div>
              <p className="text-sm font-medium">{user?.username}</p>
              <p className="text-xs text-muted-foreground">{user?.email}</p>
            </div>
            {user?.roles && user.roles.length > 0 && (
              <div className="flex flex-wrap gap-1">
                {user.roles.map(role => (
                  <Badge
                    key={role}
                    variant={role === 'ROLE_ADMIN' ? 'default' : 'secondary'}
                    className="text-[10px] px-1.5 py-0"
                  >
                    {ROLE_LABELS[role]}
                  </Badge>
                ))}
              </div>
            )}
          </div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuItem asChild>
          <Link to="/profile" className="flex items-center gap-2 cursor-pointer w-full">
            <User className="h-4 w-4" />
            <span>Profile</span>
          </Link>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem variant="destructive" onClick={logout} className="cursor-pointer">
          <LogOut className="h-4 w-4" />
          <span>Log out</span>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export { SidePanel, SidebarUserProfile };
