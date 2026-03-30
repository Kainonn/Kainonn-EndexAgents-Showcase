import { Link } from '@inertiajs/react';
import { LayoutGrid, ListChecks, Rocket, Target, Users } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Campanas',
        href: '/endex/campaigns/builder',
        icon: Rocket,
    },
    {
        title: 'Centro de Ataque',
        href: '/endex/workspace',
        icon: Target,
    },
    {
        title: 'Prospectos',
        href: '/endex/prospectos',
        icon: Users,
    },
    {
        title: 'Prospectos Lista',
        href: '/endex/prospectos/lista-estatus',
        icon: Users,
    },
    {
        title: 'Inbox Leads',
        href: '/endex/leads/inbox',
        icon: ListChecks,
    },
];

export function AppSidebar() {
    return (
        <Sidebar
            collapsible="icon"
            variant="inset"
            className="**:data-[sidebar=sidebar]:border-sidebar-border/70 **:data-[sidebar=sidebar]:bg-[linear-gradient(180deg,rgba(255,255,255,0.98)_0%,rgba(247,248,250,0.98)_58%,rgba(242,244,247,0.98)_100%)] dark:**:data-[sidebar=sidebar]:border-sidebar-border/40 dark:**:data-[sidebar=sidebar]:bg-[linear-gradient(180deg,rgba(6,7,8,0.97)_0%,rgba(10,14,18,0.98)_55%,rgba(12,17,22,0.98)_100%)]"
        >
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
