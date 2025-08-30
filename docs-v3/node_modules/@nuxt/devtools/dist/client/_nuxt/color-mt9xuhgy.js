function n(o,r=65,e=50,l=1){let t=0;for(let h=0;h<o.length;h++)t=o.charCodeAt(h)+((t<<5)-t);return`hsla(${t%360}, ${r}%, ${e}%, ${l})`}export{n as g};
