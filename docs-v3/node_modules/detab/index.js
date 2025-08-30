const search = /[\t\n\r]/g

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
export function detab(value, tabSize = 4) {
  /** @type {Array<string>} */
  const result = []
  let start = 0
  let index = 0
  let column = -1

  if (typeof value !== 'string') {
    throw new TypeError('detab expected string')
  }

  while (index < value.length) {
    search.lastIndex = index
    const match = search.exec(value)
    const end = match ? match.index : value.length

    if (value.codePointAt(end) === 9) {
      const add = tabSize - ((column + end - index + 1) % tabSize)
      result.push(value.slice(start, end), ' '.repeat(add))
      column += end - index + add
      start = end + 1
    } else {
      column = -1
    }

    index = end + 1
  }

  result.push(value.slice(start))

  return result.join('')
}
