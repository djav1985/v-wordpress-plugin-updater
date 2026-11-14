import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import test from "node:test";
import { runInNewContext } from "node:vm";

function loadHeaderScript({ clipboard, execCommand = () => true } = {}) {
  const documentListeners = {};
  const buttonListeners = {};
  const nav = {
    inert: false,
    querySelector: () => ({ textContent: "Manage Plugins" }),
  };
  const navClasses = new Set();
  const navWrap = {
    classList: {
      add: (name) => navClasses.add(name),
      toggle: (name, enabled) => {
        if (enabled) navClasses.add(name);
        else navClasses.delete(name);
      },
    },
    contains: () => false,
    querySelector: () => nav,
  };
  const attributes = {};
  const navToggle = {
    dataset: { menuSection: "Manage Plugins" },
    hidden: true,
    contains: () => false,
    setAttribute: (name, value) => {
      attributes[name] = value;
    },
    getAttribute: (name) => attributes[name],
    addEventListener: (name, callback) => {
      buttonListeners[name] = callback;
    },
  };
  const toastContainer = { appendChild() {} };
  const document = {
    activeElement: null,
    body: { appendChild() {} },
    addEventListener: (name, callback) => {
      documentListeners[name] ??= [];
      documentListeners[name].push(callback);
    },
    createElement: () => ({
      dataset: {},
      append() {},
      addEventListener() {},
      classList: { add() {}, remove() {} },
      setAttribute() {},
      style: {},
      focus() {},
      select() {},
      setSelectionRange() {},
      remove() {},
    }),
    execCommand,
    getElementById: (id) => id === "toast-container" ? toastContainer : null,
    querySelector: (selector) => {
      if (selector === ".admin-nav-wrap") return navWrap;
      if (selector === ".mobile-menu-toggle") return navToggle;
      return null;
    },
    querySelectorAll: () => [],
  };
  const context = {
    document,
    navigator: { clipboard },
    window: {
      matchMedia: () => ({ matches: true, addEventListener() {} }),
      requestAnimationFrame: (callback) => callback(),
      setTimeout: () => 1,
    },
  };
  const source = readFileSync(
    new URL("../v-update-api/public/assets/js/header-scripts.js", import.meta.url),
    "utf8",
  );
  runInNewContext(source, context);
  documentListeners.DOMContentLoaded[0]();

  return { attributes, buttonListeners, context, documentListeners, navToggle };
}

function runCopyAction(loaded) {
  let prevented = false;
  const keyButton = {
    dataset: { keyAction: "copy" },
    getAttribute: () => "host-key",
  };
  const keyInput = {
    dataset: { expiresAt: String(Math.floor(Date.now() / 1000) + 30) },
    id: "host-key",
    value: "test-secret",
  };
  loaded.context.document.getElementById = (id) =>
    id === "toast-container" ? { appendChild() {} } : keyInput;
  loaded.documentListeners.click[0]({
    target: { closest: () => keyButton },
    preventDefault: () => { prevented = true; },
  });
  return { keyInput, prevented };
}

test("copies a revealed key with the textarea fallback when Clipboard API is unavailable", async () => {
  let copiedText = "";
  let removed = false;
  const loaded = loadHeaderScript({
    execCommand: (command) => {
      assert.equal(command, "copy");
      copiedText = "test-secret";
      return true;
    },
  });
  loaded.context.document.createElement = () => ({
    dataset: {},
    value: "",
    append() {},
    addEventListener() {},
    classList: { add() {}, remove() {} },
    style: {},
    setAttribute() {},
    focus() {},
    select() {},
    setSelectionRange() {},
    remove() { removed = true; },
  });

  assert.doesNotThrow(() => runCopyAction(loaded));
  await new Promise((resolve) => setImmediate(resolve));

  assert.equal(copiedText, "test-secret");
  assert.equal(removed, true);
});

test("uses the textarea fallback when Clipboard API rejects", async () => {
  let fallbackUsed = false;
  const loaded = loadHeaderScript({
    clipboard: { writeText: () => Promise.reject(new Error("insecure context")) },
    execCommand: () => { fallbackUsed = true; return true; },
  });

  runCopyAction(loaded);
  await new Promise((resolve) => setImmediate(resolve));

  assert.equal(fallbackUsed, true);
});

test("mobile menu toggle names the active section and exposes its expanded state", () => {
  const loaded = loadHeaderScript();

  assert.equal(loaded.attributes["aria-label"], "Open Manage Plugins navigation menu");
  assert.equal(loaded.attributes["aria-expanded"], "false");

  loaded.buttonListeners.click();

  assert.equal(loaded.attributes["aria-label"], "Close Manage Plugins navigation menu");
  assert.equal(loaded.attributes["aria-expanded"], "true");
});

test("PR auto-fix workflow has no write token or direct push", () => {
  const workflow = readFileSync(
    new URL("../.github/workflows/ci-security.yml", import.meta.url),
    "utf8",
  );

  assert.match(workflow, /lint-fix:[\s\S]*?permissions:\s*contents:\s*read/);
  assert.doesNotMatch(workflow, /contents:\s*write|pull-requests:\s*write|git push/);
  assert.match(workflow, /actions\/upload-artifact@v4/);
});
