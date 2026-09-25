import { Link } from '@inertiajs/react';
import { ArrowLeft, Gauge } from 'lucide-react';
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

export function AdminSidebar() {
    // CMS sections (tracks, lessons, skills…) join this list in phase 5b.
    const adminNavItems: NavItem[] = [
        { title: t('nav.adminOverview'), href: adminDashboard(), icon: Gauge },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={adminDashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={adminNavItems} label={t('nav.admin')} />
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            tooltip={{ children: t('nav.backToApp') }}
                        >
                            <Link href={dashboard()}>
                                <ArrowLeft />
                                <span>{t('nav.backToApp')}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
