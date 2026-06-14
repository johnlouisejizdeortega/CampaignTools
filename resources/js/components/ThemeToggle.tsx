import { useEffect, useState } from 'react';
import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { isDark, toggleTheme } from '@/lib/theme';

export default function ThemeToggle() {
    // Start light on the server; sync to the real value once mounted on the client.
    const [dark, setDark] = useState(false);
    useEffect(() => setDark(isDark()), []);

    return (
        <Button
            variant="ghost"
            size="icon"
            aria-label="Toggle dark mode"
            onClick={() => setDark(toggleTheme() === 'dark')}
        >
            {dark ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
        </Button>
    );
}
