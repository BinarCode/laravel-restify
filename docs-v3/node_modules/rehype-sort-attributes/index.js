/**
 * rehype plugin to sort attributes.
 *
 * ## What is this?
 *
 * This package is a plugin that sorts attributes based on how frequent they
 * occur, which optimizes for repetition-based compression (such as GZip).
 *
 * ## When should I use this?
 *
 * You can use this plugin when you want to improve the transfer size of HTML
 * documents.
 *
 * ## API
 *
 * ### `unified().use(rehypeSortAttributeValues)`
 *
 * Sort attributes.
 *
 * ###### Returns
 *
 * Transform ([`Transformer`](https://github.com/unifiedjs/unified#transformer)).
 *
 * @example
 *   {}
 *   <div id="foo">bar</div>
 *   <div class="baz" id="qux">quux</div>
 */

export {default} from './lib/index.js'
