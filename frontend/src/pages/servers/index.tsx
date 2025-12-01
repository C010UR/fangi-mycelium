import * as React from 'react';
import { PageHeader } from '@/components/pages/page-header';
import { SidePanel } from '@/components/pages/side-panel';
import { SidebarProvider, SidebarInset } from '@/components/ui/sidebar';
import { useList } from '@/hooks/use-list';
import type { Server } from '@/types';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { MoreHorizontal, CheckCircle, Ban, Pencil, Key } from 'lucide-react';
import { DataTableColumnHeader } from '@/components/ui/data-table-column-header';
import { ListPagination } from '@/components/ui/list-pagination';
import { Input } from '@/components/ui/input';
import { useDebouncedCallback } from '@/hooks/use-debounced-callback';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/components/ui/hover-card';
import {
  type ColumnDef,
  type SortingState,
  flexRender,
  getCoreRowModel,
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
import { toast } from 'sonner';
import { ApiRoutes } from '@/lib/api';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { fangiFetch } from '@/lib/api/fetch';
import { FormDialog } from '@/components/ui/form-dialog';
import { ServerForm } from '@/components/servers/server-form';

export default function ServersPage() {
  const queryClient = useQueryClient();
  const { data, setParams, meta } = useList<Server>({
    route: ApiRoutes.SERVER.LIST,
    queryKey: ['servers'],
  });

  const [editingServer, setEditingServer] = React.useState<Server | null>(null);

  const { mutate: activateServer } = useMutation({
    mutationFn: (id: string) =>
      fangiFetch({
        route: ApiRoutes.SERVER.ACTIVATE(id),
        method: 'GET',
        useCredentials: true,
      }),
    onSuccess: () => {
      toast.success('Server activated successfully');
      queryClient.invalidateQueries({ queryKey: ['servers'] });
    },
    onError: () => {
      toast.error('Failed to activate server');
    },
  });

  const { mutate: deactivateServer } = useMutation({
    mutationFn: (id: string) =>
      fangiFetch({
        route: ApiRoutes.SERVER.DEACTIVATE(id),
        method: 'GET',
        useCredentials: true,
      }),
    onSuccess: () => {
      toast.success('Server deactivated successfully');
      queryClient.invalidateQueries({ queryKey: ['servers'] });
    },
    onError: () => {
      toast.error('Failed to deactivate server');
    },
  });

  const { mutate: generateSecret } = useMutation({
    mutationFn: (id: string) =>
      fangiFetch({
        route: ApiRoutes.SERVER.GENERATE_SECRET(id),
        method: 'GET',
        useCredentials: true,
      }),
    onSuccess: () => {
      toast.success('Secret was sent to your email inbox');
      queryClient.invalidateQueries({ queryKey: ['servers'] });
    },
    onError: () => {
      toast.error('Failed to generate secret');
    },
  });

  const onSearch = useDebouncedCallback((value: string) => {
    setParams({ search: value });
  }, 500);

  const sorting = React.useMemo<SortingState>(() => {
    return (meta.orders || []).map(o => ({
      id: o.field,
      desc: o.order === 'desc',
    }));
  }, [meta.orders]);

  const columns = React.useMemo<ColumnDef<Server>[]>(
    () => [
      {
        accessorKey: 'image_url',
        header: 'Image',
        size: 60,
        cell: ({ row }) => {
          const name = row.original.name;
          const src = row.original.image_url;
          return (
            <Avatar className="h-8 w-8">
              <AvatarImage src={src || undefined} alt={name} />
              <AvatarFallback>{name.charAt(0).toUpperCase()}</AvatarFallback>
            </Avatar>
          );
        },
      },
      {
        accessorKey: 'name',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Name" />,
      },
      {
        accessorKey: 'urls',
        header: 'Allowed URLs',
        size: 250,
        cell: ({ row }) => {
          const urls = row.original.urls || [];
          const maxVisible = 2;

          if (urls.length === 0) {
            return <div className="text-muted-foreground">-</div>;
          }

          if (urls.length <= maxVisible) {
            return <div className="truncate">{urls.join('; ')}</div>;
          }

          return (
            <div>
              <HoverCard>
                <HoverCardTrigger asChild>
                  <span className="cursor-pointer underline decoration-dotted truncate">
                    {urls.slice(0, maxVisible).join('; ')} (+
                    {urls.length - maxVisible})
                  </span>
                </HoverCardTrigger>
                <HoverCardContent className="w-80" align="start">
                  <div className="flex flex-col gap-2">
                    <h4 className="font-medium leading-none">Allowed URLs</h4>
                    <div className="text-sm text-muted-foreground">
                      <ul className="space-y-1">
                        {urls.map((url, i) => (
                          <li key={i} className="break-all">
                            {url}
                          </li>
                        ))}
                      </ul>
                    </div>
                  </div>
                </HoverCardContent>
              </HoverCard>
            </div>
          );
        },
      },
      {
        accessorKey: 'client_id',
        header: 'Client ID',
      },
      {
        accessorKey: 'is_active',
        header: 'Active',
        size: 100,
        cell: ({ row }) => (
          <Badge variant={row.original.is_active ? 'default' : 'secondary'}>
            {row.original.is_active ? 'Active' : 'Inactive'}
          </Badge>
        ),
      },
      {
        accessorKey: 'created_by',
        header: 'Created By',
      },
      {
        accessorKey: 'created_at',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Created At" />,
        cell: ({ row }) => new Date(row.original.created_at).toLocaleDateString(),
      },
      {
        accessorKey: 'updated_at',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Updated At" />,
        cell: ({ row }) => new Date(row.original.updated_at).toLocaleDateString(),
      },
      {
        id: 'actions',
        size: 50,
        cell: ({ row }) => {
          const server = row.original;
          return (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-8 w-8 p-0">
                  <span className="sr-only">Open menu</span>
                  <MoreHorizontal className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => setEditingServer(server)}>
                  <Pencil className="mr-2 h-4 w-4" />
                  Update server
                </DropdownMenuItem>
                {server.is_active ? (
                  <DropdownMenuItem
                    onClick={() => deactivateServer(String(server.id))}
                    className="text-red-600 focus:text-red-600 focus:bg-red-50"
                  >
                    <Ban className="mr-2 h-4 w-4" />
                    Deactivate Server
                  </DropdownMenuItem>
                ) : (
                  <DropdownMenuItem
                    onClick={() => activateServer(String(server.id))}
                    className="text-green-600 focus:text-green-600 focus:bg-green-50"
                  >
                    <CheckCircle className="mr-2 h-4 w-4" />
                    Activate Server
                  </DropdownMenuItem>
                )}
                <DropdownMenuItem onClick={() => generateSecret(String(server.id))}>
                  <Key className="mr-2 h-4 w-4" />
                  Generate Secret
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          );
        },
      },
    ],
    [activateServer, deactivateServer]
  );

  const table = useReactTable({
    data: data?.data || [],
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualSorting: true,
    manualPagination: true,
    state: {
      sorting,
    },
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
      <SidePanel activeItem="servers" />
      <SidebarInset>
        <PageHeader title="Server List" />
        <div className="flex-1 p-4 flex flex-col gap-4">
          <div className="flex justify-between items-center">
            <div className="flex gap-2">
              <FormDialog
                trigger={<Button>Create Server</Button>}
                title="Create New Server"
                description="Fill in the details below to create a new server."
              >
                {({ close }) => (
                  <ServerForm
                    onSuccess={() => {
                      close();
                      queryClient.invalidateQueries({ queryKey: ['servers'] });
                    }}
                  />
                )}
              </FormDialog>
            </div>
            <div className="w-72">
              <Input
                placeholder="Search servers..."
                defaultValue={meta.search}
                onChange={e => onSearch(e.target.value)}
              />
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
                      <TableRow key={row.id} data-state={row.getIsSelected() && 'selected'}>
                        {row.getVisibleCells().map(cell => (
                          <TableCell key={cell.id}>
                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                          </TableCell>
                        ))}
                      </TableRow>
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

          {/* Pagination */}
          {data?.meta && (
            <ListPagination
              page={data.meta.page}
              totalPages={data.meta.total_pages}
              onPageChange={page => setParams({ page })}
            />
          )}
        </div>

        {/* Edit Server Dialog */}
        <FormDialog
          open={!!editingServer}
          onOpenChange={open => !open && setEditingServer(null)}
          title="Update Server"
          description="Update server details."
        >
          {({ close }) =>
            editingServer && (
              <ServerForm
                initialData={editingServer}
                onSuccess={() => {
                  close();
                  queryClient.invalidateQueries({ queryKey: ['servers'] });
                }}
              />
            )
          }
        </FormDialog>
      </SidebarInset>
    </SidebarProvider>
  );
}
