/**
 * @import {Root} from 'hast'
 */

import {isElement} from 'hast-util-is-element'
import {visit} from 'unist-util-visit'
import {schema} from './schema.js'

/**
 * Sort attribute values.
 *
 * @returns
 *   Transform.
 */
export default function rehypeSortAttributeValues() {
  /**
   * @param {Root} tree
   *   Tree.
   * @returns {undefined}
   *   Nothing.
   */
  return function (tree) {
    // Map of properties to property values to counts.
    /** @type {Map<string, Map<number | string, number>>} */
    const counts = new Map()
    // List of all arrays, with their property names, so we don’t walk twice.
    /** @type {Array<[string, Array<number | string>]>} */
    const queues = []

    visit(tree, 'element', function (node) {
      /** @type {string} */
      let property

      for (property in node.properties) {
        if (Object.hasOwn(node.properties, property)) {
          const value = node.properties[property]

          if (
            Object.hasOwn(schema, property) &&
            isElement(node, schema[property]) &&
            Array.isArray(value)
          ) {
            add(property, value)
          }
        }
      }
    })

    flush()

    /**
     * @param {string} property
     *   Property name.
     * @param {Array<number | string>} values
     *   Values.
     * @returns {undefined}
     *   Nothing.
     */
    function add(property, values) {
      let index = -1
      let cache = counts.get(property)

      if (!cache) {
        cache = new Map()
        counts.set(property, cache)
      }

      while (++index < values.length) {
        const value = values[index]
        cache.set(value, (cache.get(value) || 0) + 1)
      }

      queues.push([property, values])
    }

    /**
     * @returns {undefined}
     *   Nothing.
     */
    function flush() {
      /** @type {Map<string, Array<number | string>>} */
      const caches = new Map()

      for (const [property, cache] of counts) {
        caches.set(
          property,
          [...cache.entries()]
            .sort(function (a, b) {
              return b[1] - a[1] || compare(String(a[0]), String(b[0]), 0)
            })
            .map(function (d) {
              return d[0]
            })
        )
      }

      let index = -1

      while (++index < queues.length) {
        const queue = queues[index]
        const cache = caches.get(queue[0])
        if (cache) {
          queue[1].sort(function (a, b) {
            return cache.indexOf(a) - cache.indexOf(b)
          })
        }
      }
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
