import { router } from '@inertiajs/react';
import {
    forceCenter,
    forceLink,
    forceManyBody,
    forceSimulation,
} from 'd3-force';
import type {
    Simulation,
    SimulationLinkDatum,
    SimulationNodeDatum,
} from 'd3-force';
import { useEffect, useRef } from 'react';
import { show } from '@/routes/notes';
import type { GraphData } from '@/types';

/** A graph node the simulation mutates in place (x/y/vx/vy come from d3-force). */
type SimNode = SimulationNodeDatum & {
    id: number;
    title: string;
    slug: string;
    color: string;
};

/** A link keyed by node id; d3-force resolves the ids to node objects on init. */
type SimLink = SimulationLinkDatum<SimNode> & {
    kind: 'mention' | 'typed';
};

/**
 * Everything the draw loop and pointer handlers read or write every frame.
 * Held in a ref (never useState) so per-frame mutation drives the canvas
 * imperatively without re-rendering React (architecture §5.15).
 */
type SimState = {
    simulation: Simulation<SimNode, SimLink> | null;
    nodes: SimNode[];
    links: SimLink[];
    width: number;
    height: number;
    hovered: SimNode | null;
    dragging: SimNode | null;
    downAt: { x: number; y: number } | null;
    moved: boolean;
    rafId: number | null;
};

const HIT_RADIUS = 12;
/** Pointer travel (px) beyond which a press counts as a drag, not a click. */
const DRAG_THRESHOLD = 4;
const NODE_RADIUS = 8;
const HOVER_RADIUS = 12;

/**
 * A stable identity for the graph data: the sim only rebuilds when the set of
 * nodes/edges actually changes, not on every render (architecture §5.7).
 */
function graphKey(data: GraphData): string {
    const nodes = data.nodes.map((n) => n.id).join(',');
    const edges = data.edges
        .map((e) => `${e.source}-${e.target}-${e.kind}`)
        .join(',');

    return `${nodes}|${edges}`;
}

function pointFromEvent(
    canvas: HTMLCanvasElement,
    event: PointerEvent,
): { x: number; y: number } {
    const rect = canvas.getBoundingClientRect();

    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
}

function hitTest(nodes: SimNode[], x: number, y: number): SimNode | null {
    for (let i = nodes.length - 1; i >= 0; i--) {
        const node = nodes[i];
        const dx = (node.x ?? 0) - x;
        const dy = (node.y ?? 0) - y;

        if (dx * dx + dy * dy <= HIT_RADIUS * HIT_RADIUS) {
            return node;
        }
    }

    return null;
}

