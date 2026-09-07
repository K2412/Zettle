/** Turn a raw enum value (`project_only`, `permanent`) into a readable label. */
export function humanize(value: string): string {
    const spaced = value.replace(/_/g, ' ');

    return spaced.charAt(0).toUpperCase() + spaced.slice(1);
}
