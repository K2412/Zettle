import { router } from '@inertiajs/svelte';
import {
    forceCenter,
    forceLink,
    forceManyBody,
    forceSimulation,
    type Simulation,
    type SimulationLinkDatum,
    type SimulationNodeDatum,
} from 'd3-force';
import { toUrl } from '@/lib/utils';
import { show } from '@/routes/notes';
import type { GraphData } from '@/types';

type SimNode = SimulationNodeDatum & {
    id: number;
    title: string;
    slug: string;
    color: string;
};

type SimLink = SimulationLinkDatum<SimNode> & {
    kind: 'mention' | 'typed';
};

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
const DRAG_THRESHOLD = 4;
const NODE_RADIUS = 8;
const HOVER_RADIUS = 12;

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

export class GraphState {
    private cleanup: (() => void) | null = null;

    attach = (canvas: HTMLCanvasElement, data: GraphData) => {
        this.detach();
        this.cleanup = runSimulation(canvas, data);
    };

    detach = () => {
        this.cleanup?.();
        this.cleanup = null;
    };
}

function runSimulation(canvas: HTMLCanvasElement, data: GraphData): () => void {
    const ctx = canvas.getContext('2d');

    if (!ctx) {
        return () => {};
    }

    const state: SimState = {
        simulation: null,
        nodes: data.nodes.map((node) => ({ ...node })),
        links: data.edges.map((edge) => ({
            source: edge.source,
            target: edge.target,
            kind: edge.kind,
        })),
        width: 0,
        height: 0,
        hovered: null,
        dragging: null,
        downAt: null,
        moved: false,
        rafId: null,
    };

    const labelColor = getComputedStyle(canvas).color;

    const resize = () => {
        const rect = canvas.getBoundingClientRect();
        state.width = rect.width;
        state.height = rect.height;
        const dpr = window.devicePixelRatio || 1;
        canvas.width = Math.floor(rect.width * dpr);
        canvas.height = Math.floor(rect.height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

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
                ctx.fillText(node.title, (node.x ?? 0) + 14, (node.y ?? 0) + 4);
            }
        }
    };

    resize();

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
    resize();

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

        if (dragged && !moved) {
            router.visit(toUrl(show(dragged.slug)));
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
}
