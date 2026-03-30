import { Search } from 'lucide-react';

type Props = {
    children: React.ReactNode;
    title?: string;
};

export function WorkspaceEmptyState({ children, title = 'Sin leads en esta vista' }: Props) {
    return (
        <div className="flex flex-col items-center justify-center py-20 text-center">
            <div className="flex size-12 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                <Search className="size-5 text-muted-foreground" />
            </div>
            <p className="mt-3 text-sm font-medium text-foreground">{title}</p>
            <p className="mt-1 max-w-sm text-xs text-muted-foreground">{children}</p>
        </div>
    );
}
