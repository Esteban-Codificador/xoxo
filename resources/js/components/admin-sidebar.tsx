import { Link } from '@inertiajs/react';
import { ArrowLeft, BookOpen, Gauge } from 'lucide-react';
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
import { useCurrentUrl } from '@/hooks/use-current-url';
import { t } from '@/i18n';
import { dashboard } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as lessonsIndex } from '@/routes/admin/lessons';
import type { NavItem } from '@/types';

export function AdminSidebar() {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    // CMS sections join this list as they ship (tracks, skills… in phase 5b).
    const adminNavItems: NavItem[] = [
        { title: t('nav.adminOverview'), href: adminDashboard(), icon: Gauge },
        {
            title: t('nav.lessons'),
            href: lessonsIndex(),
            icon: BookOpen,
            // Also active while editing a lesson.
            isActive: isCurrentOrParentUrl(lessonsIndex()),
        },
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
