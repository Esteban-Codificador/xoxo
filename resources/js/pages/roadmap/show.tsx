import { Head, setLayoutProps } from '@inertiajs/react';
import { Map as MapIcon } from 'lucide-react';
import { lazy, Suspense, useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { NodeDetailPanel } from '@/features/roadmap-graph/node-detail-panel';
import { RoadmapListView } from '@/features/roadmap-graph/roadmap-list-view';
import type { RoadmapEdge, RoadmapTrack } from '@/features/roadmap-graph/types';
import { t } from '@/i18n';
import { dashboard } from '@/routes';
import { show as showRoadmap } from '@/routes/roadmaps';
import type { UnlockPolicy } from '@/types/enums';

const RoadmapCanvas = lazy(
    () => import('@/features/roadmap-graph/roadmap-canvas'),
);

type Props = {
    roadmap: { slug: string; title: string; summary: string };
    policy: UnlockPolicy;
    tracks: RoadmapTrack[];
    edges: RoadmapEdge[];
};

type View = 'graph' | 'list';

function initialView(): View {
    if (typeof window === 'undefined') {
        return 'graph';
    }

    return new URLSearchParams(window.location.search).get('view') === 'list'
        ? 'list'
        : 'graph';
}

export default function RoadmapShow({ roadmap, policy, tracks, edges }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.dashboard'), href: dashboard() },
            { title: roadmap.title, href: showRoadmap(roadmap.slug) },
        ],
    });

    const [view, setView] = useState<View>(initialView);
    const [selected, setSelected] = useState<string | null>(null);
    const selectedTrack =
        tracks.find((track) => track.slug === selected) ?? null;

    const changeView = (next: string) => {
        if (next !== 'graph' && next !== 'list') {
            return;
        }

        setView(next);
        const url = new URL(window.location.href);
        if (next === 'list') url.searchParams.set('view', 'list');
        else url.searchParams.delete('view');
        window.history.replaceState(window.history.state, '', url);
    };

    return (
        <>
            <Head title={roadmap.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div className="space-y-1">
                        <p className="text-sm text-muted-foreground">
                            {t('roadmap.eyebrow')}
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {roadmap.title}
                        </h1>
                        <p className="max-w-2xl text-sm text-muted-foreground">
                            {roadmap.summary}
                        </p>
                    </div>
                    {tracks.length > 0 && (
                        <div className="hidden items-center gap-4 md:flex">
                            <ul
                                className="flex items-center gap-4 text-xs text-muted-foreground"
                                aria-hidden="true"
                            >
                                <li className="flex items-center gap-2">
                                    <span className="inline-block h-0 w-6 border-t-2 border-muted-foreground" />
                                    {t('roadmap.legendRequired')}
                                </li>
                                <li className="flex items-center gap-2">
                                    <span className="inline-block h-0 w-6 border-t-2 border-dashed border-muted-foreground" />
                                    {t('roadmap.legendRecommended')}
                                </li>
                            </ul>
                            <ToggleGroup
                                type="single"
                                value={view}
                                onValueChange={changeView}
                                variant="outline"
                                aria-label={t('roadmap.view')}
                            >
                                <ToggleGroupItem value="graph">
                                    {t('roadmap.graph')}
                                </ToggleGroupItem>
                                <ToggleGroupItem value="list">
                                    {t('roadmap.list')}
                                </ToggleGroupItem>
                            </ToggleGroup>
                        </div>
                    )}
                </header>

                {tracks.length === 0 ? (
                    <EmptyState icon={MapIcon} title={t('roadmap.empty')} />
                ) : (
                    <>
                        {view === 'graph' && (
                            // Phones always get the list: a canvas is unusable at 390 px.
                            <div className="hidden h-[70vh] min-h-[480px] overflow-hidden rounded-xl border bg-muted/20 md:block">
                                <Suspense fallback={null}>
                                    <RoadmapCanvas
                                        tracks={tracks}
                                        edges={edges}
                                        selected={selected}
                                        onOpen={setSelected}
                                    />
                                </Suspense>
                            </div>
                        )}
                        <div
                            className={
                                view === 'graph' ? 'md:hidden' : undefined
                            }
                        >
                            <RoadmapListView
                                roadmapSlug={roadmap.slug}
                                tracks={tracks}
                                edges={edges}
                                onOpen={setSelected}
                            />
                        </div>
                    </>
                )}
            </div>

            <NodeDetailPanel
                roadmapSlug={roadmap.slug}
                policy={policy}
                track={selectedTrack}
                tracks={tracks}
                edges={edges}
                onClose={() => setSelected(null)}
            />
        </>
    );
}
