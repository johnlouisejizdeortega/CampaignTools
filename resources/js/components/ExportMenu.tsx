import { useEffect, useRef, useState } from 'react';
import { Download, FileText, Sheet, FileSpreadsheet } from 'lucide-react';
import { Button } from '@/components/ui/button';

/**
 * A small Export dropdown. Each item is a plain link to the export endpoint with
 * the current filters, so the browser downloads the file (session cookie is sent
 * automatically). Formats: PDF, Excel (.xlsx) and CSV.
 */
export default function ExportMenu({ baseUrl, params }: { baseUrl: string; params: Record<string, string> }) {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const onClick = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false); };
        document.addEventListener('mousedown', onClick);
        return () => document.removeEventListener('mousedown', onClick);
    }, []);

    const url = (format: string) => {
        const q = new URLSearchParams({ ...params, format }).toString();
        return `${baseUrl}?${q}`;
    };

    const item = 'flex w-full items-center gap-2 px-3 py-2 text-sm text-foreground hover:bg-muted';

    return (
        <div className="relative" ref={ref}>
            <Button variant="outline" size="sm" onClick={() => setOpen((v) => !v)}>
                <Download className="h-4 w-4" /> Export
            </Button>
            {open && (
                <div className="absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-md border bg-card py-1 shadow-lg">
                    <a href={url('pdf')} className={item} onClick={() => setOpen(false)}><FileText className="h-4 w-4 text-muted-foreground" /> PDF</a>
                    <a href={url('xlsx')} className={item} onClick={() => setOpen(false)}><FileSpreadsheet className="h-4 w-4 text-muted-foreground" /> Excel (.xlsx)</a>
                    <a href={url('csv')} className={item} onClick={() => setOpen(false)}><Sheet className="h-4 w-4 text-muted-foreground" /> CSV / Sheets</a>
                </div>
            )}
        </div>
    );
}
