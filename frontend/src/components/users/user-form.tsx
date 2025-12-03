'use client';

import * as React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2, Upload, X } from 'lucide-react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  FileUpload,
  FileUploadDropzone,
  FileUploadList,
  FileUploadItem,
  FileUploadItemPreview,
  FileUploadItemMetadata,
  FileUploadItemDelete,
} from '@/components/ui/file-upload';
import { ServerMultiSelect } from './server-multi-select';
import type { User, ServerShort } from '@/types';
import { ApiRoutes, fangiFetch, FetchError } from '@/lib/api';

const formSchema = z.object({
  username: z.string().min(3, 'Username must be at least 3 characters').max(255),
  email: z.email('Invalid email address'),
  role: z.enum(['ROLE_USER', 'ROLE_ADMIN'] as const),
  image: z.array(z.instanceof(File)).max(1, 'Only one image is allowed').optional(),
  servers: z
    .array(
      z.object({
        id: z.number(),
        name: z.string(),
      })
    )
    .min(1, 'At least one server is required'),
});

type FormValues = z.infer<typeof formSchema>;

interface UserFormProps {
  initialData?: User;
  onSuccess?: (user: User) => void;
}

export function UserForm({ initialData, onSuccess }: UserFormProps) {
  const [isPending, setIsPending] = React.useState(false);

  const defaultValues: FormValues = {
    username: initialData?.username || '',
    email: initialData?.email || '',
    role: initialData?.roles.includes('ROLE_ADMIN') ? 'ROLE_ADMIN' : 'ROLE_USER',
    image: undefined,
    servers: (initialData?.servers as unknown as FormValues['servers']) || [],
  };

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues,
  });

  async function onSubmit(data: FormValues) {
    setIsPending(true);
    try {
      const imageFile = data.image?.[0] || null;

      const basePayload = {
        username: data.username,
        roles: [data.role],
        image: imageFile,
        servers: (data.servers as unknown as ServerShort[])?.map(s => s.id) || [],
      };

      let user: User;
      if (initialData) {
        user = await fangiFetch({
          route: ApiRoutes.USERS.UPDATE(initialData.id.toString()),
          method: 'POST',
          contentType: 'multipart/form-data',
          body: basePayload,
          useCredentials: true,
        });
        toast.success('User updated successfully');
      } else {
        user = await fangiFetch({
          route: ApiRoutes.USERS.CREATE,
          method: 'POST',
          contentType: 'multipart/form-data',
          body: { ...basePayload, email: data.email },
          useCredentials: true,
        });
        toast.success('User created successfully');
      }

      if (onSuccess) {
        onSuccess(user);
      }
    } catch (error) {
      if (error instanceof FetchError) {
        error.errors.reverse().forEach(errorMsg => toast.error(errorMsg));
      } else if (error instanceof Error) {
        toast.error(error.message);
      } else {
        toast.error('Failed to save user');
      }
    } finally {
      setIsPending(false);
    }
  }

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
        <FormField
          control={form.control}
          name="username"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Username</FormLabel>
              <FormControl>
                <Input placeholder="jdoe" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        {!initialData && (
          <FormField
            control={form.control}
            name="email"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Email</FormLabel>
                <FormControl>
                  <Input placeholder="john.doe@example.com" type="email" {...field} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        )}

        <FormField
          control={form.control}
          name="role"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Role</FormLabel>
              <Select onValueChange={field.onChange} defaultValue={field.value}>
                <FormControl>
                  <SelectTrigger>
                    <SelectValue placeholder="Select a role" />
                  </SelectTrigger>
                </FormControl>
                <SelectContent>
                  <SelectItem value="ROLE_USER">User</SelectItem>
                  <SelectItem value="ROLE_ADMIN">Admin</SelectItem>
                </SelectContent>
              </Select>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="servers"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Servers</FormLabel>
              <FormControl>
                <ServerMultiSelect
                  value={field.value as unknown as ServerShort[]}
                  onChange={field.onChange}
                  disabled={isPending}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="image"
          render={({ field: { value, onChange, ...field } }) => (
            <FormItem>
              <FormLabel>Profile Image</FormLabel>
              {initialData?.image_url && !value?.length && (
                <div className="mb-2">
                  <img
                    src={initialData.image_url}
                    alt="Current profile image"
                    className="h-32 w-32 rounded-full object-cover"
                  />
                </div>
              )}
              <FormControl>
                <FileUpload
                  accept="image/*"
                  maxSize={8 * 1024 * 1024} // 8MB
                  maxFiles={1}
                  value={value || []}
                  onValueChange={onChange}
                  disabled={isPending}
                  {...field}
                >
                  <FileUploadDropzone className="cursor-pointer h-32">
                    <div className="flex flex-col items-center gap-2">
                      <Upload className="h-8 w-8 text-muted-foreground" />
                      <div className="text-center">
                        <p className="text-sm font-medium">Click to upload or drag and drop</p>
                        <p className="text-xs text-muted-foreground">PNG, JPG, GIF up to 8MB</p>
                      </div>
                    </div>
                  </FileUploadDropzone>
                  <FileUploadList>
                    {value?.map(file => (
                      <FileUploadItem key={file.name} value={file}>
                        <FileUploadItemPreview />
                        <FileUploadItemMetadata />
                        <FileUploadItemDelete>
                          <X className="h-4 w-4" />
                        </FileUploadItemDelete>
                      </FileUploadItem>
                    ))}
                  </FileUploadList>
                </FileUpload>
              </FormControl>
              <FormDescription>Upload a profile picture (optional).</FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />

        <Button type="submit" disabled={isPending}>
          {isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          {initialData ? 'Update User' : 'Create User'}
        </Button>
      </form>
    </Form>
  );
}
