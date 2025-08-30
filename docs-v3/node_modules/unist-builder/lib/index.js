/**
 * @typedef {import('unist').Node} Node
 */

/**
 * @typedef {Array<Node> | string} ChildrenOrValue
 *   List to use as `children` or value to use as `value`.
 *
 * @typedef {Record<string, unknown>} Props
 *   Other fields to add to the node.
 */

/**
 * Build a node.
 *
 * @template {string} T
 * @template {Props} P
 * @template {Array<Node>} C
 *
 * @overload
 * @param {T} type
 * @returns {{type: T}}
 *
 * @overload
 * @param {T} type
 * @param {P} props
 * @returns {{type: T} & P}
 *
 * @overload
 * @param {T} type
 * @param {string} value
 * @returns {{type: T, value: string}}
 *
 * @overload
 * @param {T} type
 * @param {P} props
 * @param {string} value
 * @returns {{type: T, value: string} & P}
 *
 * @overload
 * @param {T} type
 * @param {C} children
 * @returns {{type: T, children: C}}
 *
 * @overload
 * @param {T} type
 * @param {P} props
 * @param {C} children
 * @returns {{type: T, children: C} & P}
 *
 * @param {string} type
 *   Node type.
 * @param {ChildrenOrValue | Props | null | undefined} [props]
 *   Fields assigned to node (default: `undefined`).
 * @param {ChildrenOrValue | null | undefined} [value]
 *   Children of node or value of `node` (cast to string).
 * @returns {Node}
 *   Built node.
 */
export function u(type, props, value) {
  /** @type {Node} */
  const node = {type: String(type)}

  if (
    (value === undefined || value === null) &&
    (typeof props === 'string' || Array.isArray(props))
  ) {
    value = props
  } else {
    Object.assign(node, props)
  }

  if (Array.isArray(value)) {
    // @ts-expect-error: create a parent.
    node.children = value
  } else if (value !== undefined && value !== null) {
    // @ts-expect-error: create a literal.
    node.value = String(value)
  }

  return node
}