export function useGraphSimulation(data: GraphData) {
    const canvasRef = useRef<HTMLCanvasElement | null>(null);
    const stateRef = useRef<SimState>({
        simulation: null,
        nodes: [],
        links: [],
        width: 0,
        height: 0,
        hovered: null,
        dragging: null,
        downAt: null,
        moved: false,
        rafId: null,
    });

    const key = graphKey(data);

    useEffect(() => {
        const canvas = canvasRef.current;

        if (!canvas) {
            return;
        }

        const ctx = canvas.getContext('2d');

        if (!ctx) {
            return;
        }

        const state = stateRef.current;

        // The hover label reads the canvas's resolved foreground color (from the
        // `text-foreground` class) so it follows light/dark theme — a canvas
        // can't read CSS tokens directly, so we resolve it once here.
        const labelColor = getComputedStyle(canvas).color;

        // Build the sim node/link objects fresh from the current graph data.
        state.nodes = data.nodes.map((node) => ({ ...node }));
        state.links = data.edges.map((edge) => ({
            source: edge.source,
            target: edge.target,
            kind: edge.kind,
        }));

        const resize = () => {
            const rect = canvas.getBoundingClientRect();
            state.width = rect.width;
            state.height = rect.height;
            const dpr = window.devicePixelRatio || 1;
            canvas.width = Math.floor(rect.width * dpr);
            canvas.height = Math.floor(rect.height * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

            // Recentre the sim only once it exists (read via state, never the
            // TDZ-bound `simulation` const, so this is safe to call pre-build).
            const sim = state.simulation;
            const center = sim?.force('center');

            if (sim && center) {
                (center as ReturnType<typeof forceCenter>)
                    .x(rect.width / 2)
                    .y(rect.height / 2);
                sim.alpha(0.3).restart();
            }
        };

        const draw = () => {
            ctx.clearRect(0, 0, state.width, state.height);

            for (const link of state.links) {
                const source = link.source as SimNode;
                const target = link.target as SimNode;

                if (link.kind === 'typed') {
                    ctx.strokeStyle = 'rgba(99, 102, 241, 0.7)';
                    ctx.lineWidth = 1.5;
                    ctx.setLineDash([]);
                } else {
                    ctx.strokeStyle = 'rgba(99, 102, 241, 0.25)';
                    ctx.lineWidth = 1;
                    ctx.setLineDash([4, 4]);
                }

                ctx.beginPath();
                ctx.moveTo(source.x ?? 0, source.y ?? 0);
                ctx.lineTo(target.x ?? 0, target.y ?? 0);
                ctx.stroke();
            }

            ctx.setLineDash([]);

            for (const node of state.nodes) {
                const isHover = node === state.hovered;
                ctx.fillStyle = node.color;
                ctx.beginPath();
                ctx.arc(
                    node.x ?? 0,
                    node.y ?? 0,
                    isHover ? HOVER_RADIUS : NODE_RADIUS,
                    0,
                    Math.PI * 2,
                );
                ctx.fill();

                if (isHover) {
                    ctx.fillStyle = labelColor;
                    ctx.font = '13px Inter, system-ui, sans-serif';
                    ctx.fillText(
                        node.title,
                        (node.x ?? 0) + 14,
                        (node.y ?? 0) + 4,
                    );
                }
            }
        };

        resize();

        // Seed every node at the canvas centre so the layout grows outward from
        // the visible middle (d3's default is a spiral around the origin, which
        // starts the graph in the top-left corner). A lone node then sits
        // deterministically dead-centre — no other force acts on it — which is
        // what the click→navigate browser test relies on.
        const centerX = state.width / 2;
        const centerY = state.height / 2;

        for (const node of state.nodes) {
            node.x = centerX;
            node.y = centerY;
        }

        const simulation = forceSimulation<SimNode, SimLink>(state.nodes)
            .force('charge', forceManyBody().strength(-200))
            .force(
                'link',
                forceLink<SimNode, SimLink>(state.links)
                    .id((node) => node.id)
                    .distance(90),
            )
            .force('center', forceCenter(state.width / 2, state.height / 2));
        state.simulation = simulation;

        // Re-run resize now that the sim exists so forceCenter is positioned.
        resize();

        // A persistent RAF draw loop: it redraws every frame — including hover
        // and drag repaints after the physics has settled and stopped ticking.
        const frame = () => {
            draw();
            state.rafId = requestAnimationFrame(frame);
        };
        state.rafId = requestAnimationFrame(frame);

        const onPointerDown = (event: PointerEvent) => {
            const { x, y } = pointFromEvent(canvas, event);
            const node = hitTest(state.nodes, x, y);

            if (node) {
                state.dragging = node;
                state.downAt = { x, y };
                state.moved = false;
                simulation.alphaTarget(0.3).restart();
                node.fx = x;
                node.fy = y;
            }
        };

        const onPointerMove = (event: PointerEvent) => {
            const { x, y } = pointFromEvent(canvas, event);

            if (state.dragging) {
                const from = state.downAt;

                if (
                    from &&
                    (Math.abs(x - from.x) > DRAG_THRESHOLD ||
                        Math.abs(y - from.y) > DRAG_THRESHOLD)
                ) {
                    state.moved = true;
                }

                // Pin the node under the pointer and zero its velocity.
                state.dragging.fx = x;
                state.dragging.fy = y;
                state.dragging.vx = 0;
                state.dragging.vy = 0;

                return;
            }

            state.hovered = hitTest(state.nodes, x, y);
            canvas.style.cursor = state.hovered ? 'pointer' : 'default';
        };

        const releaseDragging = () => {
            if (state.dragging) {
                state.dragging.fx = null;
                state.dragging.fy = null;
                state.dragging = null;
                simulation.alphaTarget(0);
            }
        };

        const onPointerUp = () => {
            const dragged = state.dragging;
            const moved = state.moved;
            releaseDragging();
            state.downAt = null;
            state.moved = false;

            // A press that never travelled far is a click, not a drag → navigate.
            if (dragged && !moved) {
                router.visit(show(dragged.slug));
            }
        };

        const onPointerLeave = () => {
            releaseDragging();
            state.downAt = null;
            state.moved = false;
            state.hovered = null;
            canvas.style.cursor = 'default';
        };

        canvas.addEventListener('pointerdown', onPointerDown);
        canvas.addEventListener('pointermove', onPointerMove);
        canvas.addEventListener('pointerup', onPointerUp);
        canvas.addEventListener('pointerleave', onPointerLeave);
        // Resize never calls preventDefault, so it can be passive (§4.2).
        window.addEventListener('resize', resize, { passive: true });

        return () => {
            if (state.rafId !== null) {
                cancelAnimationFrame(state.rafId);
                state.rafId = null;
            }

            simulation.stop();
            state.simulation = null;
            canvas.removeEventListener('pointerdown', onPointerDown);
            canvas.removeEventListener('pointermove', onPointerMove);
            canvas.removeEventListener('pointerup', onPointerUp);
            canvas.removeEventListener('pointerleave', onPointerLeave);
            window.removeEventListener('resize', resize);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [key]);

    return canvasRef;
}
