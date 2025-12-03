import * as React from 'react';
import { PageHeader } from '@/components/pages/page-header';
import { SidePanel } from '@/components/pages/side-panel';
import { SidebarProvider, SidebarInset } from '@/components/ui/sidebar';
import { useList } from '@/hooks/use-list';
import type { User } from '@/types';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { MoreHorizontal, CheckCircle, Ban, Pencil, ChevronRight, ChevronDown } from 'lucide-react';
import { DataTableColumnHeader } from '@/components/ui/data-table-column-header';
import { ListPagination } from '@/components/ui/list-pagination';
import { Input } from '@/components/ui/input';
import { useDebouncedCallback } from '@/hooks/use-debounced-callback';
import {
  type ColumnDef,
  type SortingState,
  type ExpandedState,
  flexRender,
  getCoreRowModel,
  getExpandedRowModel,
  useReactTable,
} from '@tanstack/react-table';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { ApiRoutes } from '@/lib/api';
import { usePermissions } from '@/hooks/use-permissions';
import { useQueryClient } from '@tanstack/react-query';
import { FormDialog } from '@/components/ui/form-dialog';
import { UserForm } from '@/components/users/user-form';
import { useUserActions } from '@/hooks/use-user-actions';
import { UserActionDialogs } from '@/components/users/user-action-dialogs';

