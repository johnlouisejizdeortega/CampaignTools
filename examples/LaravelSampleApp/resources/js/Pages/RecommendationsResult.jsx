import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import Tip from '@/components/Tip';
import { playbook } from '@/data/playbook';

export default function RecommendationsResult({ customerId, recommendations = [], error = null }) {
    return (
        <AppLayout
            title="Optimization suggestions"
            subtitle={`For account ${customerId}`}
        >
            <Head title="Optimization suggestions" />

            {error ? (
                <Alert variant="destructive" className="mb-6">
                    <AlertDescription>
                        <strong>Couldn't fetch live recommendations.</strong> This usually means the
                        server isn't connected to Google Ads yet, or the Customer ID is invalid. You
                        can still use the playbook below.
                        <span className="mt-1 block text-xs text-muted-foreground">
                            Details: {String(error).slice(0, 240)}
                        </span>
                    </AlertDescription>
                </Alert>
            ) : recommendations.length === 0 ? (
                <Alert variant="info" className="mb-6">
                    <AlertDescription>
                        No active recommendations right now — nice, the account is in good shape. Keep
                        an eye on the playbook below.
                    </AlertDescription>
                </Alert>
            ) : (
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle>
                            Live recommendations
                            <Badge variant="success">{recommendations.length} found</Badge>
                        </CardTitle>
                        <CardDescription>
                            Straight from Google Ads for this account, with what each one means.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {recommendations.map((rec, i) => (
                            <Tip
                                key={i}
                                title={rec.title}
                                meta={rec.campaignId ? `· campaign ${rec.campaignId}` : null}
                                badge={{ variant: rec.badge, label: rec.type.replace(/_/g, ' ') }}
                                problem={rec.why}
                                fix={rec.fix}
                                fixLabel="How to fix"
                            />
                        ))}
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader>
                    <CardTitle>Optimization playbook</CardTitle>
                    <CardDescription>
                        Common problems and how to fix them — useful for any account.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                    {playbook.map((tip, i) => (
                        <Tip key={i} {...tip} />
                    ))}
                </CardContent>
            </Card>

            <div className="mt-6">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/">← Back to dashboard</Link>
                </Button>
            </div>
        </AppLayout>
    );
}
