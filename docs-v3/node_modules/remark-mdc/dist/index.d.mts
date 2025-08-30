import { Plugin } from 'unified';
import * as micromark_util_types from 'micromark-util-types';

interface ComponentHandler {
  name: string
  instance: any
  options?: any
}

interface RemarkMDCOptions {
  components?: ComponentHandler[]
  maxAttributesLength?: number
  autoUnwrap?: boolean | {
    safeTypes?: Array<string>
  }
  yamlCodeBlockProps?: boolean
  experimental?: {
    /**
     * @deprecated This feature is out of experimental, use `autoUnwrap`
     */
    autoUnwrap?: boolean
    /**
     * @deprecated This feature is out of experimental, use `yamlCodeBlockProps`
     */
    componentCodeBlockYamlProps?: boolean
  }
}

declare function stringifyFrontMatter(data: any, content?: string): string;
declare function parseFrontMatter(content: string): {
    content: string;
    data: Record<string, any>;
};

/**
 * Based on: https://github.com/micromark/micromark-extension-directive
 * Version: 2.1.0
 * License: MIT (https://github.com/micromark/micromark-extension-directive/blob/main/license)
 */
declare function micromarkComponentsExtension(): {
    text: {
        [x: number]: {
            tokenize: (this: micromark_util_types.TokenizeContext, effects: micromark_util_types.Effects, ok: micromark_util_types.State, nok: micromark_util_types.State) => (code: micromark_util_types.Code) => micromark_util_types.State | undefined;
            previous: (this: micromark_util_types.TokenizeContext, code: micromark_util_types.Code) => boolean;
        } | {
            tokenize: (this: micromark_util_types.TokenizeContext, effects: micromark_util_types.Effects, ok: micromark_util_types.State, nok: micromark_util_types.State) => (code: micromark_util_types.Code) => micromark_util_types.State | undefined;
        }[];
    };
    flow: {
        [x: number]: {
            tokenize: (this: micromark_util_types.TokenizeContext, effects: micromark_util_types.Effects, ok: micromark_util_types.State, nok: micromark_util_types.State) => micromark_util_types.State;
        }[];
    };
    flowInitial: {
        [x: number]: {
            tokenize: (this: micromark_util_types.TokenizeContext, effects: micromark_util_types.Effects, ok: micromark_util_types.State, nok: micromark_util_types.State) => micromark_util_types.State;
        };
        '-2': {
            tokenize: (this: micromark_util_types.TokenizeContext, effects: micromark_util_types.Effects, ok: micromark_util_types.State, nok: micromark_util_types.State) => micromark_util_types.State;
        };
        '-1': {
            tokenize: (this: micromark_util_types.TokenizeContext, effects: micromark_util_types.Effects, ok: micromark_util_types.State, nok: micromark_util_types.State) => micromark_util_types.State;
        };
    };
};

declare function convertHtmlEntitiesToChars(text: string): string;

declare module 'unist' {
    interface Data {
        hName?: string;
        hProperties?: Record<string, any>;
    }
}
declare const _default: Plugin<RemarkMDCOptions[]>;

export { convertHtmlEntitiesToChars, _default as default, micromarkComponentsExtension as micromarkExtension, parseFrontMatter, stringifyFrontMatter };
