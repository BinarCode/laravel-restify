import { isRef, toRefs as toRefs$1, customRef, toValue, onMounted, nextTick, watch, getCurrentScope, onScopeDispose, getCurrentInstance, shallowRef, computed, unref, ref, defineComponent, mergeModels, useModel, watchEffect, withDirectives, createElementBlock, openBlock, createCommentVNode, createElementVNode, withModifiers, vShow, reactive, useTemplateRef, normalizeClass, normalizeStyle, toDisplayString, Fragment, createVNode, defineCustomElement } from 'vue';

const css = `*{box-sizing:border-box;border-style:solid;border-width:0;border-color:var(--un-default-border-color,#e5e7eb)}:before{box-sizing:border-box;border-style:solid;border-width:0;border-color:var(--un-default-border-color,#e5e7eb)}:after{box-sizing:border-box;border-style:solid;border-width:0;border-color:var(--un-default-border-color,#e5e7eb)}:before{--un-content:""}:after{--un-content:""}html{-webkit-text-size-adjust:100%;tab-size:4;font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent;font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Noto Sans,Ubuntu,Cantarell,Helvetica Neue,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;line-height:1.5}:host{-webkit-text-size-adjust:100%;tab-size:4;font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent;font-family:ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Noto Sans,Ubuntu,Cantarell,Helvetica Neue,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;line-height:1.5}body{line-height:inherit;margin:0}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,samp,pre{font-feature-settings:normal;font-variation-settings:normal;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace;font-size:1em}small{font-size:80%}sub,sup{vertical-align:baseline;font-size:75%;line-height:0;position:relative}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}button,input,optgroup,select,textarea{font-feature-settings:inherit;font-variation-settings:inherit;font-family:inherit;font-size:100%;font-weight:inherit;line-height:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}button{-webkit-appearance:button;background-color:transparent;background-image:none}[type=button]{-webkit-appearance:button;background-color:transparent;background-image:none}[type=reset]{-webkit-appearance:button;background-color:transparent;background-image:none}[type=submit]{-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}progress{vertical-align:baseline}::-webkit-inner-spin-button{height:auto}::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}blockquote,dl,dd,h1,h2,h3,h4,h5,h6,hr,figure,p,pre{margin:0}fieldset{margin:0;padding:0}legend{padding:0}ol,ul,menu{margin:0;padding:0;list-style:none}dialog{padding:0}textarea{resize:vertical}input::placeholder{opacity:1;color:#9ca3af}textarea::placeholder{opacity:1;color:#9ca3af}button{cursor:pointer}[role=button]{cursor:pointer}:disabled{cursor:default}img,svg,video,canvas,audio,iframe,embed,object{vertical-align:middle;display:block}img,video{max-width:100%;height:auto}[hidden]:where(:not([hidden=until-found])){display:none}:root{--un-text-opacity:100%}@property --nuxt-devtools-inspect-border-angle{syntax:"<angle>";inherits:false;initial-value:90deg}.nuxt-devtools-inspect-running-border{filter:blur(10px);--nuxt-devtools-inspect-border-angle:0deg;background:conic-gradient(from var(--nuxt-devtools-inspect-border-angle),#00dc82,#00e6e8,#42e200,#0086e2,#00dc82)border-box;-webkit-mask:linear-gradient(#fff 0,#fff 0) padding-box,linear-gradient(#fff 0,#fff 0);-webkit-mask-composite:destination-out;transition:all .5s;animation:1s linear infinite color-rotate-background;mask-image:linear-gradient(#fff 0,#fff 0),linear-gradient(#fff 0,#fff 0);mask-position:0 0,0 0;mask-size:auto,auto;mask-repeat:repeat,repeat;mask-clip:padding-box,border-box;mask-origin:padding-box,border-box;mask-composite:exclude;mask-mode:match-source,match-source}.nuxt-devtools-inspect-neon{background-image:linear-gradient(90deg,#00dc82,#00e6e8,#42e200,#00dc82,#00e6e8,#42e200,#00dc82,#00e6e8,#42e200);background-position:0;background-size:400% 400%}.nuxt-devtools-inspect-neon.running{animation:3s linear infinite neon-background}@keyframes neon-background{0%{background-position:0%}50%{background-position:100%}to{background-position:0%}}.nuxt-devtools-frame{z-index:2147483645;-webkit-font-smoothing:antialiased;width:100%;height:100%;position:fixed}.nuxt-devtools-frame iframe{background:var(--nuxt-devtools-widget-bg);border:1px solid rgba(125,125,125,.2);-webkit-border-radius:10px;border-radius:10px;outline:none;width:100%;height:100%}.nuxt-devtools-resize-handle-horizontal{cursor:ns-resize;-webkit-border-radius:5px;border-radius:5px;height:10px;margin:-5px 0;position:absolute;left:6px;right:6px}.nuxt-devtools-resize-handle-vertical{cursor:ew-resize;-webkit-border-radius:5px;border-radius:5px;width:10px;margin:0 -5px;position:absolute;top:6px;bottom:0}.nuxt-devtools-resize-handle-corner{-webkit-border-radius:6px;border-radius:6px;width:14px;height:14px;margin:-6px;position:absolute}.nuxt-devtools-resize-handle:hover{background:rgba(125,125,125,.1)}#nuxt-devtools-anchor{z-index:2147483645;transform-origin:50%;box-sizing:border-box;width:0;font-family:Arial,Helvetica,sans-serif;position:fixed;transform:translate(-50%,-50%)rotate(0);font-size:15px!important}#nuxt-devtools-anchor *{box-sizing:border-box}#nuxt-devtools-anchor button{cursor:pointer;color:inherit;background:0 0;border:none;outline:none;margin:0;padding:0}#nuxt-devtools-anchor .nuxt-devtools-label{align-items:center;justify-items:center;gap:3px;padding:0 7px 0 8px;font-size:.8em;line-height:1em;display:flex}#nuxt-devtools-anchor .nuxt-devtools-label .nuxt-devtools-label-main{opacity:.8;white-space:nowrap}#nuxt-devtools-anchor .nuxt-devtools-label .nuxt-devtools-label-secondary{opacity:.5;white-space:nowrap;font-size:.8em;line-height:.6em}#nuxt-devtools-anchor .nuxt-devtools-nuxt-button{flex:none}#nuxt-devtools-anchor.nuxt-devtools-vertical .nuxt-devtools-nuxt-button{transform:rotate(-90deg)}#nuxt-devtools-anchor.nuxt-devtools-vertical .nuxt-devtools-label{flex-direction:column;gap:2px;padding:0 10px;transform:rotate(-90deg)}#nuxt-devtools-anchor .nuxt-devtools-panel{border:1px solid var(--nuxt-devtools-widget-border);background-color:var(--nuxt-devtools-widget-bg);backdrop-filter:blur(10px);height:30px;color:var(--nuxt-devtools-widget-fg);box-shadow:2px 2px 8px var(--nuxt-devtools-widget-shadow);user-select:none;touch-action:none;-webkit-border-radius:100px;border-radius:100px;justify-content:flex-start;align-items:center;gap:2px;max-width:150px;padding:2px 2px 2px 2.5px;transition:all .6s,max-width .6s,padding .5s,transform .4s,opacity .2s;display:flex;position:absolute;top:0;left:0;overflow:hidden;transform:translate(-50%,-50%)}#nuxt-devtools-anchor.nuxt-devtools-hide .nuxt-devtools-panel{max-width:32px;padding:2px 0}#nuxt-devtools-anchor.nuxt-devtools-vertical .nuxt-devtools-panel{box-shadow:2px -2px 8px var(--nuxt-devtools-widget-shadow);transform:translate(-50%,-50%)rotate(90deg)}#nuxt-devtools-anchor .nuxt-devtools-panel-content{transition:opacity .4s}#nuxt-devtools-anchor.nuxt-devtools-hide .nuxt-devtools-panel-content{opacity:0}#nuxt-devtools-anchor .nuxt-devtools-icon-button{opacity:.8;border-width:0;-webkit-border-radius:100%;border-radius:100%;justify-content:center;align-items:center;width:30px;height:30px;transition:opacity .2s ease-in-out;display:flex}#nuxt-devtools-anchor .nuxt-devtools-icon-button:hover{opacity:1}#nuxt-devtools-anchor:hover .nuxt-devtools-glowing{opacity:.6}#nuxt-devtools-anchor .nuxt-devtools-glowing{opacity:0;pointer-events:none;z-index:-1;filter:blur(60px);background-image:linear-gradient(45deg,#00dc82,#00dc82,#00dc82);-webkit-border-radius:9999px;border-radius:9999px;width:160px;height:160px;transition:all 1s;position:absolute;top:0;left:0;transform:translate(-50%,-50%)}@media print{#nuxt-devtools-anchor{display:none}}*{--un-rotate:0;--un-rotate-x:0;--un-rotate-y:0;--un-rotate-z:0;--un-scale-x:1;--un-scale-y:1;--un-scale-z:1;--un-skew-x:0;--un-skew-y:0;--un-translate-x:0;--un-translate-y:0;--un-translate-z:0;--un-pan-x: ;--un-pan-y: ;--un-pinch-zoom: ;--un-scroll-snap-strictness:proximity;--un-ordinal: ;--un-slashed-zero: ;--un-numeric-figure: ;--un-numeric-spacing: ;--un-numeric-fraction: ;--un-border-spacing-x:0;--un-border-spacing-y:0;--un-ring-offset-shadow:0 0 transparent;--un-ring-shadow:0 0 transparent;--un-shadow-inset: ;--un-shadow:0 0 transparent;--un-ring-inset: ;--un-ring-offset-width:0px;--un-ring-offset-color:#fff;--un-ring-width:0px;--un-ring-color:rgba(147,197,253,.5);--un-blur: ;--un-brightness: ;--un-contrast: ;--un-drop-shadow: ;--un-grayscale: ;--un-hue-rotate: ;--un-invert: ;--un-saturate: ;--un-sepia: ;--un-backdrop-blur: ;--un-backdrop-brightness: ;--un-backdrop-contrast: ;--un-backdrop-grayscale: ;--un-backdrop-hue-rotate: ;--un-backdrop-invert: ;--un-backdrop-opacity: ;--un-backdrop-saturate: ;--un-backdrop-sepia: }:before{--un-rotate:0;--un-rotate-x:0;--un-rotate-y:0;--un-rotate-z:0;--un-scale-x:1;--un-scale-y:1;--un-scale-z:1;--un-skew-x:0;--un-skew-y:0;--un-translate-x:0;--un-translate-y:0;--un-translate-z:0;--un-pan-x: ;--un-pan-y: ;--un-pinch-zoom: ;--un-scroll-snap-strictness:proximity;--un-ordinal: ;--un-slashed-zero: ;--un-numeric-figure: ;--un-numeric-spacing: ;--un-numeric-fraction: ;--un-border-spacing-x:0;--un-border-spacing-y:0;--un-ring-offset-shadow:0 0 transparent;--un-ring-shadow:0 0 transparent;--un-shadow-inset: ;--un-shadow:0 0 transparent;--un-ring-inset: ;--un-ring-offset-width:0px;--un-ring-offset-color:#fff;--un-ring-width:0px;--un-ring-color:rgba(147,197,253,.5);--un-blur: ;--un-brightness: ;--un-contrast: ;--un-drop-shadow: ;--un-grayscale: ;--un-hue-rotate: ;--un-invert: ;--un-saturate: ;--un-sepia: ;--un-backdrop-blur: ;--un-backdrop-brightness: ;--un-backdrop-contrast: ;--un-backdrop-grayscale: ;--un-backdrop-hue-rotate: ;--un-backdrop-invert: ;--un-backdrop-opacity: ;--un-backdrop-saturate: ;--un-backdrop-sepia: }:after{--un-rotate:0;--un-rotate-x:0;--un-rotate-y:0;--un-rotate-z:0;--un-scale-x:1;--un-scale-y:1;--un-scale-z:1;--un-skew-x:0;--un-skew-y:0;--un-translate-x:0;--un-translate-y:0;--un-translate-z:0;--un-pan-x: ;--un-pan-y: ;--un-pinch-zoom: ;--un-scroll-snap-strictness:proximity;--un-ordinal: ;--un-slashed-zero: ;--un-numeric-figure: ;--un-numeric-spacing: ;--un-numeric-fraction: ;--un-border-spacing-x:0;--un-border-spacing-y:0;--un-ring-offset-shadow:0 0 transparent;--un-ring-shadow:0 0 transparent;--un-shadow-inset: ;--un-shadow:0 0 transparent;--un-ring-inset: ;--un-ring-offset-width:0px;--un-ring-offset-color:#fff;--un-ring-width:0px;--un-ring-color:rgba(147,197,253,.5);--un-blur: ;--un-brightness: ;--un-contrast: ;--un-drop-shadow: ;--un-grayscale: ;--un-hue-rotate: ;--un-invert: ;--un-saturate: ;--un-sepia: ;--un-backdrop-blur: ;--un-backdrop-brightness: ;--un-backdrop-contrast: ;--un-backdrop-grayscale: ;--un-backdrop-hue-rotate: ;--un-backdrop-invert: ;--un-backdrop-opacity: ;--un-backdrop-saturate: ;--un-backdrop-sepia: }::backdrop{--un-rotate:0;--un-rotate-x:0;--un-rotate-y:0;--un-rotate-z:0;--un-scale-x:1;--un-scale-y:1;--un-scale-z:1;--un-skew-x:0;--un-skew-y:0;--un-translate-x:0;--un-translate-y:0;--un-translate-z:0;--un-pan-x: ;--un-pan-y: ;--un-pinch-zoom: ;--un-scroll-snap-strictness:proximity;--un-ordinal: ;--un-slashed-zero: ;--un-numeric-figure: ;--un-numeric-spacing: ;--un-numeric-fraction: ;--un-border-spacing-x:0;--un-border-spacing-y:0;--un-ring-offset-shadow:0 0 transparent;--un-ring-shadow:0 0 transparent;--un-shadow-inset: ;--un-shadow:0 0 transparent;--un-ring-inset: ;--un-ring-offset-width:0px;--un-ring-offset-color:#fff;--un-ring-width:0px;--un-ring-color:rgba(147,197,253,.5);--un-blur: ;--un-brightness: ;--un-contrast: ;--un-drop-shadow: ;--un-grayscale: ;--un-hue-rotate: ;--un-invert: ;--un-saturate: ;--un-sepia: ;--un-backdrop-blur: ;--un-backdrop-brightness: ;--un-backdrop-contrast: ;--un-backdrop-grayscale: ;--un-backdrop-hue-rotate: ;--un-backdrop-invert: ;--un-backdrop-opacity: ;--un-backdrop-saturate: ;--un-backdrop-sepia: }.i-ph-arrow-bend-left-up-duotone{--un-icon:url("data:image/svg+xml;utf8,%3Csvg viewBox='0 0 256 256' width='1em' height='1em' xmlns='http://www.w3.org/2000/svg' %3E%3Cg fill='currentColor'%3E%3Cpath d='M152 80H56l48-48Z' opacity='.2'/%3E%3Cpath d='M200 216a88.1 88.1 0 0 1-88-88V88h40a8 8 0 0 0 5.66-13.66l-48-48a8 8 0 0 0-11.32 0l-48 48A8 8 0 0 0 56 88h40v40a104.11 104.11 0 0 0 104 104a8 8 0 0 0 0-16M104 43.31L132.69 72H75.31Z'/%3E%3C/g%3E%3C/svg%3E");-webkit-mask:var(--un-icon)no-repeat;mask:var(--un-icon)no-repeat;color:inherit;background-color:currentColor;width:1em;height:1em;mask-size:100% 100%}.i-ph-arrow-up-right-light{--un-icon:url("data:image/svg+xml;utf8,%3Csvg viewBox='0 0 256 256' width='1em' height='1em' xmlns='http://www.w3.org/2000/svg' %3E%3Cpath fill='currentColor' d='M198 64v104a6 6 0 0 1-12 0V78.48L68.24 196.24a6 6 0 0 1-8.48-8.48L177.52 70H88a6 6 0 0 1 0-12h104a6 6 0 0 1 6 6'/%3E%3C/svg%3E");-webkit-mask:var(--un-icon)no-repeat;mask:var(--un-icon)no-repeat;color:inherit;background-color:currentColor;width:1em;height:1em;mask-size:100% 100%}.i-ph-x{--un-icon:url("data:image/svg+xml;utf8,%3Csvg viewBox='0 0 256 256' width='1em' height='1em' xmlns='http://www.w3.org/2000/svg' %3E%3Cpath fill='currentColor' d='M205.66 194.34a8 8 0 0 1-11.32 11.32L128 139.31l-66.34 66.35a8 8 0 0 1-11.32-11.32L116.69 128L50.34 61.66a8 8 0 0 1 11.32-11.32L128 116.69l66.34-66.35a8 8 0 0 1 11.32 11.32L139.31 128Z'/%3E%3C/svg%3E");-webkit-mask:var(--un-icon)no-repeat;mask:var(--un-icon)no-repeat;color:inherit;background-color:currentColor;width:1em;height:1em;mask-size:100% 100%}.container{width:100%}.bg-glass{--un-backdrop-blur:blur(5px);backdrop-filter:var(--un-backdrop-blur)var(--un-backdrop-brightness)var(--un-backdrop-contrast)var(--un-backdrop-grayscale)var(--un-backdrop-hue-rotate)var(--un-backdrop-invert)var(--un-backdrop-opacity)var(--un-backdrop-saturate)var(--un-backdrop-sepia);background-color:rgba(255,255,255,.75)}.color-base{--un-text-opacity:1;color:rgba(38,38,38,var(--un-text-opacity))}.ring-base{--un-ring-opacity:.13;--un-ring-color:rgba(136,136,136,var(--un-ring-opacity))}@media (prefers-color-scheme:dark){.bg-glass{background-color:rgba(17,17,17,.75)}.color-base{--un-text-opacity:1;color:rgba(229,229,229,var(--un-text-opacity))}}@media (min-width:640px){.container{max-width:640px}}@media (min-width:768px){.container{max-width:768px}}@media (min-width:1024px){.container{max-width:1024px}}@media (min-width:1280px){.container{max-width:1280px}}@media (min-width:1536px){.container{max-width:1536px}}.pointer-events-none{pointer-events:none}.disabled\\:pointer-events-none:disabled{pointer-events:none}.absolute{position:absolute}.fixed{position:fixed}.relative{position:relative}.inset-0{top:0;bottom:0;left:0;right:0}.z-10{z-index:10}.z-9999999{z-index:9999999}.ms:not(:is(:lang(ae),:lang(ar),:lang(arc),:lang(bcc),:lang(bqi),:lang(ckb),:lang(dv),:lang(fa),:lang(glk),:lang(he),:lang(ku),:lang(mzn),:lang(nqo),:lang(pnb),:lang(ps),:lang(sd),:lang(ug),:lang(ur),:lang(yi))){margin-left:1rem}.ms:is(:lang(ae),:lang(ar),:lang(arc),:lang(bcc),:lang(bqi),:lang(ckb),:lang(dv),:lang(fa),:lang(glk),:lang(he),:lang(ku),:lang(mzn),:lang(nqo),:lang(pnb),:lang(ps),:lang(sd),:lang(ug),:lang(ur),:lang(yi)){margin-right:1rem}.mt--2{margin-top:-.5rem}.w-400px{width:400px}.flex{display:flex}.flex-auto{flex:auto}.flex-none{flex:none}.flex-col{flex-direction:column}.transform{transform:translateX(var(--un-translate-x))translateY(var(--un-translate-y))translateZ(var(--un-translate-z))rotate(var(--un-rotate))rotateX(var(--un-rotate-x))rotateY(var(--un-rotate-y))rotateZ(var(--un-rotate-z))skewX(var(--un-skew-x))skewY(var(--un-skew-y))scaleX(var(--un-scale-x))scaleY(var(--un-scale-y))scaleZ(var(--un-scale-z))}.resize{resize:both}.items-center{align-items:center}.gap-2{gap:.5rem}.of-hidden{overflow:hidden}.border-1\\.5{border-width:1.5px}.border-transparent{border-color:transparent}.rounded-lg{-webkit-border-radius:.5rem;border-radius:.5rem}.p2{padding:.5rem}.px{padding-left:1rem;padding-right:1rem}.text-lg{font-size:1.125rem;line-height:1.75rem}.text-sm{font-size:.875rem;line-height:1.25rem}.hover\\:text-green6:hover{--un-text-opacity:1;color:rgba(22,163,74,var(--un-text-opacity))}.font-mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,Liberation Mono,Courier New,monospace}.op0{opacity:0}.op100{opacity:1}.op50{opacity:.5}.hover\\:op100:hover{opacity:1}.disabled\\:op10\\!:disabled{opacity:.1!important}.shadow-lg{--un-shadow:var(--un-shadow-inset)0 10px 15px -3px var(--un-shadow-color,rgba(0,0,0,.1)),var(--un-shadow-inset)0 4px 6px -4px var(--un-shadow-color,rgba(0,0,0,.1));box-shadow:var(--un-ring-offset-shadow),var(--un-ring-shadow),var(--un-shadow)}.ring-1{--un-ring-width:1px;--un-ring-offset-shadow:var(--un-ring-inset)0 0 0 var(--un-ring-offset-width)var(--un-ring-offset-color);--un-ring-shadow:var(--un-ring-inset)0 0 0 calc(var(--un-ring-width) + var(--un-ring-offset-width))var(--un-ring-color);box-shadow:var(--un-ring-offset-shadow),var(--un-ring-shadow),var(--un-shadow)}.backdrop-blur{--un-backdrop-blur:blur(8px);backdrop-filter:var(--un-backdrop-blur)var(--un-backdrop-brightness)var(--un-backdrop-contrast)var(--un-backdrop-grayscale)var(--un-backdrop-hue-rotate)var(--un-backdrop-invert)var(--un-backdrop-opacity)var(--un-backdrop-saturate)var(--un-backdrop-sepia)}.transition-opacity{transition-property:opacity;transition-duration:.15s;transition-timing-function:cubic-bezier(.4,0,.2,1)}.duration-200{transition-duration:.2s}.duration-300{transition-duration:.3s}.transition-none{transition:none}`;

