/** "Ada Obi" → "AO" (first letters of the first two words), as the Blade pages did. */
export function initials(name: string): string {
    const [first = '', second = ''] = name.trim().split(/\s+/);
    return (first.charAt(0) + second.charAt(0)).toUpperCase();
}
