/***
 * Replace tabs with spaces, being smart about which column the tab is at and
 * which size should be used.
 *
 * @param {string} value
 *   Value with tabs.
 * @param {number} [tabSize=4]
 *   Tab size.
 * @returns {string}
 *   Value with spaces.
 */
export function detab(value: string, tabSize?: number | undefined): string
