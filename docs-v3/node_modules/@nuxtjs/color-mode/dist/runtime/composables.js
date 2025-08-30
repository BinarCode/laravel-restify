import { useState } from "#imports";
export const useColorMode = () => {
  return useState("color-mode").value;
};
