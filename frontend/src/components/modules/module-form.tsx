'use client';

import * as React from 'react';
import { useFieldArray, useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2, Plus, Trash, Upload, X } from 'lucide-react';
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
import { Textarea } from '@/components/ui/textarea';
import {
  FileUpload,
  FileUploadDropzone,
  FileUploadList,
  FileUploadItem,
  FileUploadItemPreview,
  FileUploadItemMetadata,
  FileUploadItemDelete,
} from '@/components/ui/file-upload';
import type { Module } from '@/types';
import { ApiRoutes, fangiFetch, FetchError } from '@/lib/api';

const formSchema = z.object({
  name: z.string().min(3, 'Name must be at least 3 characters').max(255),
  description: z.string().max(4096, 'Description must be at most 4096 characters').optional(),
  image: z.array(z.instanceof(File)).max(1, 'Only one image is allowed').optional(),
  urls: z
    .array(
      z.object({
        value: z
          .string()
          .regex(
            /^https?:\/\/([a-zA-Z0-9.-]+|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})(:\d{1,5})?$/,
            'URL must follow format: http(s)://domain-or-ip(:port)'
          ),
      })
    )
    .min(1, 'At least one URL is required')
    .max(10, 'At most 10 URLs are allowed')
    .refine(urls => {
      const values = urls.map(url => url.value);
      return new Set(values).size === values.length;
    }, 'All URLs must be unique'),
});

type FormValues = z.infer<typeof formSchema>;

interface ModuleFormProps {
  initialData?: Module;
  onSuccess?: (module: Module) => void;
}

export function ModuleForm({ initialData, onSuccess }: ModuleFormProps) {
  const [isPending, setIsPending] = React.useState(false);

  const defaultValues: Partial<FormValues> = {
    name: initialData?.name || '',
    description: initialData?.description || '',
    urls: initialData?.urls?.map(url => ({ value: url })) || [{ value: '' }],
    image: [],
  };

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues,
  });

  const { fields, append, remove } = useFieldArray({
    control: form.control,
    name: 'urls',
  });

  async function onSubmit(data: FormValues) {
    setIsPending(true);
    try {
      const urls = data.urls?.map(item => item.value) || [];
      const imageFile = data.image?.[0] || null;

      const payload = {
        name: data.name,
        description: data.description || null,
        image: imageFile,
        urls: urls,
      };

      let module: Module;
      if (initialData) {
        module = await fangiFetch({
          route: ApiRoutes.MODULES.UPDATE(initialData.id.toString()),
          method: 'POST',
          contentType: 'multipart/form-data',
          body: payload,
          useCredentials: true,
        });
        toast.success('Module updated successfully');
      } else {
        if (!payload.name || !payload.urls) {
          throw new Error('Missing required fields');
        }
        module = await fangiFetch({
          route: ApiRoutes.MODULES.CREATE,
          method: 'POST',
          contentType: 'multipart/form-data',
          body: payload,
          useCredentials: true,
        });
        toast.success('Module created successfully');
      }

      if (onSuccess) {
        onSuccess(module);
      }
    } catch (error) {
      if (error instanceof FetchError) {
        error.errors.reverse().forEach(errorMsg => toast.error(errorMsg));
      } else if (error instanceof Error) {
        toast.error(error.message);
      } else {
        toast.error('Failed to create module');
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
          name="name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Module Name</FormLabel>
              <FormControl>
                <Input placeholder="My Module" {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="description"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Description</FormLabel>
              <FormControl>
                <Textarea
                  placeholder="Describe what this module does..."
                  className="resize-none"
                  rows={4}
                  {...field}
                />
              </FormControl>
              <FormDescription>
                Provide a brief description of your module (optional).
              </FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="image"
          render={({ field: { value, onChange, ...field } }) => (
            <FormItem>
              <FormLabel>Module Image</FormLabel>
              {initialData?.image_url && !value?.length && (
                <div className="mb-2">
                  <img
                    src={initialData.image_url}
                    alt="Current module image"
                    className="h-32 w-32 rounded-md object-cover"
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
              <FormDescription>Upload an image for your module (optional).</FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="urls"
          render={() => (
            <FormItem className="space-y-4">
              <div className="flex items-center justify-between">
                <FormLabel>List of URLs allowed for this module</FormLabel>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  disabled={fields.length >= 10}
                  onClick={() => append({ value: '' })}
                >
                  <Plus className="mr-2 h-4 w-4" />
                  Add URL
                </Button>
              </div>
              <div className="space-y-2">
                {fields.map((field, index) => (
                  <FormField
                    key={field.id}
                    control={form.control}
                    name={`urls.${index}.value`}
                    render={({ field }) => (
                      <FormItem>
                        <div className="flex items-center gap-2">
                          <FormControl>
                            <Input placeholder="https://example.com" {...field} />
                          </FormControl>
                          <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            onClick={() => remove(index)}
                            disabled={fields.length <= 1}
                          >
                            <Trash className="h-4 w-4 text-destructive" />
                          </Button>
                        </div>
                        <FormMessage />
                      </FormItem>
                    )}
                  />
                ))}
              </div>
              <FormMessage />
              {(form.formState.errors.urls as any)?.root?.message && (
                <p className="text-[0.8rem] font-medium text-destructive">
                  {(form.formState.errors.urls as any).root.message}
                </p>
              )}
            </FormItem>
          )}
        />

        <Button type="submit" disabled={isPending}>
          {isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          {initialData ? 'Update Module' : 'Create Module'}
        </Button>
      </form>
    </Form>
  );
}
