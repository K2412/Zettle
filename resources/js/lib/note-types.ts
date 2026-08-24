import type { NoteType } from '@/types';

export const NOTE_TYPES: { value: NoteType; label: string }[] = [
    { value: 'fleeting', label: 'Fleeting' },
    { value: 'literature', label: 'Literature' },
    { value: 'permanent', label: 'Permanent' },
    { value: 'structure', label: 'Structure' },
    { value: 'project', label: 'Project' },
];

export function noteTypeLabel(type: NoteType): string {
    return NOTE_TYPES.find((t) => t.value === type)?.label ?? type;
}
