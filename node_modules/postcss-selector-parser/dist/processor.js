"use strict";
var __values = (this && this.__values) || function(o) {
    var s = typeof Symbol === "function" && Symbol.iterator, m = s && o[s], i = 0;
    if (m) return m.call(o);
    if (o && typeof o.length === "number") return {
        next: function () {
            if (o && i >= o.length) o = void 0;
            return { value: o && o[i++], done: !o };
        }
    };
    throw new TypeError(s ? "Object is not iterable." : "Symbol.iterator is not defined.");
};
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
var parser_1 = __importDefault(require("./parser"));
// The entry points a caller may reasonably take off a processor, as in
// `const { process } = parser()`. They are bound per instance in the
// constructor: unbound, they lost their receiver and threw a TypeError on
// `this._run`.
var BOUND_METHODS = ["ast", "astSync", "transform", "transformSync", "process", "processSync"];
var Processor = /** @class */ (function () {
    function Processor(func, options) {
        var e_1, _a;
        this.func = func || function noop() { };
        this.funcRes = null;
        this.options = options;
        try {
            for (var BOUND_METHODS_1 = __values(BOUND_METHODS), BOUND_METHODS_1_1 = BOUND_METHODS_1.next(); !BOUND_METHODS_1_1.done; BOUND_METHODS_1_1 = BOUND_METHODS_1.next()) {
                var name = BOUND_METHODS_1_1.value;
                this[name] = this[name].bind(this);
            }
        }
        catch (e_1_1) { e_1 = { error: e_1_1 }; }
        finally {
            try {
                if (BOUND_METHODS_1_1 && !BOUND_METHODS_1_1.done && (_a = BOUND_METHODS_1.return)) _a.call(BOUND_METHODS_1);
            }
            finally { if (e_1) throw e_1.error; }
        }
    }
    Processor.prototype._shouldUpdateSelector = function (rule, options) {
        if (options === void 0) { options = {}; }
        var merged = Object.assign({}, this.options, options);
        if (merged.updateSelector === false) {
            return false;
        }
        else {
            return typeof rule !== "string";
        }
    };
    Processor.prototype._isLossy = function (options) {
        if (options === void 0) { options = {}; }
        var merged = Object.assign({}, this.options, options);
        if (merged.lossless === false) {
            return true;
        }
        else {
            return false;
        }
    };
    Processor.prototype._root = function (rule, options) {
        if (options === void 0) { options = {}; }
        var parser = new parser_1.default(rule, this._parseOptions(options));
        return parser.root;
    };
    Processor.prototype._parseOptions = function (options) {
        var merged = Object.assign({}, this.options, options);
        return {
            lossy: this._isLossy(merged),
            maxNestingDepth: merged.maxNestingDepth,
            maxInputLength: merged.maxInputLength,
        };
    };
    Processor.prototype._stringifyOptions = function (options) {
        var merged = Object.assign({}, this.options, options);
        return {
            maxNestingDepth: merged.maxNestingDepth,
        };
    };
    Processor.prototype._run = function (rule, options) {
        var _this = this;
        if (options === void 0) { options = {}; }
        return new Promise(function (resolve, reject) {
            try {
                var root_1 = _this._root(rule, options);
                Promise.resolve(_this.func(root_1))
                    .then(function (transform) {
                    var string = undefined;
                    if (_this._shouldUpdateSelector(rule, options)) {
                        string = root_1.toString(_this._stringifyOptions(options));
                        rule.selector = string;
                    }
                    return { transform: transform, root: root_1, string: string };
                })
                    .then(resolve, reject);
            }
            catch (e) {
                reject(e);
                return;
            }
        });
    };
    Processor.prototype._runSync = function (rule, options) {
        if (options === void 0) { options = {}; }
        var root = this._root(rule, options);
        var transform = this.func(root);
        if (transform && typeof transform.then === "function") {
            throw new Error("Selector processor returned a promise to a synchronous call.");
        }
        var string = undefined;
        if (options.updateSelector && typeof rule !== "string") {
            string = root.toString(this._stringifyOptions(options));
            rule.selector = string;
        }
        return { transform: transform, root: root, string: string };
    };
    /**
     * Process rule into a selector AST.
     *
     * @param rule {postcss.Rule | string} The css selector to be processed
     * @param options The options for processing
     * @returns {Promise<parser.Root>} The AST of the selector after processing it.
     */
    Processor.prototype.ast = function (rule, options) {
        return this._run(rule, options).then(function (result) { return result.root; });
    };
    /**
     * Process rule into a selector AST synchronously.
     *
     * @param rule {postcss.Rule | string} The css selector to be processed
     * @param options The options for processing
     * @returns {parser.Root} The AST of the selector after processing it.
     */
    Processor.prototype.astSync = function (rule, options) {
        return this._runSync(rule, options).root;
    };
    /**
     * Process a selector into a transformed value asynchronously
     *
     * @param rule {postcss.Rule | string} The css selector to be processed
     * @param options The options for processing
     * @returns {Promise<any>} The value returned by the processor.
     */
    Processor.prototype.transform = function (rule, options) {
        return this._run(rule, options).then(function (result) { return result.transform; });
    };
    /**
     * Process a selector into a transformed value synchronously.
     *
     * @param rule {postcss.Rule | string} The css selector to be processed
     * @param options The options for processing
     * @returns {any} The value returned by the processor.
     */
    Processor.prototype.transformSync = function (rule, options) {
        return this._runSync(rule, options).transform;
    };
    /**
     * Process a selector into a new selector string asynchronously.
     *
     * @param rule {postcss.Rule | string} The css selector to be processed
     * @param options The options for processing
     * @returns {string} the selector after processing.
     */
    Processor.prototype.process = function (rule, options) {
        var _this = this;
        return this._run(rule, options).then(function (result) { return result.string || result.root.toString(_this._stringifyOptions(options)); });
    };
    /**
     * Process a selector into a new selector string synchronously.
     *
     * @param rule {postcss.Rule | string} The css selector to be processed
     * @param options The options for processing
     * @returns {string} the selector after processing.
     */
    Processor.prototype.processSync = function (rule, options) {
        var result = this._runSync(rule, options);
        return result.string || result.root.toString(this._stringifyOptions(options));
    };
    return Processor;
}());
exports.default = Processor;
//# sourceMappingURL=processor.js.map