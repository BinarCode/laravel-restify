/**
 * @import {Properties, Root} from 'hast'
 */

import {visit} from 'unist-util-visit'

/**
 * Sort attributes.
 *
 * @returns
 *   Transform.
 */
export default function rehypeSortAttributes() {
  /**
   * @param {Root} tree
   *   Tree.
   * @returns {undefined}
   *   Nothing.
   */
  return function (tree) {
    // Map of tag names to property names to counts.
    /** @type {Map<string, Map<string, number>>} */
    const counts = new Map()

    visit(tree, 'element', function (node) {
      let cache = counts.get(node.tagName)

      if (!cache) {
        cache = new Map()
        counts.set(node.tagName, cache)
      }

      /** @type {string} */
      let property

      for (property in node.properties) {
        if (Object.hasOwn(node.properties, property)) {
          cache.set(property, (cache.get(property) || 0) + 1)
        }
      }
    })

    const caches = optimize()

    visit(tree, 'element', function (node) {
      const cache = caches.get(node.tagName)

      if (cache) {
        /** @type {Array<string>} */
        const keys = []
        /** @type {Properties} */
        const result = {}
        let index = -1
        /** @type {string} */
        let property

        for (property in node.properties) {
          if (Object.hasOwn(node.properties, property)) {
            keys.push(property)
          }
        }

        keys.sort(function (a, b) {
          return cache.indexOf(a) - cache.indexOf(b)
        })

        while (++index < keys.length) {
          result[keys[index]] = node.properties[keys[index]]
        }

        node.properties = result
      }
    })

    /**
     * @returns
     *   Optimized caches.
     */
    function optimize() {
      // Map of tag names to sorted property names.
      /** @type {Map<string, Array<string>>} */
      const caches = new Map()

      for (const [name, properties] of counts.entries()) {
        caches.set(
          name,
          [...properties.entries()]
            .sort(function (a, b) {
              return b[1] - a[1] || compare(String(a[0]), String(b[0]), 0)
            })
            .map(function (d) {
              return d[0]
            })
        )
      }

      return caches
    }
  }
}

/**
 * This would create an infinite loop if `a` and `b` could be equal, but the
 * list we operate on only has unique values.
 *
 * @param {string} a
 *   Left value.
 * @param {string} b
 *   Right value.
 * @param {number} index
 *   Current index in values.
 * @returns {number}
 *   Order.
 */
function compare(a, b, index) {
  return (
    (a.charCodeAt(index) || 0) - (b.charCodeAt(index) || 0) ||
    compare(a, b, index + 1)
  )
}
