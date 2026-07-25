import { Component, type ErrorInfo, type ReactNode } from 'react';

interface Props { children: ReactNode }
interface State { error: Error | null }

/**
 * Catches render-time errors so a crash shows a readable message instead of a
 * blank white screen, and gives the user a way to recover.
 */
export default class ErrorBoundary extends Component<Props, State> {
    state: State = { error: null };

    static getDerivedStateFromError(error: Error): State {
        return { error };
    }

    componentDidCatch(error: Error, info: ErrorInfo): void {
        // Surface to the console for debugging; no external reporting.
        console.error('Unhandled UI error:', error, info.componentStack);
    }

    render(): ReactNode {
        if (this.state.error) {
            return (
                <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 24, fontFamily: 'system-ui, sans-serif' }}>
                    <div style={{ maxWidth: 480 }}>
                        <h1 style={{ fontSize: 20, fontWeight: 600, marginBottom: 8 }}>Something went wrong</h1>
                        <p style={{ color: '#5f6368', marginBottom: 16 }}>
                            This page hit an error and couldn’t finish loading. A hard refresh usually
                            fixes it after a new deploy.
                        </p>
                        <pre style={{ background: '#f1f3f4', padding: 12, borderRadius: 8, fontSize: 12, overflow: 'auto', color: '#d93025' }}>
                            {this.state.error.message}
                        </pre>
                        <button
                            onClick={() => { window.location.reload(); }}
                            style={{ marginTop: 16, background: '#1a73e8', color: '#fff', border: 0, borderRadius: 999, padding: '8px 20px', fontSize: 14, cursor: 'pointer' }}
                        >
                            Reload
                        </button>
                    </div>
                </div>
            );
        }
        return this.props.children;
    }
}
