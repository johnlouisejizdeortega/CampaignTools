import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({ password: '' });

    const submit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-background p-6">
            <Head title="Sign in" />
            <div className="w-full max-w-sm">
                <div className="mb-6 flex items-center justify-center gap-2 font-semibold">
                    <span className="flex h-7 w-7 items-center justify-center rounded-md bg-primary text-xs font-bold text-primary-foreground">
                        Ads
                    </span>
                    <span>Google Ads Dashboard</span>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Sign in</CardTitle>
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
