import { Head } from '@inertiajs/react';
import { useGraphSimulation } from '@/hooks/use-graph-simulation';
import { graph } from '@/routes/notes';
import type { GraphData } from '@/types';

type Props = {
    graph: GraphData;
};

/**
 * The interactive graph canvas. Kept at module scope (never defined inside the
 * page component — architecture §5.4) so it isn't a fresh type every render.
 */
function GraphCanvas({ data }: { data: GraphData }) {
    const canvasRef = useGraphSimulation(data);

    // `text-foreground` gives the canvas a theme-aware color to inherit; the
    // draw loop reads it via getComputedStyle so the hover label follows the theme.
    return (
        <canvas
            ref={canvasRef}
            className="h-full w-full text-foreground"
            data-test="graph-canvas-el"
        />
    );
}

export default function NotesGraph({ graph: data }: Props) {
    // Counts are derived during render — never stored in state (architecture §5.1).
    const nodeCount = data.nodes.length;
    const connectionCount = data.edges.length;

    return (
        <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
            <Head title="Graph" />

            <header className="flex flex-col gap-1">
                <h2 className="text-xl font-semibold tracking-tight">Graph</h2>
                <p
                    className="text-sm text-muted-foreground"
                    data-test="graph-counts"
                >
                    {nodeCount} {nodeCount === 1 ? 'note' : 'notes'} ·{' '}
                    {connectionCount}{' '}
                    {connectionCount === 1 ? 'connection' : 'connections'}
                </p>
            </header>

            {nodeCount === 0 ? (
                <div
                    className="rounded-xl border border-dashed p-10 text-center text-muted-foreground"
                    data-test="empty-state"
                >
                    <p className="font-medium">No notes to visualize yet.</p>
                    <p className="text-sm">
                        Create a few and link them with [[Note Title]].
                    </p>
                </div>
            ) : (
                <div
                    className="h-[70vh] w-full overflow-hidden rounded-xl border"
                    data-test="graph-canvas"
                >
                    <GraphCanvas data={data} />
                </div>
            )}
        </div>
    );
}

NotesGraph.layout = {
    breadcrumbs: [{ title: 'Graph', href: graph() }],
};
