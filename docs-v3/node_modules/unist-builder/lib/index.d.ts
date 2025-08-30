export function u<
  T extends string,
  P extends Record<string, unknown>,
  C extends import('unist').Node[]
>(
  type: T
): {
  type: T
}
export function u<
  T extends string,
  P extends Record<string, unknown>,
  C extends import('unist').Node[]
>(
  type: T,
  props: P
): {
  type: T
} & P
export function u<
  T extends string,
  P extends Record<string, unknown>,
  C extends import('unist').Node[]
>(
  type: T,
  value: string
): {
  type: T
  value: string
}
export function u<
  T extends string,
  P extends Record<string, unknown>,
  C extends import('unist').Node[]
>(
  type: T,
  props: P,
  value: string
): {
  type: T
  value: string
} & P
export function u<
  T extends string,
  P extends Record<string, unknown>,
  C extends import('unist').Node[]
>(
  type: T,
  children: C
): {
  type: T
  children: C
}
export function u<
  T extends string,
  P extends Record<string, unknown>,
  C extends import('unist').Node[]
>(
  type: T,
  props: P,
  children: C
): {
  type: T
  children: C
} & P
export type Node = import('unist').Node
/**
 * List to use as `children` or value to use as `value`.
 */
export type ChildrenOrValue = Array<Node> | string
/**
 * Other fields to add to the node.
 */
export type Props = Record<string, unknown>
