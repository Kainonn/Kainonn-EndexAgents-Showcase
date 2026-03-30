import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 bg-[linear-gradient(180deg,rgba(255,255,255,0.98)_0%,rgba(248,249,251,0.95)_100%)] px-6 transition-[width,height] ease-linear dark:border-sidebar-border/40 dark:bg-[linear-gradient(180deg,rgba(6,7,8,0.98)_0%,rgba(10,14,18,0.94)_100%)] group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2 text-foreground">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <p className="hidden rounded-full border border-[#ff750f50] bg-[#ff750f12] px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#9a4d16] dark:border-[#ff750f40] dark:bg-[#ff750f1a] dark:text-[#ffb278] sm:inline-flex">
                EndexAgents B2B
            </p>
        </header>
    );
}
