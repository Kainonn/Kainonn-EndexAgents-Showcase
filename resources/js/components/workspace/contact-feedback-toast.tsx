import { CheckCircle, Copy, ExternalLink, XCircle } from 'lucide-react';
import { useEffect } from 'react';
import { cn } from '@/lib/utils';

type FeedbackState = {
    visible: boolean;
    type: 'success' | 'info' | 'error';
    title: string;
    description: string;
};

type Props = {
    feedback: FeedbackState;
    onDismiss: () => void;
};

const icons = {
    success: CheckCircle,
    info: Copy,
    error: XCircle,
};

const colors = {
    success: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200',
    info: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-200',
    error: 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800 dark:bg-rose-950/60 dark:text-rose-200',
};

export function ContactFeedbackToast({ feedback, onDismiss }: Props) {
    useEffect(() => {
        if (!feedback.visible) return;
        const timer = setTimeout(onDismiss, 4000);
        return () => clearTimeout(timer);
    }, [feedback.visible, onDismiss]);

    if (!feedback.visible) return null;

    const Icon = icons[feedback.type];

    return (
        <div className="fixed bottom-6 right-6 z-[9999] animate-in slide-in-from-bottom-4 fade-in duration-300">
            <div
                className={cn(
                    'flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg',
                    colors[feedback.type],
                )}
            >
                <Icon className="mt-0.5 size-4 shrink-0" />
                <div className="min-w-0">
                    <p className="text-sm font-medium">{feedback.title}</p>
                    <p className="mt-0.5 text-xs opacity-80">{feedback.description}</p>
                </div>
                <button
                    type="button"
                    onClick={onDismiss}
                    className="ml-2 shrink-0 opacity-50 hover:opacity-100"
                >
                    <ExternalLink className="size-3.5" />
                </button>
            </div>
        </div>
    );
}

export type { FeedbackState };