function tryOnScopeDispose(fn) {
  if (getCurrentScope()) {
    onScopeDispose(fn);
    return true;
  }
  return false;
}

const isClient = typeof window !== "undefined" && typeof document !== "undefined";
typeof WorkerGlobalScope !== "undefined" && globalThis instanceof WorkerGlobalScope;
const notNullish = (val) => val != null;
const toString = Object.prototype.toString;
const isObject = (val) => toString.call(val) === "[object Object]";
const noop = () => {
};
const isIOS = /* @__PURE__ */ getIsIOS();
function getIsIOS() {
  var _a, _b;
  return isClient && ((_a = window == null ? void 0 : window.navigator) == null ? void 0 : _a.userAgent) && (/iP(?:ad|hone|od)/.test(window.navigator.userAgent) || ((_b = window == null ? void 0 : window.navigator) == null ? void 0 : _b.maxTouchPoints) > 2 && /iPad|Macintosh/.test(window == null ? void 0 : window.navigator.userAgent));
}

function createFilterWrapper(filter, fn) {
  function wrapper(...args) {
    return new Promise((resolve, reject) => {
      Promise.resolve(filter(() => fn.apply(this, args), { fn, thisArg: this, args })).then(resolve).catch(reject);
    });
  }
  return wrapper;
}
function debounceFilter(ms, options = {}) {
  let timer;
  let maxTimer;
  let lastRejector = noop;
  const _clearTimeout = (timer2) => {
    clearTimeout(timer2);
    lastRejector();
    lastRejector = noop;
  };
  let lastInvoker;
  const filter = (invoke) => {
    const duration = toValue(ms);
    const maxDuration = toValue(options.maxWait);
    if (timer)
      _clearTimeout(timer);
    if (duration <= 0 || maxDuration !== void 0 && maxDuration <= 0) {
      if (maxTimer) {
        _clearTimeout(maxTimer);
        maxTimer = void 0;
      }
      return Promise.resolve(invoke());
    }
    return new Promise((resolve, reject) => {
      lastRejector = options.rejectOnCancel ? reject : resolve;
      lastInvoker = invoke;
      if (maxDuration && !maxTimer) {
        maxTimer = setTimeout(() => {
          if (timer)
            _clearTimeout(timer);
          maxTimer = void 0;
          resolve(lastInvoker());
        }, maxDuration);
      }
      timer = setTimeout(() => {
        if (maxTimer)
          _clearTimeout(maxTimer);
        maxTimer = void 0;
        resolve(invoke());
      }, duration);
    });
  };
  return filter;
}
function toArray(value) {
  return Array.isArray(value) ? value : [value];
}

