import * as React from 'react';
import { PageHeader } from '@/components/pages/page-header';
import { SidePanel } from '@/components/pages/side-panel';
import { SidebarProvider, SidebarInset } from '@/components/ui/sidebar';
import { useList } from '@/hooks/use-list';
import type { Module } from '@/types';
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
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/components/ui/hover-card';
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
import { Code } from '@/components/ui/code';
import { usePermissions } from '@/hooks/use-permissions';
import { useAuth } from '@/lib/auth/context';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useQueryClient } from '@tanstack/react-query';
import { FormDialog } from '@/components/ui/form-dialog';
import { ModuleForm } from '@/components/modules/module-form';
import { useModuleActions } from '@/hooks/use-module-actions';
import { ModuleActionDialogs } from '@/components/modules/module-action-dialogs';

export default function ModulesPage() {
  const queryClient = useQueryClient();
  const { isAdmin } = usePermissions();
  const { user } = useAuth();
  const [expanded, setExpanded] = React.useState<ExpandedState>({});
  const [showMine, setShowMine] = React.useState(false);
  const { data, setParams, meta } = useList<Module>({
    route: ApiRoutes.MODULES.LIST,
    queryKey: ['modules'],
  });

  const [editingModule, setEditingModule] = React.useState<Module | null>(null);
  const [activateDialog, setActivateDialog] = React.useState<{
    open: boolean;
    module: Module | null;
  }>({
    open: false,
    module: null,
  });
  const [deactivateDialog, setDeactivateDialog] = React.useState<{
    open: boolean;
    module: Module | null;
  }>({
    open: false,
    module: null,
  });

  const { activateModule, deactivateModule } = useModuleActions();

  React.useEffect(() => {
    if (user && meta.filters) {
      const ownerFilter = meta.filters.find(
        f => f.field === 'owner' && f.operator === 'eq' && f.value === user.id.toString()
      );
      setShowMine(!!ownerFilter);
    }
  }, [user, meta.filters]);

  const onSearch = useDebouncedCallback((value: string) => {
    setParams({ search: value });
  }, 500);

  const handleShowMineChange = (checked: boolean) => {
    setShowMine(checked);
    if (checked && user) {
      setParams({
        filters: [
          {
            field: 'owner',
            operator: 'eq',
            value: user.id.toString(),
          },
        ],
      });
    } else {
      setParams({ filters: [] });
    }
  };

  const sorting = React.useMemo<SortingState>(() => {
    return (meta.orders || []).map(o => ({
      id: o.field,
      desc: o.order === 'desc',
    }));
  }, [meta.orders]);

  const columns = React.useMemo<ColumnDef<Module>[]>(() => {
    const baseColumns: ColumnDef<Module>[] = [
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
    ];

    if (isAdmin) {
      baseColumns.push(
        {
          accessorKey: 'urls',
          header: 'Allowed URLs',
          size: 250,
          cell: ({ row }) => {
            const urls = row.original.urls || [];
            const maxVisible = 1;

            if (urls.length === 0) {
              return (
                <div className="text-muted-foreground">
                  <Code>-</Code>
                </div>
              );
            }

            if (urls.length <= maxVisible) {
              return (
                <div className="truncate">
                  {urls.map(url => (
                    <Code copy key={url}>
                      {url}
                    </Code>
                  ))}
                </div>
              );
            }

            return (
              <div>
                <HoverCard>
                  <HoverCardTrigger asChild onClick={e => e.stopPropagation()}>
                    <div>
                      {urls.slice(0, maxVisible).map(url => (
                        <Code copy key={url}>
                          {url}
                        </Code>
                      ))}
                      <span className="text-muted-foreground ml-1">
                        + {urls.length - maxVisible}
                      </span>
                    </div>
                  </HoverCardTrigger>
                  <HoverCardContent align="start">
                    <div className="flex flex-col gap-2">
                      <h4 className="font-medium leading-none">Allowed URLs</h4>
                      <div className="text-sm text-muted-foreground">
                        <ul className="space-y-1">
                          {urls.map((url, i) => (
                            <li key={i} className="break-all">
                              <Code copy>{url}</Code>
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
          header: 'Client ID',
          cell: ({ row }) => <Code copy>{row.original.client_id}</Code>,
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
        }
      );
    }

    baseColumns.push(
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
      }
    );

    if (isAdmin) {
      baseColumns.push({
        id: 'actions',
        size: 50,
        cell: ({ row }) => {
          const module = row.original;

          if (module.created_by_entity.id !== user?.id) {
            return null;
          }

          return (
            <DropdownMenu>
              <DropdownMenuTrigger asChild onClick={e => e.stopPropagation()}>
                <Button variant="table-action" className="h-8 w-8 p-0">
                  <span className="sr-only">Open menu</span>
                  <MoreHorizontal className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" onClick={e => e.stopPropagation()}>
                <DropdownMenuItem onClick={() => setEditingModule(module)}>
                  <Pencil className="mr-2 h-4 w-4" />
                  Update module
                </DropdownMenuItem>
                {module.is_active ? (
                  <DropdownMenuItem
                    onClick={() => setDeactivateDialog({ open: true, module })}
                    className="text-red-600 focus:text-red-600 focus:bg-red-50"
                  >
                    <Ban className="mr-2 h-4 w-4" />
                    Deactivate Module
                  </DropdownMenuItem>
                ) : (
                  <DropdownMenuItem
                    onClick={() => setActivateDialog({ open: true, module })}
                    className="text-green-600 focus:text-green-600 focus:bg-green-50"
                  >
                    <CheckCircle className="mr-2 h-4 w-4" />
                    Activate Module
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
      <SidePanel activeItem="modules" />
      <SidebarInset>
        <PageHeader title="Module List" />
        <div className="flex-1 p-4 flex flex-col gap-4">
          <div className="flex justify-between items-center">
            <div className="flex gap-4 items-center">
              {isAdmin && (
                <FormDialog
                  trigger={<Button>Create Module</Button>}
                  title="Create New Module"
                  description="Fill in the details below to create a new module."
                >
                  {({ close }) => (
                    <ModuleForm
                      onSuccess={() => {
                        close();
                        queryClient.invalidateQueries({ queryKey: ['modules'] });
                      }}
                    />
                  )}
                </FormDialog>
              )}
            </div>
            <div className="flex items-center gap-4">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="show-mine"
                  checked={showMine}
                  onCheckedChange={handleShowMineChange}
                />
                <Label htmlFor="show-mine" className="cursor-pointer text-sm font-medium">
                  Show owned
                </Label>
              </div>
              <div className="w-72">
                <Input
                  placeholder="Search modules..."
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
                                      alt={row.original.name}
                                    />
                                    <AvatarFallback className="rounded-lg text-2xl">
                                      {row.original.name.charAt(0).toUpperCase()}
                                    </AvatarFallback>
                                  </Avatar>
                                </div>
                                <div className="flex flex-col gap-4">
                                  <div>
                                    <h4 className="text-xl font-bold">{row.original.name}</h4>
                                    <p className="text-muted-foreground mt-1">
                                      {row.original.description || 'No description provided.'}
                                    </p>
                                  </div>

                                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    {isAdmin && (
                                      <>
                                        <div className="flex flex-col gap-1">
                                          <span className="font-medium text-muted-foreground">
                                            Client ID
                                          </span>
                                          <Code copy>{row.original.client_id}</Code>
                                        </div>

                                        <div className="flex flex-col gap-1">
                                          <span className="font-medium text-muted-foreground">
                                            Allowed URLs
                                          </span>
                                          <div className="flex flex-col gap-1">
                                            {row.original.urls && row.original.urls.length > 0 ? (
                                              row.original.urls.map((url, i) => (
                                                <Code key={i} copy>
                                                  {url}
                                                </Code>
                                              ))
                                            ) : (
                                              <span>-</span>
                                            )}
                                          </div>
                                        </div>
                                      </>
                                    )}

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

        <ModuleActionDialogs
          editingModule={editingModule}
          activateDialog={activateDialog}
          deactivateDialog={deactivateDialog}
          onEditClose={() => setEditingModule(null)}
          onActivateClose={() => setActivateDialog({ open: false, module: null })}
          onDeactivateClose={() => setDeactivateDialog({ open: false, module: null })}
          onActivateConfirm={moduleId => {
            activateModule(moduleId);
            setActivateDialog({ open: false, module: null });
          }}
          onDeactivateConfirm={moduleId => {
            deactivateModule(moduleId);
            setDeactivateDialog({ open: false, module: null });
          }}
          onEditSuccess={() => {
            queryClient.invalidateQueries({ queryKey: ['modules'] });
          }}
        />
      </SidebarInset>
    </SidebarProvider>
  );
}
