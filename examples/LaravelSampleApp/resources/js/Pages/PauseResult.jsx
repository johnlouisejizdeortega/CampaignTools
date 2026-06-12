import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';

export default function PauseResult({ campaign }) {
    return (
        <AppLayout
            title="Campaign paused"
            subtitle="The campaign below is now paused and will stop spending."
        >
            <Head title="Campaign paused" />
            <Card className="max-w-xl">
                <CardContent className="space-y-4 pt-6">
                    <div className="space-y-2">
                        <Label>Campaign ID</Label>
                        <div className="flex h-10 items-center rounded-md border bg-muted px-3 text-sm">
                            {campaign.id}
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Campaign name</Label>
                        <div className="flex h-10 items-center rounded-md border bg-muted px-3 text-sm">
                            {campaign.name}
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label>Status</Label>
                        <div>
                            <Badge variant="warning">{campaign.status}</Badge>
                        </div>
                    </div>
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