function getLifeCycleTarget(target) {
  return getCurrentInstance();
}

// @__NO_SIDE_EFFECTS__
function useDebounceFn(fn, ms = 200, options = {}) {
  return createFilterWrapper(
    debounceFilter(ms, options),
    fn
  );
}

function toRefs(objectRef, options = {}) {
  if (!isRef(objectRef))
    return toRefs$1(objectRef);
  const result = Array.isArray(objectRef.value) ? Array.from({ length: objectRef.value.length }) : {};
  for (const key in objectRef.value) {
    result[key] = customRef(() => ({
      get() {
        return objectRef.value[key];
      },
      set(v) {
        var _a;
        const replaceRef = (_a = toValue(options.replaceRef)) != null ? _a : true;
        if (replaceRef) {
          if (Array.isArray(objectRef.value)) {
            const copy = [...objectRef.value];
            copy[key] = v;
            objectRef.value = copy;
          } else {
            const newObject = { ...objectRef.value, [key]: v };
            Object.setPrototypeOf(newObject, Object.getPrototypeOf(objectRef.value));
            objectRef.value = newObject;
          }
        } else {
          objectRef.value[key] = v;
        }
      }
    }));
  }
  return result;
}

function tryOnMounted(fn, sync = true, target) {
  const instance = getLifeCycleTarget();
  if (instance)
    onMounted(fn, target);
  else if (sync)
    fn();
  else
    nextTick(fn);
}

function watchImmediate(source, cb, options) {
  return watch(
    source,
    cb,
    {
      ...options,
      immediate: true
    }
  );
}

const defaultWindow = isClient ? window : void 0;

function unrefElement(elRef) {
  var _a;
  const plain = toValue(elRef);
  return (_a = plain == null ? void 0 : plain.$el) != null ? _a : plain;
}

function useEventListener(...args) {
  const cleanups = [];
  const cleanup = () => {
    cleanups.forEach((fn) => fn());
    cleanups.length = 0;
  };
  const register = (el, event, listener, options) => {
    el.addEventListener(event, listener, options);
    return () => el.removeEventListener(event, listener, options);
  };
  const firstParamTargets = computed(() => {
    const test = toArray(toValue(args[0])).filter((e) => e != null);
    return test.every((e) => typeof e !== "string") ? test : void 0;
  });
  const stopWatch = watchImmediate(
    () => {
      var _a, _b;
      return [
        (_b = (_a = firstParamTargets.value) == null ? void 0 : _a.map((e) => unrefElement(e))) != null ? _b : [defaultWindow].filter((e) => e != null),
        toArray(toValue(firstParamTargets.value ? args[1] : args[0])),
        toArray(unref(firstParamTargets.value ? args[2] : args[1])),
        // @ts-expect-error - TypeScript gets the correct types, but somehow still complains
        toValue(firstParamTargets.value ? args[3] : args[2])
      ];
    },
    ([raw_targets, raw_events, raw_listeners, raw_options]) => {
      cleanup();
      if (!(raw_targets == null ? void 0 : raw_targets.length) || !(raw_events == null ? void 0 : raw_events.length) || !(raw_listeners == null ? void 0 : raw_listeners.length))
        return;
      const optionsClone = isObject(raw_options) ? { ...raw_options } : raw_options;
      cleanups.push(
        ...raw_targets.flatMap(
          (el) => raw_events.flatMap(
            (event) => raw_listeners.map((listener) => register(el, event, listener, optionsClone))
          )
        )
      );
    },
    { flush: "post" }
  );
  const stop = () => {
    stopWatch();
    cleanup();
  };
  tryOnScopeDispose(cleanup);
  return stop;
}

