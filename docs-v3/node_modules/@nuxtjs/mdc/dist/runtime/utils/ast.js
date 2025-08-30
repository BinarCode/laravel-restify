export function flattenNodeText(node) {
  if (node.type === "comment") {
    return "";
  }
  if (node.type === "text") {
    return node.value || "";
  } else {
    return (node.children || []).reduce((text, child) => {
      return text.concat(flattenNodeText(child));
    }, "");
  }
}
export function flattenNode(node, maxDepth = 2, _depth = 0) {
  if (!Array.isArray(node.children) || _depth === maxDepth) {
    return [node];
  }
  return [
    node,
    ...node.children.reduce((acc, child) => acc.concat(flattenNode(child, maxDepth, _depth + 1)), [])
  ];
}
export function setNodeData(node, name, value, pageData) {
  if (!name.startsWith(":")) {
    name = ":" + name;
  }
  const dataKey = `content_d_${randomHash()}`;
  pageData[dataKey] = value;
  node.data.hProperties[name] = dataKey;
}
function randomHash() {
  return Math.random().toString(36).substr(2, 16);
}
