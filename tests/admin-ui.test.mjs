import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import test from "node:test";
import { runInNewContext } from "node:vm";

function loadHeaderScript({ clipboard, execCommand = () => true, dateNow = () => Date.now() } = {}) {
  const documentListeners = {};
  const buttonListeners = {};
  const toastElements = [];
  const timeouts = [];
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
  const toastContainer = { appendChild: (toast) => toastElements.push(toast) };
  const document = {
    activeElement: null,
    body: { appendChild() {} },
    addEventListener: (name, callback) => {
      documentListeners[name] ??= [];
      documentListeners[name].push(callback);
    },
    createElement: () => {
      const listeners = {};
      return {
      dataset: {},
      append() {},
      addEventListener: (name, callback) => { listeners[name] = callback; },
      classList: { add() {}, remove() {} },
      listeners,
      setAttribute() {},
      style: {},
      focus() {},
      select() {},
      setSelectionRange() {},
      remove() {},
      };
    },
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
    Date: { now: dateNow },
    navigator: { clipboard },
    window: {
      matchMedia: () => ({ matches: true, addEventListener() {} }),
      requestAnimationFrame: (callback) => callback(),
      setTimeout: (callback, delay) => {
        const timeout = { callback, delay, cleared: false };
        timeouts.push(timeout);
        return timeout;
      },
      clearTimeout: (timeout) => { timeout.cleared = true; },
    },
  };
  const source = readFileSync(
    new URL("../v-update-api/public/assets/js/header-scripts.js", import.meta.url),
    "utf8",
  );
  runInNewContext(source, context);
  documentListeners.DOMContentLoaded[0]();

  return { attributes, buttonListeners, context, documentListeners, navToggle, timeouts, toastElements };
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

test("toast timer pauses on hover and resumes with the remaining time", () => {
  let now = 0;
  const loaded = loadHeaderScript({ dateNow: () => now });

  loaded.context.window.showToast("Saved");
  const toast = loaded.toastElements[0];
  const initialTimer = loaded.timeouts.find((timeout) => timeout.delay === 5000);

  assert.equal(typeof toast.listeners.mouseenter, "function");
  now = 1700;
  toast.listeners.mouseenter();
  assert.equal(initialTimer.cleared, true);

  now = 4500;
  toast.listeners.mouseleave();
  assert.equal(loaded.timeouts.at(-1).delay, 3300);
});

test("mobile menu toggle names the active section and exposes its expanded state", () => {
  const loaded = loadHeaderScript();

  assert.equal(loaded.attributes["aria-label"], "Open Manage Plugins navigation menu");
  assert.equal(loaded.attributes["aria-expanded"], "false");

  loaded.buttonListeners.click();

  assert.equal(loaded.attributes["aria-label"], "Close Manage Plugins navigation menu");
  assert.equal(loaded.attributes["aria-expanded"], "true");
});

test("PR auto-fix workflow commits fixes to the PR branch", () => {
  const workflow = readFileSync(
    new URL("../.github/workflows/ci-security.yml", import.meta.url),
    "utf8",
  );

  assert.match(workflow, /lint-fix:[\s\S]*?permissions:[\s\S]*?contents:\s*write/);
  assert.match(workflow, /git commit -m "chore: auto-lint fixes"/);
  assert.match(workflow, /git push origin ["']?HEAD:\$\{\{ github\.head_ref \}\}["']?/);
});