let _iOSWorkaround = false;
function onClickOutside(target, handler, options = {}) {
  const { window = defaultWindow, ignore = [], capture = true, detectIframe = false, controls = false } = options;
  if (!window) {
    return controls ? { stop: noop, cancel: noop, trigger: noop } : noop;
  }
  if (isIOS && !_iOSWorkaround) {
    _iOSWorkaround = true;
    const listenerOptions = { passive: true };
    Array.from(window.document.body.children).forEach((el) => el.addEventListener("click", noop, listenerOptions));
    window.document.documentElement.addEventListener("click", noop, listenerOptions);
  }
  let shouldListen = true;
  const shouldIgnore = (event) => {
    return toValue(ignore).some((target2) => {
      if (typeof target2 === "string") {
        return Array.from(window.document.querySelectorAll(target2)).some((el) => el === event.target || event.composedPath().includes(el));
      } else {
        const el = unrefElement(target2);
        return el && (event.target === el || event.composedPath().includes(el));
      }
    });
  };
  function hasMultipleRoots(target2) {
    const vm = toValue(target2);
    return vm && vm.$.subTree.shapeFlag === 16;
  }
  function checkMultipleRoots(target2, event) {
    const vm = toValue(target2);
    const children = vm.$.subTree && vm.$.subTree.children;
    if (children == null || !Array.isArray(children))
      return false;
    return children.some((child) => child.el === event.target || event.composedPath().includes(child.el));
  }
  const listener = (event) => {
    const el = unrefElement(target);
    if (event.target == null)
      return;
    if (!(el instanceof Element) && hasMultipleRoots(target) && checkMultipleRoots(target, event))
      return;
    if (!el || el === event.target || event.composedPath().includes(el))
      return;
    if ("detail" in event && event.detail === 0)
      shouldListen = !shouldIgnore(event);
    if (!shouldListen) {
      shouldListen = true;
      return;
    }
    handler(event);
  };
  let isProcessingClick = false;
  const cleanup = [
    useEventListener(window, "click", (event) => {
      if (!isProcessingClick) {
        isProcessingClick = true;
        setTimeout(() => {
          isProcessingClick = false;
        }, 0);
        listener(event);
      }
    }, { passive: true, capture }),
    useEventListener(window, "pointerdown", (e) => {
      const el = unrefElement(target);
      shouldListen = !shouldIgnore(e) && !!(el && !e.composedPath().includes(el));
    }, { passive: true }),
    detectIframe && useEventListener(window, "blur", (event) => {
      setTimeout(() => {
        var _a;
        const el = unrefElement(target);
        if (((_a = window.document.activeElement) == null ? void 0 : _a.tagName) === "IFRAME" && !(el == null ? void 0 : el.contains(window.document.activeElement))) {
          handler(event);
        }
      }, 0);
    }, { passive: true })
  ].filter(Boolean);
  const stop = () => cleanup.forEach((fn) => fn());
  if (controls) {
    return {
      stop,
      cancel: () => {
        shouldListen = false;
      },
      trigger: (event) => {
        shouldListen = true;
        listener(event);
        shouldListen = false;
      }
    };
  }
  return stop;
}

// @__NO_SIDE_EFFECTS__
function useMounted() {
  const isMounted = shallowRef(false);
  const instance = getCurrentInstance();
  if (instance) {
    onMounted(() => {
      isMounted.value = true;
    }, instance);
  }
  return isMounted;
}

// @__NO_SIDE_EFFECTS__
function useSupported(callback) {
  const isMounted = useMounted();
  return computed(() => {
    isMounted.value;
    return Boolean(callback());
  });
}

function useMutationObserver(target, callback, options = {}) {
  const { window = defaultWindow, ...mutationOptions } = options;
  let observer;
  const isSupported = useSupported(() => window && "MutationObserver" in window);
  const cleanup = () => {
    if (observer) {
      observer.disconnect();
      observer = void 0;
    }
  };
  const targets = computed(() => {
    const value = toValue(target);
    const items = toArray(value).map(unrefElement).filter(notNullish);
    return new Set(items);
  });
  const stopWatch = watch(
    targets,
    (newTargets) => {
      cleanup();
      if (isSupported.value && newTargets.size) {
        observer = new MutationObserver(callback);
        newTargets.forEach((el) => observer.observe(el, mutationOptions));
      }
    },
    { immediate: true, flush: "post" }
  );
  const takeRecords = () => {
    return observer == null ? void 0 : observer.takeRecords();
  };
  const stop = () => {
    stopWatch();
    cleanup();
  };
  tryOnScopeDispose(stop);
  return {
    isSupported,
    stop,
    takeRecords
  };
}

function useCssVar(prop, target, options = {}) {
  const { window = defaultWindow, initialValue, observe = false } = options;
  const variable = shallowRef(initialValue);
  const elRef = computed(() => {
    var _a;
    return unrefElement(target) || ((_a = window == null ? void 0 : window.document) == null ? void 0 : _a.documentElement);
  });
  function updateCssVar() {
    var _a;
    const key = toValue(prop);
    const el = toValue(elRef);
    if (el && window && key) {
      const value = (_a = window.getComputedStyle(el).getPropertyValue(key)) == null ? void 0 : _a.trim();
      variable.value = value || variable.value || initialValue;
    }
  }
  if (observe) {
    useMutationObserver(elRef, updateCssVar, {
      attributeFilter: ["style", "class"],
      window
    });
  }
  watch(
    [elRef, () => toValue(prop)],
    (_, old) => {
      if (old[0] && old[1])
        old[0].style.removeProperty(old[1]);
      updateCssVar();
    },
    { immediate: true }
  );
  watch(
    [variable, elRef],
    ([val, el]) => {
      const raw_prop = toValue(prop);
      if ((el == null ? void 0 : el.style) && raw_prop) {
        if (val == null)
          el.style.removeProperty(raw_prop);
        else
          el.style.setProperty(raw_prop, val);
      }
    },
    { immediate: true }
  );
  return variable;
}

function useDraggable(target, options = {}) {
  var _a;
  const {
    pointerTypes,
    preventDefault,
    stopPropagation,
    exact,
    onMove,
    onEnd,
    onStart,
    initialValue,
    axis = "both",
    draggingElement = defaultWindow,
    containerElement,
    handle: draggingHandle = target,
    buttons = [0]
  } = options;
  const position = ref(
    (_a = toValue(initialValue)) != null ? _a : { x: 0, y: 0 }
  );
  const pressedDelta = ref();
  const filterEvent = (e) => {
    if (pointerTypes)
      return pointerTypes.includes(e.pointerType);
    return true;
  };
  const handleEvent = (e) => {
    if (toValue(preventDefault))
      e.preventDefault();
    if (toValue(stopPropagation))
      e.stopPropagation();
  };
  const start = (e) => {
    var _a2;
    if (!toValue(buttons).includes(e.button))
      return;
    if (toValue(options.disabled) || !filterEvent(e))
      return;
    if (toValue(exact) && e.target !== toValue(target))
      return;
    const container = toValue(containerElement);
    const containerRect = (_a2 = container == null ? void 0 : container.getBoundingClientRect) == null ? void 0 : _a2.call(container);
    const targetRect = toValue(target).getBoundingClientRect();
    const pos = {
      x: e.clientX - (container ? targetRect.left - containerRect.left + container.scrollLeft : targetRect.left),
      y: e.clientY - (container ? targetRect.top - containerRect.top + container.scrollTop : targetRect.top)
    };
    if ((onStart == null ? void 0 : onStart(pos, e)) === false)
      return;
    pressedDelta.value = pos;
    handleEvent(e);
  };
  const move = (e) => {
    if (toValue(options.disabled) || !filterEvent(e))
      return;
    if (!pressedDelta.value)
      return;
    const container = toValue(containerElement);
    const targetRect = toValue(target).getBoundingClientRect();
    let { x, y } = position.value;
    if (axis === "x" || axis === "both") {
      x = e.clientX - pressedDelta.value.x;
      if (container)
        x = Math.min(Math.max(0, x), container.scrollWidth - targetRect.width);
    }
    if (axis === "y" || axis === "both") {
      y = e.clientY - pressedDelta.value.y;
      if (container)
        y = Math.min(Math.max(0, y), container.scrollHeight - targetRect.height);
    }
    position.value = {
      x,
      y
    };
    onMove == null ? void 0 : onMove(position.value, e);
    handleEvent(e);
  };
  const end = (e) => {
    if (toValue(options.disabled) || !filterEvent(e))
      return;
    if (!pressedDelta.value)
      return;
    pressedDelta.value = void 0;
    onEnd == null ? void 0 : onEnd(position.value, e);
    handleEvent(e);
  };
  if (isClient) {
    const config = () => {
      var _a2;
      return {
        capture: (_a2 = options.capture) != null ? _a2 : true,
        passive: !toValue(preventDefault)
      };
    };
    useEventListener(draggingHandle, "pointerdown", start, config);
    useEventListener(draggingElement, "pointermove", move, config);
    useEventListener(draggingElement, "pointerup", end, config);
  }
  return {
    ...toRefs(position),
    position,
    isDragging: computed(() => !!pressedDelta.value),
    style: computed(
      () => `left:${position.value.x}px;top:${position.value.y}px;`
    )
  };
}

