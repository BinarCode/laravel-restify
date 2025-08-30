import { walkResolver } from 'unhead/utils';
export * from 'unhead/utils';
import { V as VueResolver } from './shared/vue.N9zWjxoK.mjs';
import 'vue';

function resolveUnrefHeadInput(input) {
  return walkResolver(input, VueResolver);
}

export { VueResolver, resolveUnrefHeadInput };
