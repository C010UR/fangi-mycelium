import * as React from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { Check, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { ApiRoutes, fangiFetch } from '@/lib/api';
import { type ListResult, type Server } from '@/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

interface ServerSelectorProps {
  selectedIds: number[];
  onSelect: (server: Server) => void;
  disabled?: boolean;
}

export function ServerSelector({ selectedIds, onSelect, disabled }: ServerSelectorProps) {
  const [open, setOpen] = React.useState(false);
  const [search, setSearch] = React.useState('');

  // Custom debounce implementation
  const [query, setQuery] = React.useState('');
  React.useEffect(() => {
    const timer = setTimeout(() => setQuery(search), 300);
    return () => clearTimeout(timer);
  }, [search]);

  // Infinite scroll intersection observer
  const observerTarget = React.useRef<HTMLDivElement>(null);

  const { data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading } = useInfiniteQuery({
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

  React.useEffect(() => {
    const observer = new IntersectionObserver(
      entries => {
        if (entries[0].isIntersecting && hasNextPage) {
          fetchNextPage();
        }
      },
      { threshold: 0.1 }
    );

    if (observerTarget.current) {
      observer.observe(observerTarget.current);
    }

    return () => observer.disconnect();
  }, [hasNextPage, fetchNextPage]);

  const servers = React.useMemo(() => {
    return data?.pages.flatMap(page => page.data) || [];
  }, [data]);

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className="w-full justify-between"
          disabled={disabled}
        >
          Select servers...
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[400px] p-0" align="start">
        <Command shouldFilter={false}>
          <CommandInput placeholder="Search servers..." value={search} onValueChange={setSearch} />
          <CommandList className="max-h-[300px] overflow-y-auto overflow-x-hidden">
            {isLoading && (
              <div className="p-4 text-sm text-center text-muted-foreground">Loading...</div>
            )}
            {!isLoading && servers.length === 0 && <CommandEmpty>No servers found.</CommandEmpty>}
            <CommandGroup>
              {servers.map(server => {
                const isSelected = selectedIds.includes(server.id);
                return (
                  <CommandItem
                    key={server.id}
                    value={server.id.toString()}
                    onSelect={() => onSelect(server)}
                  >
                    <div className="flex items-center gap-2 w-full">
                      <Check
                        className={cn('mr-2 h-4 w-4', isSelected ? 'opacity-100' : 'opacity-0')}
                      />
                      <Avatar className="h-8 w-8">
                        <AvatarImage src={server.image_url || undefined} alt={server.name} />
                        <AvatarFallback>{server.name.charAt(0).toUpperCase()}</AvatarFallback>
                      </Avatar>
                      <div className="flex flex-col flex-1 min-w-0">
                        <span className="truncate font-medium">{server.name}</span>
                        <span className="text-xs text-muted-foreground truncate">
                          ID: {server.client_id}
                        </span>
                      </div>
                    </div>
                  </CommandItem>
                );
              })}
            </CommandGroup>
            {hasNextPage && (
              <div ref={observerTarget} className="p-2 text-center text-xs text-muted-foreground">
                {isFetchingNextPage ? 'Loading more...' : 'Load more'}
              </div>
            )}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}