function useResizeObserver(target, callback, options = {}) {
  const { window = defaultWindow, ...observerOptions } = options;
  let observer;
  const isSupported = useSupported(() => window && "ResizeObserver" in window);
  const cleanup = () => {
    if (observer) {
      observer.disconnect();
      observer = void 0;
    }
  };
  const targets = computed(() => {
    const _targets = toValue(target);
    return Array.isArray(_targets) ? _targets.map((el) => unrefElement(el)) : [unrefElement(_targets)];
  });
  const stopWatch = watch(
    targets,
    (els) => {
      cleanup();
      if (isSupported.value && window) {
        observer = new ResizeObserver(callback);
        for (const _el of els) {
          if (_el)
            observer.observe(_el, observerOptions);
        }
      }
    },
    { immediate: true, flush: "post" }
  );
  const stop = () => {
    cleanup();
    stopWatch();
  };
  tryOnScopeDispose(stop);
  return {
    isSupported,
    stop
  };
}

function useElementBounding(target, options = {}) {
  const {
    reset = true,
    windowResize = true,
    windowScroll = true,
    immediate = true,
    updateTiming = "sync"
  } = options;
  const height = shallowRef(0);
  const bottom = shallowRef(0);
  const left = shallowRef(0);
  const right = shallowRef(0);
  const top = shallowRef(0);
  const width = shallowRef(0);
  const x = shallowRef(0);
  const y = shallowRef(0);
  function recalculate() {
    const el = unrefElement(target);
    if (!el) {
      if (reset) {
        height.value = 0;
        bottom.value = 0;
        left.value = 0;
        right.value = 0;
        top.value = 0;
        width.value = 0;
        x.value = 0;
        y.value = 0;
      }
      return;
    }
    const rect = el.getBoundingClientRect();
    height.value = rect.height;
    bottom.value = rect.bottom;
    left.value = rect.left;
    right.value = rect.right;
    top.value = rect.top;
    width.value = rect.width;
    x.value = rect.x;
    y.value = rect.y;
  }
  function update() {
    if (updateTiming === "sync")
      recalculate();
    else if (updateTiming === "next-frame")
      requestAnimationFrame(() => recalculate());
  }
  useResizeObserver(target, update);
  watch(() => unrefElement(target), (ele) => !ele && update());
  useMutationObserver(target, update, {
    attributeFilter: ["style", "class"]
  });
  if (windowScroll)
    useEventListener("scroll", update, { capture: true, passive: true });
  if (windowResize)
    useEventListener("resize", update, { passive: true });
  tryOnMounted(() => {
    if (immediate)
      update();
  });
  return {
    height,
    bottom,
    left,
    right,
    top,
    width,
    x,
    y,
    update
  };
}

const topVarName = "--vueuse-safe-area-top";
const rightVarName = "--vueuse-safe-area-right";
const bottomVarName = "--vueuse-safe-area-bottom";
const leftVarName = "--vueuse-safe-area-left";
function useScreenSafeArea() {
  const top = shallowRef("");
  const right = shallowRef("");
  const bottom = shallowRef("");
  const left = shallowRef("");
  if (isClient) {
    const topCssVar = useCssVar(topVarName);
    const rightCssVar = useCssVar(rightVarName);
    const bottomCssVar = useCssVar(bottomVarName);
    const leftCssVar = useCssVar(leftVarName);
    topCssVar.value = "env(safe-area-inset-top, 0px)";
    rightCssVar.value = "env(safe-area-inset-right, 0px)";
    bottomCssVar.value = "env(safe-area-inset-bottom, 0px)";
    leftCssVar.value = "env(safe-area-inset-left, 0px)";
    tryOnMounted(update);
    useEventListener("resize", useDebounceFn(update), { passive: true });
  }
  function update() {
    top.value = getValue(topVarName);
    right.value = getValue(rightVarName);
    bottom.value = getValue(bottomVarName);
    left.value = getValue(leftVarName);
  }
  return {
    top,
    right,
    bottom,
    left,
    update
  };
}
function getValue(position) {
  return getComputedStyle(document.documentElement).getPropertyValue(position);
}

const PANEL_MIN = 20;
const PANEL_MAX = 100;
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "NuxtDevtoolsFrameBox",
  props: /* @__PURE__ */ mergeModels({
    client: { type: Object, required: true },
    isDragging: { type: Boolean, required: true },
    state: { type: Object, required: true }
  }, {
    "popupWindow": { type: null },
    "popupWindowModifiers": {}
  }),
  emits: ["update:popupWindow"],
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const popupWindow = useModel(__props, "popupWindow");
    const {
      state
    } = toRefs(props);
    const container = ref();
    const isResizing = ref(false);
    watchEffect(() => {
      if (!container.value)
        return;
      if (state.value.open) {
        const iframe = props.client.getIframe();
        if (!iframe)
          return;
        iframe.style.pointerEvents = isResizing.value || props.isDragging || props.client.inspector?.isEnabled.value ? "none" : "auto";
        if (!popupWindow.value) {
          if (Array.from(container.value.children).every((el) => el !== iframe))
            container.value.appendChild(iframe);
        }
      }
    });
    useEventListener(window, "keydown", (e) => {
      if (e.key === "Escape" && props.client.inspector?.isEnabled.value) {
        e.preventDefault();
        props.client.inspector?.disable();
        props.client.devtools.close();
      }
    });
    useEventListener(window, "mousedown", (e) => {
      if (!state.value.closeOnOutsideClick)
        return;
      if (popupWindow.value)
        return;
      if (!state.value.open || isResizing.value || props.client.inspector?.isEnabled.value)
        return;
      const matched = e.composedPath().find((_el) => {
        const el = _el;
        return Array.from(el.classList || []).some((c) => c.startsWith("nuxt-devtools-")) || el.tagName?.toLowerCase() === "iframe";
      });
      if (!matched)
        state.value.open = false;
    });
    function handleResize(e) {
      if (!isResizing.value || !state.value.open)
        return;
      const iframe = props.client.getIframe();
      if (!iframe)
        return;
      const box = iframe.getBoundingClientRect();
      let widthPx, heightPx;
      if (isResizing.value.right) {
        widthPx = Math.abs(e instanceof MouseEvent ? e.clientX : (e.touches[0]?.clientX || 0) - (box?.left || 0));
        state.value.width = Math.min(PANEL_MAX, Math.max(PANEL_MIN, widthPx / window.innerWidth * 100));
      } else if (isResizing.value.left) {
        widthPx = Math.abs((box?.right || 0) - (e instanceof MouseEvent ? e.clientX : e.touches[0]?.clientX || 0));
        state.value.width = Math.min(PANEL_MAX, Math.max(PANEL_MIN, widthPx / window.innerWidth * 100));
      }
      if (isResizing.value.top) {
        heightPx = Math.abs((box?.bottom || 0) - (e instanceof MouseEvent ? e.clientY : e.touches[0]?.clientY || 0));
        state.value.height = Math.min(PANEL_MAX, Math.max(PANEL_MIN, heightPx / window.innerHeight * 100));
      } else if (isResizing.value.bottom) {
        heightPx = Math.abs(e instanceof MouseEvent ? e.clientY : (e.touches[0]?.clientY || 0) - (box?.top || 0));
        state.value.height = Math.min(PANEL_MAX, Math.max(PANEL_MIN, heightPx / window.innerHeight * 100));
      }
    }
    useEventListener(window, "mousemove", handleResize);
    useEventListener(window, "touchmove", handleResize);
    useEventListener(window, "mouseup", () => isResizing.value = false);
    useEventListener(window, "touchend", () => isResizing.value = false);
    useEventListener(window, "mouseleave", () => isResizing.value = false);
    const __returned__ = { props, PANEL_MIN, PANEL_MAX, popupWindow, state, container, isResizing, handleResize };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});

const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};

