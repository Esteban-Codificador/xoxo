import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, ShieldCheck } from 'lucide-react';
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
import { t } from '@/i18n';
import { dashboard } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { can } = usePage().props;

    // Only routes that exist: new sections are added here as they ship.
    const mainNavItems: NavItem[] = [
        { title: t('nav.dashboard'), href: dashboard(), icon: LayoutGrid },
        ...(can.accessAdmin
            ? [
                  {
                      title: t('nav.admin'),
                      href: adminDashboard(),
                      icon: ShieldCheck,
                  },
              ]
            : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
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
