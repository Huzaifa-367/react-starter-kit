import React from 'react';
import { useForm, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';

export default function MagicLink({ status }: { status?: string }) {
  const { data, setData, post, processing, errors } = useForm({
    email: '',
  });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    post('/auth/magic-link');
  };

  return (
    <>
      <Head title="Magic Link Sign In" />

      {status ? (
        <div className="flex flex-col gap-4 text-center">
          <div className="rounded-xl bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-950/20 dark:text-emerald-300 border border-emerald-100 dark:border-emerald-900/50">
            <p className="text-sm font-medium">{status}</p>
          </div>
          <TextLink href={login()} className="text-sm">
            Back to log in
          </TextLink>
        </div>
      ) : (
        <form onSubmit={submit} className="flex flex-col gap-6">
          <div className="grid gap-6">
            <div className="grid gap-2">
              <Label htmlFor="email">Email address</Label>
              <Input
                id="email"
                type="email"
                required
                autoFocus
                tabIndex={1}
                autoComplete="email"
                placeholder="email@example.com"
                value={data.email}
                onChange={e => setData('email', e.target.value)}
              />
              <InputError message={errors.email} />
            </div>

            <Button
              type="submit"
              className="mt-2 w-full"
              tabIndex={2}
              disabled={processing}
            >
              {processing && <Spinner />}
              Send Magic Link
            </Button>
          </div>

          <div className="text-center text-sm text-muted-foreground">
            Remember your password?{' '}
            <TextLink href={login()} tabIndex={3}>
              Log in
            </TextLink>
          </div>
        </form>
      )}
    </>
  );
}

MagicLink.layout = {
  title: 'Passwordless Sign In',
  description: 'Enter your email address and we will mail you a link to log in instantly',
};
