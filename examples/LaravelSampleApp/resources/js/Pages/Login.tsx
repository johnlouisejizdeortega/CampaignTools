import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({ password: '' });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-background p-6">
            <Head title="Sign in" />
            {/* Subtle ambient gradient for depth without clutter. */}
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-[radial-gradient(60rem_60rem_at_110%_-10%,hsl(var(--muted))_0,transparent_55%),radial-gradient(50rem_50rem_at_-10%_110%,hsl(var(--muted))_0,transparent_55%)]"
            />
            <div className="relative w-full max-w-sm">
                <div className="mb-6 flex items-center justify-center gap-2.5 font-semibold tracking-tight">
                    <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-[0.7rem] font-bold text-primary-foreground">
                        Ads
                    </span>
                    <span>Google Ads Dashboard</span>
                </div>
                <Card className="shadow-lg">
                    <CardHeader>
                        <CardTitle><Lock className="h-5 w-5 text-muted-foreground" /> Sign in</CardTitle>
                        <CardDescription>Enter the team password to continue.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {errors.password && (
                            <Alert variant="destructive" className="mb-4">
                                <AlertDescription>{errors.password}</AlertDescription>
                            </Alert>
                        )}
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    placeholder="••••••••"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    autoFocus
                                    required
                                />
                            </div>
                            <Button type="submit" className="w-full" disabled={processing}>
                                Sign in
                            </Button>
                        </form>
                    </CardContent>
                </Card>
                <p className="mt-4 text-center text-xs text-muted-foreground">
                    Internal tool · Access is restricted to your team.
                </p>
            </div>
        </div>
    );
}
