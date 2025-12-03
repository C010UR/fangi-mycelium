import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Link } from '@tanstack/react-router';
import { useMutation } from '@tanstack/react-query';

import { AlertCircle, CheckCircle, Upload, X } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
  TwoPaneCard,
  TwoPaneCardContent,
  TwoPaneCardHeader,
  TwoPaneCardTitle,
  TwoPaneCardDescription,
  TwoPaneCardBody,
  TwoPaneCardFooter,
} from '@/components/pages/two-pane-card';
import { Input } from '@/components/ui/input';
import { PasswordInput } from '@/components/ui/password-input';
import {
  FileUpload,
  FileUploadDropzone,
  FileUploadList,
  FileUploadItem,
  FileUploadItemPreview,
  FileUploadItemMetadata,
  FileUploadItemDelete,
} from '@/components/ui/file-upload';
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { ApiRoutes, fangiFetch, FetchError } from '@/lib/api';
import type { ApiSuccess } from '@/types';

const registrationSchema = z.object({
  email: z
    .email('Please enter a valid email address')
    .min(1, 'Email is required')
    .max(180, 'Email must be less than 180 characters'),
  username: z
    .string()
    .min(3, 'Username must be at least 3 characters')
    .max(255, 'Username must be less than 255 characters'),
  password: z
    .string()
    .min(1, 'Password is required')
    .min(8, 'Password must be at least 8 characters')
    .regex(/[0-9]/, 'Password must contain at least one digit')
    .regex(/[!@#$%^&*(),.?":{}|<>]/, 'Password must contain at least one special character'),
  image: z.instanceof(File).optional().nullable(),
});

type RegistrationFormValues = z.infer<typeof registrationSchema>;

function RegisterPage() {
  const form = useForm<RegistrationFormValues>({
    resolver: zodResolver(registrationSchema),
    defaultValues: {
      email: '',
      username: '',
      password: '',
      image: undefined,
    },
  });

  const registerMutation = useMutation({
    mutationFn: (data: RegistrationFormValues) =>
      fangiFetch<ApiSuccess, RegistrationFormValues>({
        route: ApiRoutes.REGISTER.POST,
        method: 'POST',
        contentType: 'multipart/form-data',
        body: data,
      }),
  });

  function onSubmit(values: RegistrationFormValues) {
    registerMutation.mutate(values);
  }

  const errorMessages =
    registerMutation.error instanceof FetchError
      ? registerMutation.error.errors
      : [registerMutation.error?.message ?? 'Failed to register account'];

  if (registerMutation.isSuccess) {
    const response = registerMutation.data;
    const isSuccess = !response.error;

    return (
      <div className="min-h-screen flex items-center justify-center bg-background p-4">
        <TwoPaneCard imageSrc="/card-image.jpeg" imageAlt="Mycelium artwork">
          <TwoPaneCardContent>
            <TwoPaneCardHeader>
              <TwoPaneCardTitle>
                {isSuccess ? 'Registration successful' : 'Registration failed'}
              </TwoPaneCardTitle>
              <TwoPaneCardDescription>
                {isSuccess
                  ? 'Your account has been created successfully'
                  : 'There was an issue with your registration'}
              </TwoPaneCardDescription>
            </TwoPaneCardHeader>

            <TwoPaneCardBody>
              <div className="space-y-4">
                <Alert variant={isSuccess ? 'default' : 'destructive'}>
                  {isSuccess ? (
                    <CheckCircle className="h-4 w-4" />
                  ) : (
                    <AlertCircle className="h-4 w-4" />
                  )}
                  <AlertDescription>
                    {isSuccess
                      ? response.message ||
                        'Account registered successfully. Please check your email for the activation link.'
                      : response.error_description ||
                        'Registration request is invalid. Please try again.'}
                  </AlertDescription>
                </Alert>
              </div>
            </TwoPaneCardBody>

            <TwoPaneCardFooter>
              <Link
                to="/login"
                className="w-full inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
              >
                Sign in
              </Link>
            </TwoPaneCardFooter>
          </TwoPaneCardContent>
        </TwoPaneCard>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <TwoPaneCard imageSrc="/card-image.jpeg" imageAlt="Mycelium artwork" className="h-[800px]">
        <TwoPaneCardContent>
          <TwoPaneCardHeader>
            <TwoPaneCardTitle>Create your account</TwoPaneCardTitle>
            <TwoPaneCardDescription>
              Join us and start your journey with Fangi
            </TwoPaneCardDescription>
          </TwoPaneCardHeader>

          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)}>
              <TwoPaneCardBody>
                {registerMutation.isError && (
                  <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>
                      {errorMessages.length === 1 ? (
                        errorMessages[0]
                      ) : (
                        <ul className="list-disc list-inside space-y-1">
                          {errorMessages.map((msg, idx) => (
                            <li key={idx}>{msg}</li>
                          ))}
                        </ul>
                      )}
                    </AlertDescription>
                  </Alert>
                )}

                <FormField
                  control={form.control}
                  name="image"
                  render={({ field: { value, onChange, ...field } }) => (
                    <FormItem>
                      <FormLabel>Profile Image</FormLabel>
                      <FormControl>
                        <FileUpload
                          accept="image/*"
                          maxSize={8 * 1024 * 1024} // 5MB
                          maxFiles={1}
                          value={value ? [value] : []}
                          onValueChange={files => onChange(files[0] || undefined)}
                          disabled={registerMutation.isPending}
                          {...field}
                        >
                          <FileUploadDropzone className="cursor-pointer">
                            <div className="flex flex-col items-center gap-2">
                              <Upload className="h-8 w-8 text-muted-foreground" />
                              <div className="text-center">
                                <p className="text-sm font-medium">
                                  Click to upload or drag and drop
                                </p>
                                <p className="text-xs text-muted-foreground">
                                  PNG, JPG, GIF up to 8MB
                                </p>
                              </div>
                            </div>
                          </FileUploadDropzone>
                          <FileUploadList>
                            {value && (
                              <FileUploadItem value={value}>
                                <FileUploadItemPreview />
                                <FileUploadItemMetadata />
                                <FileUploadItemDelete>
                                  <X className="h-4 w-4" />
                                </FileUploadItemDelete>
                              </FileUploadItem>
                            )}
                          </FileUploadList>
                        </FileUpload>
                      </FormControl>
                      <FormMessage />
                      <p className="text-xs text-muted-foreground">
                        Upload a profile image (optional, max 8MB)
                      </p>
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="username"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Username</FormLabel>
                      <FormControl>
                        <Input
                          placeholder="Choose a username (3-255 characters)"
                          autoComplete="username"
                          disabled={registerMutation.isPending}
                          {...field}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="email"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Email</FormLabel>
                      <FormControl>
                        <Input
                          placeholder="Enter your email"
                          type="email"
                          autoComplete="email"
                          disabled={registerMutation.isPending}
                          {...field}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="password"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Password</FormLabel>
                      <FormControl>
                        <PasswordInput
                          placeholder="Create a password"
                          autoComplete="new-password"
                          disabled={registerMutation.isPending}
                          {...field}
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <Button type="submit" className="w-full" disabled={registerMutation.isPending}>
                  {registerMutation.isPending ? 'Creating account...' : 'Create account'}
                </Button>
              </TwoPaneCardBody>
            </form>
          </Form>

          <TwoPaneCardFooter>
            <p className="text-center text-sm text-muted-foreground">
              Already have an account?{' '}
              <Link
                to="/login"
                className="font-medium text-primary hover:text-primary/80 transition-colors"
              >
                Sign in
              </Link>
            </p>
          </TwoPaneCardFooter>
        </TwoPaneCardContent>
      </TwoPaneCard>
    </div>
  );
}

export default RegisterPage;
