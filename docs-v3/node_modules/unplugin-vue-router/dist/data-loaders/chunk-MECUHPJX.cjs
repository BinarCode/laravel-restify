"use strict";Object.defineProperty(exports, "__esModule", {value: true});// src/data-loaders/createDataLoader.ts
var toLazyValue = (lazy, to, from) => typeof lazy === "function" ? lazy(to, from) : lazy;



exports.toLazyValue = toLazyValue;
