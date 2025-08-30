/**
 * rehype plugin to sort attribute values.
 *
 * ## What is this?
 *
 * This package is a plugin that sorts attribute values, which optimizes for
 * repetition-based compression (such as GZip).
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
 * Sort attribute values.
 *
 * ###### Returns
 *
 * Transform ([`Transformer`](https://github.com/unifiedjs/unified#transformer)).
 *
 * @example
 *   {}
 *   <div class="qux quux foo bar"></div>
 */

export {default} from './lib/index.js'
