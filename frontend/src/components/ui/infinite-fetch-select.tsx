import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown } from 'lucide-react';
import * as React from 'react';
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
import { useDebouncedCallback } from '@/hooks/use-debounced-callback';

interface InfiniteFetchSelectProps<T extends { value: string; label: string }> {
  options: T[];
  value?: string;
  values?: string[];
  onChangeValue: (value: string) => void;
  placeholder?: string;
  onLoadMore: () => void;
  isLoading: boolean;
  onSearch: (value: string) => void;
  hasMore?: boolean;
  loadingMessage?: string;
  renderOption?: (option: T, isSelected: boolean) => React.ReactNode;
  renderSelectedItems?: () => React.ReactNode;
  disabled?: boolean;
}

export function InfiniteFetchSelect<T extends { value: string; label: string }>({
  options = [],
  value,
  values = [],
  onChangeValue,
  placeholder = 'Select...',
  onLoadMore,
  isLoading,
  onSearch,
  hasMore = false,
  loadingMessage = 'Loading more...',
  renderOption,
  renderSelectedItems,
  disabled,
}: InfiniteFetchSelectProps<T>) {
  const [open, setOpen] = React.useState(false);
  const [searchTerm, setSearchTerm] = React.useState('');

  const debouncedSearch = useDebouncedCallback((value: string) => {
    onSearch(value);
  }, 300);

  const handleSearch = (value: string) => {
    setSearchTerm(value);
    debouncedSearch(value);
  };

  const selectedOption = options.find(option => option.value === value);

  const onScroll = (e: React.UIEvent<HTMLDivElement>) => {
    const { scrollTop, scrollHeight, clientHeight } = e.currentTarget;
    if (scrollTop + clientHeight >= scrollHeight - 5) {
      if (hasMore && !isLoading) {
        onLoadMore();
      }
    }
  };

  const isSelected = (optionValue: string) => {
    if (values.length > 0) {
      return values.includes(optionValue);
    }
    return value === optionValue;
  };

  return (
    <div className="flex flex-col gap-3 w-full">
      {renderSelectedItems && renderSelectedItems()}
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            role="combobox"
            aria-expanded={open}
            className="w-full justify-between"
            disabled={disabled}
          >
            <span
              className={cn(
                'font-normal text-left truncate',
                !selectedOption && 'text-muted-foreground'
              )}
            >
              {selectedOption ? selectedOption.label : placeholder}
            </span>
            <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
          </Button>
        </PopoverTrigger>
        <PopoverContent
          className="w-[--radix-popover-trigger-width] min-w-[8rem] p-0"
          align="start"
        >
          <Command shouldFilter={false}>
            <CommandInput value={searchTerm} onValueChange={handleSearch} placeholder="Search..." />
            <CommandList onScroll={onScroll} className="max-h-[300px] w-full">
              {!isLoading && options.length === 0 && <CommandEmpty>No results found.</CommandEmpty>}
              <CommandGroup>
                {options.map(option => {
                  const selected = isSelected(option.value);
                  return (
                    <CommandItem
                      key={option.value}
                      value={option.label}
                      onSelect={() => {
                        onChangeValue?.(option.value);
                        if (values.length === 0) {
                          setOpen(false);
                        }
                      }}
                      className="pr-8 pl-2"
                    >
                      {renderOption ? (
                        renderOption(option, selected)
                      ) : (
                        <>
                          {option.label}
                          <span className="absolute right-2 flex h-3.5 w-3.5 items-center justify-center">
                            <Check
                              className={cn('h-4 w-4', selected ? 'opacity-100' : 'opacity-0')}
                            />
                          </span>
                        </>
                      )}
                    </CommandItem>
                  );
                })}
                {isLoading && (
                  <div className="py-2 text-center text-sm text-muted-foreground">
                    {loadingMessage}
                  </div>
                )}
              </CommandGroup>
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>
    </div>
  );
}
