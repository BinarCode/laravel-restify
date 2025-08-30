import parsePath = require("parse-path");
import normalizeUrl = require("normalize-url");

declare namespace parseUrl {
  const MAX_INPUT_LENGTH: 2048;

  type NormalizeOptions = normalizeUrl.Options;

  type ParsedUrl = parsePath.ParsedPath;

  interface ParsingError extends Error {
    readonly subject_url: string;
  }
}

declare function parseUrl(
  url: string,
  normalize?: boolean | parseUrl.NormalizeOptions
): parseUrl.ParsedUrl;

export = parseUrl;
