"use strict";

Object.defineProperty(exports, "__esModule", {
    value: true
});

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; // Dependencies

var _normalizeUrl = require("normalize-url");

var _normalizeUrl2 = _interopRequireDefault(_normalizeUrl);

var _parsePath = require("parse-path");

var _parsePath2 = _interopRequireDefault(_parsePath);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/**
 * parseUrl
 * Parses the input url.
 *
 * **Note**: This *throws* if invalid urls are provided.
 *
 * @name parseUrl
 * @function
 * @param {String} url The input url.
 * @param {Boolean|Object} normalize Whether to normalize the url or not.
 *                         Default is `false`. If `true`, the url will
 *                         be normalized. If an object, it will be the
 *                         options object sent to [`normalize-url`](https://github.com/sindresorhus/normalize-url).
 *
 *                         For SSH urls, normalize won't work.
 *
 * @return {Object} An object containing the following fields:
 *
 *    - `protocols` (Array): An array with the url protocols (usually it has one element).
 *    - `protocol` (String): The first protocol, `"ssh"` (if the url is a ssh url) or `"file"`.
 *    - `port` (null|Number): The domain port.
 *    - `resource` (String): The url domain (including subdomains).
 *    - `host` (String):  The fully qualified domain name of a network host, or its IP address.
 *    - `user` (String): The authentication user (usually for ssh urls).
 *    - `pathname` (String): The url pathname.
 *    - `hash` (String): The url hash.
 *    - `search` (String): The url querystring value.
 *    - `href` (String): The input url.
 *    - `query` (Object): The url querystring, parsed as object.
 *    - `parse_failed` (Boolean): Whether the parsing failed or not.
 */
var parseUrl = function parseUrl(url) {
    var normalize = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;


    // Constants
    /**
     * ([a-zA-Z_][a-zA-Z0-9_-]{0,31}) Try to match the user
     * ([\w\.\-@]+) Match the host/resource
     * (([\~,\.\w,\-,\_,\/,\s]|%[0-9A-Fa-f]{2})+?(?:\.git|\/)?) Match the path, allowing spaces/white 
     */
    var GIT_RE = /^(?:([a-zA-Z_][a-zA-Z0-9_-]{0,31})@|https?:\/\/)([\w\.\-@]+)[\/:](([\~,\.\w,\-,\_,\/,\s]|%[0-9A-Fa-f]{2})+?(?:\.git|\/)?)$/;

    var throwErr = function throwErr(msg) {
        var err = new Error(msg);
        err.subject_url = url;
        throw err;
    };

    if (typeof url !== "string" || !url.trim()) {
        throwErr("Invalid url.");
    }

    if (url.length > parseUrl.MAX_INPUT_LENGTH) {
        throwErr("Input exceeds maximum length. If needed, change the value of parseUrl.MAX_INPUT_LENGTH.");
    }

    if (normalize) {
        if ((typeof normalize === "undefined" ? "undefined" : _typeof(normalize)) !== "object") {
            normalize = {
                stripHash: false
            };
        }
        url = (0, _normalizeUrl2.default)(url, normalize);
    }

    var parsed = (0, _parsePath2.default)(url);

    // Potential git-ssh urls
    if (parsed.parse_failed) {
        var matched = parsed.href.match(GIT_RE);

        if (matched) {
            parsed.protocols = ["ssh"];
            parsed.protocol = "ssh";
            parsed.resource = matched[2];
            parsed.host = matched[2];
            parsed.user = matched[1];
            parsed.pathname = "/" + matched[3];
            parsed.parse_failed = false;
        } else {
            throwErr("URL parsing failed.");
        }
    }

    return parsed;
};

parseUrl.MAX_INPUT_LENGTH = 2048;

exports.default = parseUrl;