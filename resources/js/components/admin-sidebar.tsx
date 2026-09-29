import { Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpen,
    ClipboardCheck,
    Gauge,
    Layers,
    Library,
    Sparkles,
} from 'lucide-react';
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
import { index as resourcesIndex } from '@/routes/admin/resources';
import { index as reviewsIndex } from '@/routes/admin/reviews';
import { index as skillsIndex } from '@/routes/admin/skills';
import { index as tracksIndex } from '@/routes/admin/tracks';
import type { NavItem } from '@/types';

export function AdminSidebar() {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { pendingReviews } = usePage().props;

    const adminNavItems: NavItem[] = [
        { title: t('nav.adminOverview'), href: adminDashboard(), icon: Gauge },
        {
            title: t('nav.tracks'),
            href: tracksIndex(),
            icon: Layers,
            isActive: isCurrentOrParentUrl(tracksIndex()),
        },
        {
            title: t('nav.lessons'),
            href: lessonsIndex(),
            icon: BookOpen,
            // Also active while editing a lesson.
            isActive: isCurrentOrParentUrl(lessonsIndex()),
        },
        // Only for who reviews: the server sends null to everyone else.
        ...(pendingReviews === null
            ? []
            : [
                  {
                      title: t('nav.reviews'),
                      href: reviewsIndex(),
                      icon: ClipboardCheck,
                      badge: pendingReviews,
                  },
              ]),
        {
            title: t('nav.skills'),
            href: skillsIndex(),
            icon: Sparkles,
            isActive: isCurrentOrParentUrl(skillsIndex()),
        },
        {
            title: t('nav.resources'),
            href: resourcesIndex(),
            icon: Library,
            isActive: isCurrentOrParentUrl(resourcesIndex()),
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
