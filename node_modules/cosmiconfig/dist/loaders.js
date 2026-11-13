"use strict";
/* eslint-disable @typescript-eslint/no-require-imports */
Object.defineProperty(exports, "__esModule", { value: true });
exports.loadYaml = exports.loadJson = exports.loadJs = exports.loadJsSync = void 0;
const promises_1 = require("fs/promises");
const url_1 = require("url");
const loadJsSync = function loadJsSync(filepath) {
    delete require.cache[require.resolve(filepath)];
    return require(filepath);
};
exports.loadJsSync = loadJsSync;
const loadJs = async function loadJs(filepath) {
    try {
        const { href } = (0, url_1.pathToFileURL)(await (0, promises_1.realpath)(filepath));
        return (await import(href)).default;
    }
    catch (error) {
        try {
            return (0, exports.loadJsSync)(filepath, '');
        }
        catch (requireError) {
            /* istanbul ignore next -- @preserve */
            if (requireError.code === 'ERR_REQUIRE_ESM' ||
                (requireError instanceof SyntaxError &&
                    requireError
                        .toString()
                        .includes('Cannot use import statement outside a module'))) {
                throw error;
            }
            throw requireError;
        }
    }
};
exports.loadJs = loadJs;
const loadJson = function loadJson(filepath, content) {
    try {
        return JSON.parse(content);
    }
    catch (error) {
        error.message = `JSON Error in ${filepath}:\n${error.message}`;
        throw error;
    }
};
exports.loadJson = loadJson;
let yaml;
const loadYaml = function loadYaml(filepath, content) {
    if (yaml === undefined) {
        yaml = require('js-yaml');
    }
    try {
        return yaml.load(content);
    }
    catch (error) {
        error.message = `YAML Error in ${filepath}:\n${error.message}`;
        throw error;
    }
};
exports.loadYaml = loadYaml;
//# sourceMappingURL=loaders.js.map