const _hoisted_1$2 = {
  ref: "container",
  class: "nuxt-devtools-frame"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return withDirectives((openBlock(), createElementBlock(
    "div",
    _hoisted_1$2,
    [
      createCommentVNode(" Handlers "),
      withDirectives(createElementVNode(
        "div",
        {
          class: "nuxt-devtools-resize-handle nuxt-devtools-resize-handle-horizontal",
          style: { top: 0 },
          onMousedown: _cache[0] || (_cache[0] = withModifiers(($event) => $setup.isResizing = { top: true }, ["prevent"])),
          onTouchstartPassive: _cache[1] || (_cache[1] = () => $setup.isResizing = { top: true })
        },
        null,
        544
        /* NEED_HYDRATION, NEED_PATCH */
      ), [
        [vShow, $setup.state.position !== "top"]
      ]),
      withDirectives(createElementVNode(
        "div",
        {
          class: "nuxt-devtools-resize-handle nuxt-devtools-resize-handle-horizontal",
          style: { bottom: 0 },
          onMousedown: _cache[2] || (_cache[2] = withModifiers(() => $setup.isResizing = { bottom: true }, ["prevent"])),
          onTouchstartPassive: _cache[3] || (_cache[3] = () => $setup.isResizing = { bottom: true })
        },
        null,
        544
        /* NEED_HYDRATION, NEED_PATCH */
      ), [
        [vShow, $setup.state.position !== "bottom"]
      ]),
      withDirectives(createElementVNode(
        "div",
        {
          class: "nuxt-devtools-resize-handle nuxt-devtools-resize-handle-vertical",
          style: { left: 0 },
          onMousedown: _cache[4] || (_cache[4] = withModifiers(() => $setup.isResizing = { left: true }, ["prevent"])),
          onTouchstartPassive: _cache[5] || (_cache[5] = () => $setup.isResizing = { left: true })
        },
        null,
        544
        /* NEED_HYDRATION, NEED_PATCH */
      ), [
        [vShow, $setup.state.position !== "left"]
      ]),
      withDirectives(createElementVNode(
        "div",
        {
          class: "nuxt-devtools-resize-handle nuxt-devtools-resize-handle-vertical",
          style: { right: 0 },
          onMousedown: _cache[6] || (_cache[6] = withModifiers(() => $setup.isResizing = { right: true }, ["prevent"])),
          onTouchstartPassive: _cache[7] || (_cache[7] = () => $setup.isResizing = { right: true })
        },
        null,
        544
        /* NEED_HYDRATION, NEED_PATCH */
      ), [
        [vShow, $setup.state.position !== "right"]
      ]),
      withDirectives(createElementVNode(
        "div",
        {
          class: "nuxt-devtools-resize-handle nuxt-devtools-resize-handle-corner",
          style: { top: 0, left: 0, cursor: "nwse-resize" },
          onMousedown: _cache[8] || (_cache[8] = withModifiers(() => $setup.isResizing = { top: true, left: true }, ["prevent"])),
          onTouchstartPassive: _cache[9] || (_cache[9] = () => $setup.isResizing = { top: true, left: true })
        },
        null,
        544
        /* NEED_HYDRATION, NEED_PATCH */
      ), [
        [vShow, $setup.state.position !== "top" && $setup.state.position !== "left"]
      ]),
      withDirectives(createElementVNode(
        "div",
        {
          class: "nuxt-devtools-resize-handle nuxt-devtools-resize-handle-corner",
          style: { top: 0, right: 0, cursor: "nesw-resize" },
          onMousedown: _cache[10] || (_cache[10] = withModifiers(() => $setup.isResizing = { top: true, right: true }, ["prevent"])),
          onTouchstartPassive: _cache[11] || (_cache[11] = () => $setup.isResizing = { top: true, right: true })
        },
        null,
        544
        /* NEED_HYDRATION, NEED_PATCH */
      ), [
        [vShow, $setup.state.position !== "top" && $setup.state.position !== "right"]
      ]),
      withDirectives(createElementVNode(
        "div",
        {
          class: "nuxt-devtools-resize-handle nuxt-devtools-resize-handle-corner",
          style: { bottom: 0, left: 0, cursor: "nesw-resize" },
          onMousedown: _cache[12] || (_cache[12] = withModifiers(() => $setup.isResizing = { bottom: true, left: true }, ["prevent"])),
          onTouchstartPassive: _cache[13] || (_cache[13] = () => $setup.isResizing = { bottom: true, left: true })
        },
        null,
        544
        /* NEED_HYDRATION, NEED_PATCH */
      ), [
        [vShow, $setup.state.position !== "bottom" && $setup.state.position !== "left"]
      ]),
      withDirectives(createElementVNode(
        "div",
        {
          class: "nuxt-devtools-resize-handle nuxt-devtools-resize-handle-corner",
          style: { bottom: 0, right: 0, cursor: "nwse-resize" },
          onMousedown: _cache[14] || (_cache[14] = withModifiers(() => $setup.isResizing = { bottom: true, right: true }, ["prevent"])),
          onTouchstartPassive: _cache[15] || (_cache[15] = () => $setup.isResizing = { bottom: true, right: true })
        },
        null,
        544
        /* NEED_HYDRATION, NEED_PATCH */
      ), [
        [vShow, $setup.state.position !== "bottom" && $setup.state.position !== "right"]
      ])
    ],
    512
    /* NEED_PATCH */
  )), [
    [vShow, $setup.state.open && !$props.client.inspector?.isEnabled.value && !$setup.popupWindow]
  ]);
}
const FrameBox = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__file", "/home/runner/work/devtools/devtools/packages/devtools/src/webcomponents/components/NuxtDevtoolsFrameBox.vue"]]);

