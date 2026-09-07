import { getContext, setContext } from 'svelte';
import {
    fetchAtomizeIdeas,
    fetchClusterProject,
    fetchConnectCandidates,
    fetchMakeFindable,
    fetchStructure,
    fetchTriage,
} from '@/lib/note-assists';
import type {
    AtomizeIdea,
    ClusterProjectResult,
    ConnectCandidate,
    MakeFindableResult,
    Note,
    PhaseOption,
    StructureResult,
    TriageResult,
} from '@/types';

export type AssistLoadState = 'idle' | 'loading' | 'ready';

const KEY = '$_assist_panel_state';

interface AssistPanelState {
    activePhase: string;
    suggestedPhase: string;
    phases: PhaseOption[];
    note: Note;
    setPhase: (phase: string) => void;
    triageState: AssistLoadState;
    triage: TriageResult | null;
    runTriage: () => Promise<void>;
    clearTriage: () => void;
    atomizeState: AssistLoadState;
    ideas: AtomizeIdea[];
    selectedTitles: Set<string>;
    runAtomize: () => Promise<void>;
    toggleIdea: (title: string) => void;
    clearAtomize: () => void;
    connectState: AssistLoadState;
    candidates: ConnectCandidate[];
    runConnect: () => Promise<void>;
    makeFindableState: AssistLoadState;
    makeFindable: MakeFindableResult | null;
    runMakeFindable: () => Promise<void>;
    structureState: AssistLoadState;
    structure: StructureResult | null;
    runStructure: () => Promise<void>;
    clusterState: AssistLoadState;
    clusters: ClusterProjectResult[];
    runClusterProject: () => Promise<void>;
}

class AssistPanelStateClass implements AssistPanelState {
    activePhase = $state('');
    suggestedPhase = $state('');
    phases = $state<PhaseOption[]>([]);
    note = $state<Note>({} as Note);

    triageState = $state<AssistLoadState>('idle');
    triage = $state<TriageResult | null>(null);

    atomizeState = $state<AssistLoadState>('idle');
    ideas = $state<AtomizeIdea[]>([]);
    selectedTitles = $state(new Set<string>());

    connectState = $state<AssistLoadState>('idle');
    candidates = $state<ConnectCandidate[]>([]);

    makeFindableState = $state<AssistLoadState>('idle');
    makeFindable = $state<MakeFindableResult | null>(null);

    structureState = $state<AssistLoadState>('idle');
    structure = $state<StructureResult | null>(null);

    clusterState = $state<AssistLoadState>('idle');
    clusters = $state<ClusterProjectResult[]>([]);

    constructor(note: Note, suggestedPhase: string, phases: PhaseOption[]) {
        this.note = note;
        this.suggestedPhase = suggestedPhase;
        this.phases = phases;
        this.activePhase = suggestedPhase;
    }

    setPhase = (phase: string) => {
        this.activePhase = phase;
    };

    runTriage = async () => {
        this.triageState = 'loading';
        this.triage = await fetchTriage(this.note.slug);
        this.triageState = 'ready';
    };

    clearTriage = () => {
        this.triage = null;
        this.triageState = 'idle';
    };

    runAtomize = async () => {
        this.atomizeState = 'loading';
        this.selectedTitles = new Set();
        this.ideas = await fetchAtomizeIdeas(this.note.slug);
        this.atomizeState = 'ready';
    };

    toggleIdea = (title: string) => {
        const next = new Set(this.selectedTitles);

        if (next.has(title)) {
            next.delete(title);
        } else {
            next.add(title);
        }

        this.selectedTitles = next;
    };

    clearAtomize = () => {
        this.ideas = [];
        this.selectedTitles = new Set();
        this.atomizeState = 'idle';
    };

    runConnect = async () => {
        this.connectState = 'loading';
        this.candidates = await fetchConnectCandidates(this.note.slug);
        this.connectState = 'ready';
    };

    runMakeFindable = async () => {
        this.makeFindableState = 'loading';
        this.makeFindable = await fetchMakeFindable(this.note.slug);
        this.makeFindableState = 'ready';
    };

    runStructure = async () => {
        this.structureState = 'loading';
        this.structure = await fetchStructure(this.note.slug);
        this.structureState = 'ready';
    };

    runClusterProject = async () => {
        this.clusterState = 'loading';
        this.clusters = await fetchClusterProject(this.note.slug);
        this.clusterState = 'ready';
    };
}

export function setAssistPanelState(
    note: Note,
    suggestedPhase: string,
    phases: PhaseOption[],
): AssistPanelState {
    return setContext(KEY, new AssistPanelStateClass(note, suggestedPhase, phases));
}

export function getAssistPanelState(): AssistPanelState {
    return getContext<AssistPanelState>(KEY);
}
