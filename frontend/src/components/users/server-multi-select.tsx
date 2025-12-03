import * as React from 'react';
import { X, Check } from 'lucide-react';
import { type ServerShort, type Server, type ListResult } from '@/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { InfiniteFetchSelect } from '@/components/ui/infinite-fetch-select';
import { useInfiniteQuery } from '@tanstack/react-query';
import { ApiRoutes, fangiFetch } from '@/lib/api';
import { cn } from '@/lib/utils';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';

interface ServerMultiSelectProps {
  value: ServerShort[];
  onChange: (value: ServerShort[]) => void;
  disabled?: boolean;
}

export function ServerMultiSelect({ value = [], onChange, disabled }: ServerMultiSelectProps) {
  const handleRemove = (serverId: number) => {
    onChange(value.filter(s => s.id !== serverId));
  };

  const selectedIds = React.useMemo(() => value.map(v => v.id), [value]);

  const [query, setQuery] = React.useState('');

  const { data, fetchNextPage, hasNextPage, isLoading } = useInfiniteQuery({
    queryKey: ['servers', 'active', query],
    queryFn: async ({ pageParam = 1 }) => {
      return fangiFetch<ListResult<Server>>({
        route: ApiRoutes.SERVER.LIST_ACTIVE,
        params: {
          q: query,
          page: pageParam,
          page_size: 20,
        },
        useCredentials: true,
      });
    },
    getNextPageParam: lastPage => {
      if (lastPage.meta.page < lastPage.meta.total_pages) {
        return lastPage.meta.page + 1;
      }
      return undefined;
    },
    initialPageParam: 1,
  });

  const options = React.useMemo(() => {
    const servers = data?.pages.flatMap(page => page.data) || [];
    return servers.map(server => ({
      value: server.id.toString(),
      label: server.name,
      ...server,
    }));
  }, [data]);

  const handleSelectOption = (idStr: string) => {
    const id = parseInt(idStr);
    const option = options.find(o => o.value === idStr);

    const isSelected = value.some(v => v.id === id);
    if (isSelected) {
      onChange(value.filter(v => v.id !== id));
    } else {
      if (option) {
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        const { value: _v, label: _l, ...serverData } = option;
        onChange([...value, serverData]);
      }
    }
  };

  const renderSelectedItems = () => {
    if (value.length === 0) return null;

    return (
      <div className="rounded-md border">
        <Table>
          <TableBody>
            {value.map(server => (
              <TableRow key={server.id}>
                <TableCell className="py-2 w-[40px]">
                  <Avatar className="h-8 w-8">
                    <AvatarImage src={server.image_url || undefined} alt={server.name} />
                    <AvatarFallback>{server.name.charAt(0).toUpperCase()}</AvatarFallback>
                  </Avatar>
                </TableCell>
                <TableCell className="py-2">
                  <div className="flex flex-col flex-1 min-w-0">
                    <span className="truncate font-medium">{server.name}</span>
                    <span className="text-xs text-muted-foreground truncate">
                      ID: {server.client_id}
                    </span>
                  </div>
                </TableCell>
                <TableCell className="py-2 w-[40px]">
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => handleRemove(server.id)}
                    className="h-8 w-8 text-muted-foreground hover:text-foreground"
                    disabled={disabled}
                  >
                    <X className="h-4 w-4" />
                    <span className="sr-only">Remove {server.name}</span>
                  </Button>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    );
  };

  return (
    <InfiniteFetchSelect
      options={options}
      values={selectedIds.map(id => id.toString())}
      onChangeValue={handleSelectOption}
      placeholder="Select servers..."
      onLoadMore={fetchNextPage}
      isLoading={isLoading}
      onSearch={setQuery}
      hasMore={hasNextPage}
      loadingMessage="Loading more servers..."
      disabled={disabled}
      renderSelectedItems={renderSelectedItems}
      renderOption={(option, isSelected) => (
        <div className="flex items-center gap-2 w-full">
          <Check className={cn('mr-2 h-4 w-4', isSelected ? 'opacity-100' : 'opacity-0')} />
          <Avatar className="h-8 w-8">
            <AvatarImage src={option.image_url || undefined} alt={option.name} />
            <AvatarFallback>{option.name.charAt(0).toUpperCase()}</AvatarFallback>
          </Avatar>
          <div className="flex flex-col flex-1 min-w-0">
            <span className="truncate font-medium">{option.name}</span>
            <span className="text-xs text-muted-foreground truncate">ID: {option.client_id}</span>
          </div>
        </div>
      )}
    />
  );
}