const SNAP_THRESHOLD = 2;
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "NuxtDevtoolsFrame",
  props: {
    client: { type: Object, required: true },
    settings: { type: Object, required: true },
    state: { type: Object, required: true }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const {
      state,
      settings
    } = toRefs(props);
    function millisecondToHumanreadable(ms) {
      if (ms < 1e3)
        return [+ms.toFixed(0), "ms"];
      if (ms < 1e3 * 60)
        return [+(ms / 1e3).toFixed(1), "s"];
      if (ms < 1e3 * 60 * 60)
        return [+(ms / 1e3 / 60).toFixed(1), "min"];
      return [+(ms / 1e3 / 60 / 60).toFixed(1), "hour"];
    }
    const panelMargins = reactive({
      left: 10,
      top: 10,
      right: 10,
      bottom: 10
    });
    const safeArea = useScreenSafeArea();
    const isSafari = navigator.userAgent.includes("Safari") && !navigator.userAgent.includes("Chrome");
    function toNumber(value) {
      const num = +value;
      if (Number.isNaN(num))
        return 0;
      return num;
    }
    watchEffect(() => {
      panelMargins.left = toNumber(safeArea.left.value) + 10;
      panelMargins.top = toNumber(safeArea.top.value) + 10;
      panelMargins.right = toNumber(safeArea.right.value) + 10;
      panelMargins.bottom = toNumber(safeArea.bottom.value) + 10;
    });
    const vars = computed(() => {
      const dark = props.client.app.colorMode.value === "dark";
      return {
        "--nuxt-devtools-widget-bg": dark ? "#111" : "#ffffff",
        "--nuxt-devtools-widget-fg": dark ? "#F5F5F5" : "#111",
        "--nuxt-devtools-widget-border": dark ? "#3336" : "#efefef",
        "--nuxt-devtools-widget-shadow": dark ? "rgba(0,0,0,0.3)" : "rgba(128,128,128,0.1)"
      };
    });
    const frameBox = useTemplateRef("frameBox");
    const panelEl = useTemplateRef("panelEl");
    const anchorEl = useTemplateRef("anchorEl");
    const windowSize = reactive({
      width: window.innerWidth,
      height: window.innerHeight
    });
    const isDragging = ref(false);
    const draggingOffset = reactive({ x: 0, y: 0 });
    const mousePosition = reactive({ x: 0, y: 0 });
    function onPointerDown(e) {
      if (!panelEl.value)
        return;
      isDragging.value = true;
      const { left, top, width, height } = panelEl.value.getBoundingClientRect();
      draggingOffset.x = e.clientX - left - width / 2;
      draggingOffset.y = e.clientY - top - height / 2;
    }
    onMounted(() => {
      windowSize.width = window.innerWidth;
      windowSize.height = window.innerHeight;
      useEventListener(window, "resize", () => {
        windowSize.width = window.innerWidth;
        windowSize.height = window.innerHeight;
      });
      useEventListener(window, "pointermove", (e) => {
        if (!isDragging.value)
          return;
        const centerX = window.innerWidth / 2;
        const centerY = window.innerHeight / 2;
        const x = e.clientX - draggingOffset.x;
        const y = e.clientY - draggingOffset.y;
        if (Number.isNaN(x) || Number.isNaN(y))
          return;
        mousePosition.x = x;
        mousePosition.y = y;
        const deg = Math.atan2(y - centerY, x - centerX);
        const HORIZONTAL_MARGIN = 70;
        const TL = Math.atan2(0 - centerY + HORIZONTAL_MARGIN, 0 - centerX);
        const TR = Math.atan2(0 - centerY + HORIZONTAL_MARGIN, window.innerWidth - centerX);
        const BL = Math.atan2(window.innerHeight - HORIZONTAL_MARGIN - centerY, 0 - centerX);
        const BR = Math.atan2(window.innerHeight - HORIZONTAL_MARGIN - centerY, window.innerWidth - centerX);
        state.value.position = deg >= TL && deg <= TR ? "top" : deg >= TR && deg <= BR ? "right" : deg >= BR && deg <= BL ? "bottom" : "left";
        state.value.left = snapToPoints(x / window.innerWidth * 100);
        state.value.top = snapToPoints(y / window.innerHeight * 100);
      });
      useEventListener(window, "pointerup", () => {
        isDragging.value = false;
      });
      useEventListener(window, "pointerleave", () => {
        isDragging.value = false;
      });
    });
    function snapToPoints(value) {
      if (value < 5)
        return 0;
      if (value > 95)
        return 100;
      if (Math.abs(value - 50) < SNAP_THRESHOLD)
        return 50;
      return value;
    }
    function clamp(value, min, max) {
      return Math.min(Math.max(value, min), max);
    }
    const isHovering = ref(false);
    const isVertical = computed(() => state.value.position === "left" || state.value.position === "right");
    const anchorPos = computed(() => {
      const halfWidth = (panelEl.value?.clientWidth || 0) / 2;
      const halfHeight = (panelEl.value?.clientHeight || 0) / 2;
      const left = state.value.left * windowSize.width / 100;
      const top = state.value.top * windowSize.height / 100;
      switch (state.value.position) {
        case "top":
          return {
            left: clamp(left, halfWidth + panelMargins.left, windowSize.width - halfWidth - panelMargins.right),
            top: panelMargins.top + halfHeight
          };
        case "right":
          return {
            left: windowSize.width - panelMargins.right - halfHeight,
            top: clamp(top, halfWidth + panelMargins.top, windowSize.height - halfWidth - panelMargins.bottom)
          };
        case "left":
          return {
            left: panelMargins.left + halfHeight,
            top: clamp(top, halfWidth + panelMargins.top, windowSize.height - halfWidth - panelMargins.bottom)
          };
        case "bottom":
        default:
          return {
            left: clamp(left, halfWidth + panelMargins.left, windowSize.width - halfWidth - panelMargins.right),
            top: windowSize.height - panelMargins.bottom - halfHeight
          };
      }
    });
    let _timer = null;
    function bringUp() {
      isHovering.value = true;
      if (state.value.minimizePanelInactive < 0)
        return;
      if (_timer)
        clearTimeout(_timer);
      _timer = setTimeout(() => {
        isHovering.value = false;
      }, +state.value.minimizePanelInactive || 0);
    }
    const isHidden = computed(() => {
      if (state.value.open)
        return false;
      if (settings.value.ui.showPanel === true)
        return false;
      if (settings.value.ui.showPanel === false)
        return true;
      return false;
    });
    const isMinimized = computed(() => {
      if (state.value.minimizePanelInactive < 0)
        return false;
      if (state.value.minimizePanelInactive === 0)
        return true;
      const isTouchDevice = "ontouchstart" in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0;
      return !isDragging.value && !state.value.open && !isHovering.value && !isTouchDevice && state.value.minimizePanelInactive;
    });
    const anchorStyle = computed(() => {
      return {
        left: `${anchorPos.value.left}px`,
        top: `${anchorPos.value.top}px`,
        pointerEvents: isHidden.value ? "none" : "auto"
      };
    });
    const panelStyle = computed(() => {
      const style = {
        transform: isVertical.value ? `translate(${isMinimized.value ? `calc(-50% ${state.value.position === "right" ? "+" : "-"} 15px)` : "-50%"}, -50%) rotate(90deg)` : `translate(-50%, ${isMinimized.value ? `calc(-50% ${state.value.position === "top" ? "-" : "+"} 15px)` : "-50%"})`
      };
      if (isHidden.value) {
        style.opacity = 0;
        style.pointerEvents = "none";
      }
      if (isMinimized.value) {
        switch (state.value.position) {
          case "top":
          case "right":
            style.borderTopLeftRadius = "0";
            style.borderTopRightRadius = "0";
            break;
          case "bottom":
          case "left":
            style.borderBottomLeftRadius = "0";
            style.borderBottomRightRadius = "0";
            break;
        }
      }
      if (isDragging.value)
        style.transition = "none !important";
      return style;
    });
    const { width: frameWidth, height: frameHeight } = useElementBounding(frameBox);
    const popupWindow = ref(null);
    const iframeStyle = computed(() => {
      mousePosition.x, mousePosition.y;
      const halfHeight = (panelEl.value?.clientHeight || 0) / 2;
      const frameMargin = {
        left: panelMargins.left + halfHeight,
        top: panelMargins.top + halfHeight,
        right: panelMargins.right + halfHeight,
        bottom: panelMargins.bottom + halfHeight
      };
      const marginHorizontal = frameMargin.left + frameMargin.right;
      const marginVertical = frameMargin.top + frameMargin.bottom;
      const maxWidth = windowSize.width - marginHorizontal;
      const maxHeight = windowSize.height - marginVertical;
      const style = {
        position: "fixed",
        zIndex: -1,
        pointerEvents: isDragging.value || !state.value.open || props.client.inspector?.isEnabled.value ? "none" : "auto",
        width: `min(${state.value.width}vw, calc(100vw - ${marginHorizontal}px))`,
        height: `min(${state.value.height}vh, calc(100vh - ${marginVertical}px))`
      };
      const anchor = anchorPos.value;
      const width = Math.min(maxWidth, state.value.width * windowSize.width / 100);
      const height = Math.min(maxHeight, state.value.height * windowSize.height / 100);
      const anchorX = anchor?.left || 0;
      const anchorY = anchor?.top || 0;
      switch (state.value.position) {
        case "top":
        case "bottom":
          style.left = `${-frameWidth.value / 2}px`;
          style.transform = "translate(0, 0)";
          if (anchorX - frameMargin.left < width / 2)
            style.left = `${width / 2 - anchorX + frameMargin.left - frameWidth.value / 2}px`;
          else if (windowSize.width - anchorX - frameMargin.right < width / 2)
            style.left = `${windowSize.width - anchorX - width / 2 - frameMargin.right - frameWidth.value / 2}px`;
          break;
        case "right":
        case "left":
          style.top = `${-frameHeight.value / 2}px`;
          style.transform = "translate(0, 0)";
          if (anchorY - frameMargin.top < height / 2)
            style.top = `${height / 2 - anchorY + frameMargin.top - frameHeight.value / 2}px`;
          else if (windowSize.height - anchorY - frameMargin.bottom < height / 2)
            style.top = `${windowSize.height - anchorY - height / 2 - frameMargin.bottom - frameHeight.value / 2}px`;
          break;
      }
      switch (state.value.position) {
        case "top":
          style.top = 0;
          break;
        case "right":
          style.right = 0;
          break;
        case "left":
          style.left = 0;
          break;
        case "bottom":
        default:
          style.bottom = 0;
          break;
      }
      if (props.client.inspector?.isEnabled.value) {
        style.opacity = 0;
      }
      return style;
    });
    const time = computed(() => {
      let type = "";
      const metric = props.client.metrics.loading();
      let time2 = -1;
      if (metric.pageEnd && metric.pageStart) {
        time2 = metric.pageEnd - metric.pageStart;
        type = "Page";
      } else if (metric.appLoad && metric.appInit) {
        time2 = metric.appLoad - metric.appInit;
        type = "App";
      }
      bringUp();
      if (time2 < 0)
        return [type, "", "-"];
      return [type, ...millisecondToHumanreadable(time2)];
    });
    function toggleInspector() {
      const isDevToolsOpen = state.value.open;
      if (!isDevToolsOpen)
        props.client.devtools.open();
      props.client.inspector?.enable();
      if (!isDevToolsOpen) {
        setTimeout(() => {
          props.client.devtools.close();
        }, 500);
      }
    }
    onMounted(() => {
      bringUp();
    });
    const __returned__ = { props, state, settings, millisecondToHumanreadable, panelMargins, safeArea, isSafari, toNumber, SNAP_THRESHOLD, vars, frameBox, panelEl, anchorEl, windowSize, isDragging, draggingOffset, mousePosition, onPointerDown, snapToPoints, clamp, isHovering, isVertical, anchorPos, get _timer() {
      return _timer;
    }, set _timer(v) {
      _timer = v;
    }, bringUp, isHidden, isMinimized, anchorStyle, panelStyle, frameWidth, frameHeight, popupWindow, iframeStyle, time, toggleInspector, FrameBox };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});