export default function UsersPage() {
  const queryClient = useQueryClient();
  const { isAdmin } = usePermissions();
  const [expanded, setExpanded] = React.useState<ExpandedState>({});

  const { data, setParams, meta } = useList<User>({
    route: ApiRoutes.USERS.LIST,
    queryKey: ['users'],
  });

  const [editingUser, setEditingUser] = React.useState<User | null>(null);
  const [activateDialog, setActivateDialog] = React.useState<{
    open: boolean;
    user: User | null;
  }>({
    open: false,
    user: null,
  });
  const [deactivateDialog, setDeactivateDialog] = React.useState<{
    open: boolean;
    user: User | null;
  }>({
    open: false,
    user: null,
  });

  const { activateUser, deactivateUser } = useUserActions();

  const onSearch = useDebouncedCallback((value: string) => {
    setParams({ search: value });
  }, 500);

  const sorting = React.useMemo<SortingState>(() => {
    return (meta.orders || []).map(o => ({
      id: o.field,
      desc: o.order === 'desc',
    }));
  }, [meta.orders]);

  const columns = React.useMemo<ColumnDef<User>[]>(() => {
    const baseColumns: ColumnDef<User>[] = [
      {
        id: 'expander',
        size: 30,
        header: () => null,
        cell: ({ row }) => {
          return (
            <div className="flex items-center justify-center">
              {row.getIsExpanded() ? (
                <ChevronDown className="h-4 w-4 text-muted-foreground" />
              ) : (
                <ChevronRight className="h-4 w-4 text-muted-foreground" />
              )}
            </div>
          );
        },
      },
      {
        accessorKey: 'image_url',
        header: 'Image',
        size: 60,
        cell: ({ row }) => {
          const username = row.original.username;
          const src = row.original.image_url;
          return (
            <Avatar className="h-8 w-8">
              <AvatarImage src={src || undefined} alt={username} />
              <AvatarFallback>{username.charAt(0).toUpperCase()}</AvatarFallback>
            </Avatar>
          );
        },
      },
      {
        accessorKey: 'username',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Username" />,
      },
      {
        accessorKey: 'email',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Email" />,
      },
      {
        accessorKey: 'roles',
        header: 'Roles',
        cell: ({ row }) => (
          <div className="flex gap-1">
            {row.original.roles.map(role => (
              <Badge key={role} variant="outline" className="text-xs">
                {role.replace('ROLE_', '')}
              </Badge>
            ))}
          </div>
        ),
      },
      {
        accessorKey: 'is_active',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Status" />,
        size: 100,
        cell: ({ row }) => (
          <Badge variant={row.original.is_active ? 'default' : 'secondary'}>
            {row.original.is_active ? 'Active' : 'Not Active'}
          </Badge>
        ),
      },
      {
        accessorKey: 'is_activated',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Activation status" />,
        size: 100,
        cell: ({ row }) => (
          <Badge variant={row.original.is_activated ? 'default' : 'secondary'}>
            {row.original.is_activated ? 'Activated' : 'Not Activated'}
          </Badge>
        ),
      },
      {
        accessorKey: 'created_at',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Created At" />,
        cell: ({ row }) => new Date(row.original.created_at).toLocaleDateString(),
      },
    ];

    if (isAdmin) {
      baseColumns.push({
        id: 'actions',
        size: 50,
        cell: ({ row }) => {
          const user = row.original;

          return (
            <DropdownMenu>
              <DropdownMenuTrigger asChild onClick={e => e.stopPropagation()}>
                <Button variant="table-action" className="h-8 w-8 p-0">
                  <span className="sr-only">Open menu</span>
                  <MoreHorizontal className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" onClick={e => e.stopPropagation()}>
                <DropdownMenuItem onClick={() => setEditingUser(user)}>
                  <Pencil className="mr-2 h-4 w-4" />
                  Update user
                </DropdownMenuItem>
                {user.is_active ? (
                  <DropdownMenuItem
                    onClick={() => setDeactivateDialog({ open: true, user })}
                    className="text-red-600 focus:text-red-600 focus:bg-red-50"
                  >
                    <Ban className="mr-2 h-4 w-4" />
                    Deactivate User
                  </DropdownMenuItem>
                ) : (
                  <DropdownMenuItem
                    onClick={() => setActivateDialog({ open: true, user })}
                    className="text-green-600 focus:text-green-600 focus:bg-green-50"
                  >
                    <CheckCircle className="mr-2 h-4 w-4" />
                    Activate User
                  </DropdownMenuItem>
                )}
              </DropdownMenuContent>
            </DropdownMenu>
          );
        },
      });
    }

    return baseColumns;
  }, [isAdmin]);

  const table = useReactTable({
    data: data?.data || [],
    columns,
    getCoreRowModel: getCoreRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    manualSorting: true,
    manualPagination: true,
    getRowCanExpand: () => true,
    state: {
      sorting,
      expanded,
    },
    onExpandedChange: setExpanded,
    onSortingChange: updater => {
      const newSorting = typeof updater === 'function' ? updater(sorting) : updater;
      setParams({
        orders: newSorting.map(s => ({
          field: s.id,
          order: s.desc ? 'desc' : 'asc',
        })),
      });
    },
  });

  return (
    <SidebarProvider>
      <SidePanel activeItem="users" />
      <SidebarInset>
        <PageHeader title="User List" />
        <div className="flex-1 p-4 flex flex-col gap-4">
          <div className="flex justify-between items-center">
            <div className="flex gap-4 items-center">
              {isAdmin && (
                <FormDialog
                  trigger={<Button>Create User</Button>}
                  title="Create New User"
                  description="Fill in the details below to create a new user."
                >
                  {({ close }) => (
                    <UserForm
                      onSuccess={() => {
                        close();
                        queryClient.invalidateQueries({ queryKey: ['users'] });
                      }}
                    />
                  )}
                </FormDialog>
              )}
            </div>
            <div className="flex items-center gap-4">
              <div className="w-72">
                <Input
                  placeholder="Search users..."
                  defaultValue={meta.search}
                  onChange={e => onSearch(e.target.value)}
                />
              </div>
            </div>
          </div>

          <div className="flex-1 rounded-md border overflow-hidden">
            <div className="h-full overflow-auto">
              <Table>
                <TableHeader>
                  {table.getHeaderGroups().map(headerGroup => (
                    <TableRow key={headerGroup.id}>
                      {headerGroup.headers.map(header => {
                        return (
                          <TableHead
                            key={header.id}
                            style={{
                              width: header.column.columnDef.size,
                            }}
                          >
                            {header.isPlaceholder
                              ? null
                              : flexRender(header.column.columnDef.header, header.getContext())}
                          </TableHead>
                        );
                      })}
                    </TableRow>
                  ))}
                </TableHeader>
                <TableBody>
                  {table.getRowModel().rows?.length ? (
                    table.getRowModel().rows.map(row => (
                      <React.Fragment key={row.id}>
                        <TableRow
                          data-state={row.getIsSelected() && 'selected'}
                          className="cursor-pointer hover:bg-muted/50"
                          onClick={row.getToggleExpandedHandler()}
                        >
                          {row.getVisibleCells().map(cell => (
                            <TableCell key={cell.id}>
                              {flexRender(cell.column.columnDef.cell, cell.getContext())}
                            </TableCell>
                          ))}
                        </TableRow>
                        {row.getIsExpanded() && (
                          <TableRow>
                            <TableCell colSpan={columns.length} className="p-0">
                              <div className="p-4 bg-muted/30 border-t grid grid-cols-[120px_1fr] gap-6">
                                <div className="flex flex-col items-center gap-2">
                                  <Avatar className="h-24 w-24 rounded-lg">
                                    <AvatarImage
                                      src={row.original.image_url || undefined}
                                      alt={row.original.username}
                                    />
                                    <AvatarFallback className="rounded-lg text-2xl">
                                      {row.original.username.charAt(0).toUpperCase()}
                                    </AvatarFallback>
                                  </Avatar>
                                </div>
                                <div className="flex flex-col gap-4">
                                  <div>
                                    <h4 className="text-xl font-bold">{row.original.username}</h4>
                                    <p className="text-muted-foreground mt-1">
                                      {row.original.email}
                                    </p>
                                  </div>

                                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div className="flex flex-col gap-1">
                                      <span className="font-medium text-muted-foreground">
                                        Roles
                                      </span>
                                      <div className="flex flex-wrap gap-1">
                                        {row.original.roles.map(role => (
                                          <Badge key={role} variant="outline">
                                            {role.replace('ROLE_', '')}
                                          </Badge>
                                        ))}
                                      </div>
                                    </div>

                                    <div className="flex flex-col gap-1">
                                      <span className="font-medium text-muted-foreground">
                                        Created By
                                      </span>
                                      <span>{row.original.created_by || '-'}</span>
                                    </div>

                                    <div className="flex flex-col gap-1">
                                      <span className="font-medium text-muted-foreground">
                                        Status
                                      </span>
                                      <div className="flex gap-2">
                                        <Badge
                                          variant={row.original.is_active ? 'default' : 'secondary'}
                                        >
                                          {row.original.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                        {row.original.is_banned && (
                                          <Badge variant="destructive">Banned</Badge>
                                        )}
                                      </div>
                                    </div>

                                    <div className="flex flex-col gap-1">
                                      <span className="font-medium text-muted-foreground">
                                        Last Login
                                      </span>
                                      <span>
                                        {row.original.last_login_at
                                          ? new Date(row.original.last_login_at).toLocaleString()
                                          : '-'}
                                      </span>
                                    </div>

                                    <div className="col-span-2 md:col-span-4 flex flex-col gap-2 mt-2">
                                      <span className="font-medium text-muted-foreground">
                                        Servers
                                      </span>
                                      <div className="flex flex-wrap gap-2">
                                        {row.original.servers?.length > 0 ? (
                                          row.original.servers.map(server => (
                                            <div
                                              key={server.id}
                                              className="flex items-center gap-2 border rounded-md p-2 bg-background"
                                            >
                                              <Avatar className="h-6 w-6">
                                                <AvatarImage
                                                  src={server.image_url || undefined}
                                                  alt={server.name}
                                                />
                                                <AvatarFallback>
                                                  {server.name.charAt(0).toUpperCase()}
                                                </AvatarFallback>
                                              </Avatar>
                                              <span className="text-sm font-medium">
                                                {server.name}
                                              </span>
                                            </div>
                                          ))
                                        ) : (
                                          <span className="text-muted-foreground text-sm italic">
                                            No servers assigned
                                          </span>
                                        )}
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </TableCell>
                          </TableRow>
                        )}
                      </React.Fragment>
                    ))
                  ) : (
                    <TableRow>
                      <TableCell colSpan={columns.length} className="h-24 text-center">
                        No results.
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </div>

          {data?.meta && (
            <ListPagination
              page={data.meta.page}
              totalPages={data.meta.total_pages}
              onPageChange={page => setParams({ page })}
            />
          )}
        </div>

        <UserActionDialogs
          editingUser={editingUser}
          activateDialog={activateDialog}
          deactivateDialog={deactivateDialog}
          onEditClose={() => setEditingUser(null)}
          onActivateClose={() => setActivateDialog({ open: false, user: null })}
          onDeactivateClose={() => setDeactivateDialog({ open: false, user: null })}
          onActivateConfirm={userId => {
            activateUser(userId);
            setActivateDialog({ open: false, user: null });
          }}
          onDeactivateConfirm={userId => {
            deactivateUser(userId);
            setDeactivateDialog({ open: false, user: null });
          }}
          onEditSuccess={() => {
            queryClient.invalidateQueries({ queryKey: ['users'] });
          }}
        />
      </SidebarInset>
    </SidebarProvider>
  );
}
