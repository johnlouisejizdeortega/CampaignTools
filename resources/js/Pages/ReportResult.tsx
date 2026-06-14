import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type ReportRow = Record<string, Record<string, string | number>>;

interface Paginated {
    data: ReportRow[];
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface ReportResultProps {
    results: Paginated;
    selectedFields?: string[];
}

function valueFor(row: ReportRow, field: string) {
    const [parent, child] = field.split('.');
    return row?.[parent]?.[child] ?? 'N/A';
}

export default function ReportResult({ results, selectedFields = [] }: ReportResultProps) {
    const rows = results?.data ?? [];

    return (
        <AppLayout title="Report" subtitle="Live results from the Google Ads API.">
            <Head title="Report" />
            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-12">#</TableHead>
                            {selectedFields.map((f) => (
                                <TableHead key={f}>{f}</TableHead>
                            ))}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {rows.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={selectedFields.length + 1}
                                    className="text-center text-muted-foreground"
                                >
                                    No data for this query.
                                </TableCell>
                            </TableRow>
                        ) : (
                            rows.map((row, i) => (
                                <TableRow key={i}>
                                    <TableCell>{(results.from ?? 1) + i}</TableCell>
                                    {selectedFields.map((f) => (
                                        <TableCell key={f}>{valueFor(row, f)}</TableCell>
                                    ))}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            {(results?.prev_page_url || results?.next_page_url) && (
                <div className="mt-4 flex items-center justify-between">
                    <div className="text-sm text-muted-foreground">
                        {results.from}–{results.to} of {results.total}
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" disabled={!results.prev_page_url} asChild={!!results.prev_page_url}>
                            {results.prev_page_url ? <Link href={results.prev_page_url}>Previous</Link> : <span>Previous</span>}
                        </Button>
                        <Button variant="outline" size="sm" disabled={!results.next_page_url} asChild={!!results.next_page_url}>
                            {results.next_page_url ? <Link href={results.next_page_url}>Next</Link> : <span>Next</span>}
                        </Button>
                    </div>
                </div>
            )}

            <div className="mt-6">
                <Button variant="outline" size="sm" asChild>
                    <Link href="/">← Back to dashboard</Link>
                </Button>
            </div>
        </AppLayout>
    );
}