const _hoisted_1$1 = {
  viewBox: "0 0 324 324",
  fill: "none",
  xmlns: "http://www.w3.org/2000/svg",
  style: { "margin-top": "-1px", "height": "1.2em", "width": "1.2em" }
};
const _hoisted_2$1 = ["title"];
const _hoisted_3$1 = { class: "nuxt-devtools-label-main" };
const _hoisted_4 = { class: "nuxt-devtools-label-secondary" };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      id: "nuxt-devtools-anchor",
      ref: "anchorEl",
      style: normalizeStyle([$setup.anchorStyle, $setup.vars]),
      class: normalizeClass({
        "nuxt-devtools-vertical": $setup.isVertical,
        "nuxt-devtools-hide": $setup.isMinimized
      }),
      onMousemove: $setup.bringUp
    },
    [
      !$setup.isSafari ? (openBlock(), createElementBlock(
        "div",
        {
          key: 0,
          class: "nuxt-devtools-glowing",
          style: normalizeStyle($setup.isDragging ? "opacity: 0.6 !important" : "")
        },
        null,
        4
        /* STYLE */
      )) : createCommentVNode("v-if", true),
      createElementVNode(
        "div",
        {
          ref: "panelEl",
          class: "nuxt-devtools-panel",
          style: normalizeStyle($setup.panelStyle),
          onPointerdown: $setup.onPointerDown
        },
        [
          createElementVNode(
            "button",
            {
              class: "nuxt-devtools-icon-button nuxt-devtools-nuxt-button",
              title: "Toggle Nuxt DevTools",
              style: normalizeStyle($setup.state.open ? "" : "filter:saturate(0)"),
              onClick: _cache[0] || (_cache[0] = ($event) => $props.client.devtools.toggle())
            },
            [
              (openBlock(), createElementBlock("svg", _hoisted_1$1, [..._cache[2] || (_cache[2] = [
                createElementVNode(
                  "path",
                  {
                    d: "M181.767 270H302.211C306.037 270 309.795 269.003 313.108 267.107C316.421 265.211 319.172 262.484 321.084 259.2C322.996 255.915 324.002 252.19 324 248.399C323.998 244.607 322.989 240.883 321.074 237.601L240.187 98.7439C238.275 95.4607 235.525 92.7342 232.213 90.8385C228.901 88.9429 225.143 87.9449 221.318 87.9449C217.494 87.9449 213.736 88.9429 210.424 90.8385C207.112 92.7342 204.361 95.4607 202.449 98.7439L181.767 134.272L141.329 64.7975C139.416 61.5145 136.664 58.7884 133.351 56.8931C130.038 54.9978 126.28 54 122.454 54C118.629 54 114.871 54.9978 111.558 56.8931C108.245 58.7884 105.493 61.5145 103.58 64.7975L2.92554 237.601C1.01067 240.883 0.00166657 244.607 2.06272e-06 248.399C-0.00166244 252.19 1.00407 255.915 2.91605 259.2C4.82803 262.484 7.57884 265.211 10.8918 267.107C14.2047 269.003 17.963 270 21.7886 270H97.3936C127.349 270 149.44 256.959 164.641 231.517L201.546 168.172L221.313 134.272L280.637 236.1H201.546L181.767 270ZM96.1611 236.065L43.3984 236.054L122.49 100.291L161.953 168.172L135.531 213.543C125.436 230.051 113.968 236.065 96.1611 236.065Z",
                    fill: "#00DC82"
                  },
                  null,
                  -1
                  /* CACHED */
                )
              ])]))
            ],
            4
            /* STYLE */
          ),
          _cache[5] || (_cache[5] = createElementVNode(
            "div",
            {
              style: { "border-left": "1px solid #8883", "width": "1px", "height": "10px" },
              class: "nuxt-devtools-panel-content"
            },
            null,
            -1
            /* CACHED */
          )),
          createElementVNode("div", {
            class: "nuxt-devtools-panel-content nuxt-devtools-label",
            title: `${$setup.time[0]} load time`
          }, [
            createElementVNode(
              "div",
              _hoisted_3$1,
              toDisplayString($setup.time[1]),
              1
              /* TEXT */
            ),
            createElementVNode(
              "span",
              _hoisted_4,
              toDisplayString($setup.time[2]),
              1
              /* TEXT */
            )
          ], 8, _hoisted_2$1),
          $props.client.inspector?.isAvailable ? (openBlock(), createElementBlock(
            Fragment,
            { key: 0 },
            [
              _cache[4] || (_cache[4] = createElementVNode(
                "div",
                {
                  style: { "border-left": "1px solid #8883", "width": "1px", "height": "10px" },
                  class: "nuxt-devtools-panel-content"
                },
                null,
                -1
                /* CACHED */
              )),
              createElementVNode("button", {
                class: "nuxt-devtools-icon-button nuxt-devtools-panel-content",
                title: "Toggle Component Inspector",
                onClick: withModifiers($setup.toggleInspector, ["prevent", "stop"])
              }, [
                (openBlock(), createElementBlock(
                  "svg",
                  {
                    xmlns: "http://www.w3.org/2000/svg",
                    style: normalizeStyle([{ "height": "1.2em", "width": "1.2em", "opacity": "0.5" }, $props.client.inspector.isEnabled.value ? "opacity:1;color:#00dc82" : ""]),
                    viewBox: "0 0 24 24"
                  },
                  [..._cache[3] || (_cache[3] = [
                    createElementVNode(
                      "g",
                      {
                        fill: "none",
                        stroke: "currentColor",
                        "stroke-linecap": "round",
                        "stroke-linejoin": "round",
                        "stroke-width": "2"
                      },
                      [
                        createElementVNode("circle", {
                          cx: "12",
                          cy: "12",
                          r: ".5",
                          fill: "currentColor"
                        }),
                        createElementVNode("path", { d: "M5 12a7 7 0 1 0 14 0a7 7 0 1 0-14 0m7-9v2m-9 7h2m7 7v2m7-9h2" })
                      ],
                      -1
                      /* CACHED */
                    )
                  ])],
                  4
                  /* STYLE */
                ))
              ])
            ],
            64
            /* STABLE_FRAGMENT */
          )) : createCommentVNode("v-if", true)
        ],
        36
        /* STYLE, NEED_HYDRATION */
      ),
      createElementVNode(
        "div",
        {
          ref: "frameBox",
          style: normalizeStyle($setup.iframeStyle)
        },
        [
          createVNode($setup["FrameBox"], {
            "popup-window": $setup.popupWindow,
            "onUpdate:popupWindow": _cache[1] || (_cache[1] = ($event) => $setup.popupWindow = $event),
            state: $setup.state,
            client: $props.client,
            "is-dragging": $setup.isDragging
          }, null, 8, ["popup-window", "state", "client", "is-dragging"])
        ],
        4
        /* STYLE */
      )
    ],
    38
    /* CLASS, STYLE, NEED_HYDRATION */
  );
}
const Component$1 = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__file", "/home/runner/work/devtools/devtools/packages/devtools/src/webcomponents/components/NuxtDevtoolsFrame.vue"]]);

const NuxtDevtoolsFrame = defineCustomElement(
  Component$1,
  {
    shadowRoot: true,
    styles: [css]
  }
);
customElements.define("nuxt-devtools-frame", NuxtDevtoolsFrame);

const PANEL_WIDTH = 400;
const PANEL_HEIGHT = 300;
const PANEL_MARGIN = 30;
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "NuxtDevtoolsInspectPanel",
  props: {
    props: { type: Object, required: true }
  },
  emits: ["close", "selectParent", "openInEditor"],
  setup(__props, { expose: __expose, emit: __emit }) {
    __expose();
    const emit = __emit;
    const initX = ref(0);
    const initY = ref(0);
    watch(
      () => [__props.props.matched, __props.props.mouse],
      ([matched, mouse]) => {
        if (!matched || !mouse)
          return;
        initX.value = Math.max(PANEL_MARGIN, Math.min(mouse.x, window.innerWidth - PANEL_WIDTH - PANEL_MARGIN));
        initY.value = Math.max(PANEL_MARGIN, Math.min(mouse.y, window.innerHeight - PANEL_HEIGHT - PANEL_MARGIN));
      }
    );
    const el = useTemplateRef("el");
    const draggingEl = useTemplateRef("draggingEl");
    const { x, y, style, isDragging } = useDraggable(el, {
      initialValue: {
        x: initX.value,
        y: initY.value
      },
      handle: draggingEl
    });
    watch([initX, initY], () => {
      if (!__props.props.matched)
        return;
      x.value = initX.value;
      y.value = initY.value;
    });
    const hasMoved = computed(() => x.value !== initX.value || y.value !== initY.value);
    onClickOutside(el, () => {
      nextTick(() => {
        if (!__props.props.matched)
          return;
        if (hasMoved.value)
          return;
        close();
      });
    });
    function close() {
      emit("close");
    }
    async function selectParent() {
      emit("selectParent");
    }
    async function openInEditor() {
      if (!__props.props.matched)
        return;
      const file = `${__props.props.matched.pos[0]}:${__props.props.matched.pos[1]}:${__props.props.matched.pos[2]}`;
      emit("openInEditor", file);
    }
    const __returned__ = { emit, PANEL_WIDTH, PANEL_HEIGHT, PANEL_MARGIN, initX, initY, el, draggingEl, x, y, style, isDragging, hasMoved, close, selectParent, openInEditor };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});

const _hoisted_1 = {
  ref: "draggingEl",
  class: "flex items-center gap-2 p2"
};
const _hoisted_2 = ["disabled"];
const _hoisted_3 = { key: 0 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(
    "div",
    {
      ref: "el",
      class: normalizeClass(["bg-glass ring-base color-base fixed relative z-9999999 w-400px flex flex-col of-hidden rounded-lg text-sm shadow-lg ring-1 backdrop-blur duration-200", [
        $setup.isDragging ? "transition-none" : "transition-opacity",
        $props.props.matched ? "op100" : "op0 pointer-events-none"
      ]]),
      style: normalizeStyle($setup.style)
    },
    [
      createCommentVNode(' <div\n      class="pointer-events-none absolute inset-0 z-10 transition-opacity duration-300"\n    >\n      <div class="nuxt-devtools-inspect-running-border pointer-events-none absolute inset-0 z-10 border-1.5 border-transparent rounded-lg" />\n    </div> '),
      createElementVNode(
        "div",
        _hoisted_1,
        [
          createElementVNode("button", {
            title: "Go to parent",
            class: "flex items-center text-sm font-mono op50 disabled:pointer-events-none hover:text-green6 hover:op100 disabled:op10!",
            disabled: !$props.props.hasParent,
            onClick: $setup.selectParent
          }, [..._cache[0] || (_cache[0] = [
            createElementVNode(
              "div",
              { class: "i-ph-arrow-bend-left-up-duotone text-lg" },
              null,
              -1
              /* CACHED */
            )
          ])], 8, _hoisted_2),
          createElementVNode("button", {
            title: "Open in editor",
            class: "flex items-center text-sm font-mono op50 hover:text-green6 hover:op100",
            onClick: $setup.openInEditor
          }, [
            $props.props.matched ? (openBlock(), createElementBlock(
              "span",
              _hoisted_3,
              toDisplayString($props.props.matched.pos[0]) + ":" + toDisplayString($props.props.matched.pos[1]) + ":" + toDisplayString($props.props.matched.pos[2]),
              1
              /* TEXT */
            )) : createCommentVNode("v-if", true),
            _cache[1] || (_cache[1] = createElementVNode(
              "div",
              { class: "i-ph-arrow-up-right-light mt--2 text-lg" },
              null,
              -1
              /* CACHED */
            ))
          ]),
          _cache[3] || (_cache[3] = createElementVNode(
            "div",
            { class: "flex-auto" },
            null,
            -1
            /* CACHED */
          )),
          createElementVNode("button", {
            title: "Close",
            class: "flex-none op50 hover:op100",
            onClick: $setup.close
          }, [..._cache[2] || (_cache[2] = [
            createElementVNode(
              "div",
              { class: "i-ph-x text-lg" },
              null,
              -1
              /* CACHED */
            )
          ])])
        ],
        512
        /* NEED_PATCH */
      )
    ],
    6
    /* CLASS, STYLE */
  );
}
const Component = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/home/runner/work/devtools/devtools/packages/devtools/src/webcomponents/components/NuxtDevtoolsInspectPanel.vue"]]);

const NuxtDevtoolsInspectPanel = defineCustomElement(
  Component,
  {
    shadowRoot: true,
    styles: [css]
  }
);
customElements.define("nuxt-devtools-inspect-panel", NuxtDevtoolsInspectPanel);

export { NuxtDevtoolsFrame, NuxtDevtoolsInspectPanel };
