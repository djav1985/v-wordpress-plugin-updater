import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import test from "node:test";
import { runInNewContext } from "node:vm";

test("upload feedback keeps every result and removes only the placeholder", () => {
  const placeholder = {
    id: "upload-status-placeholder",
    remove() {
      status.children = status.children.filter((child) => child !== placeholder);
    },
  };
  const status = {
    children: [placeholder],
    appendChild(child) {
      this.children.push(child);
    },
    querySelector(selector) {
      return selector === "#upload-status-placeholder"
        ? this.children.find((child) => child.id === "upload-status-placeholder") ?? null
        : selector === "p"
          ? this.children[0] ?? null
          : null;
    },
  };
  const handlers = {};
  const context = {
    Dropzone: class {
      constructor(_form, options) {
        options.init.call(this);
      }

      on(event, callback) {
        handlers[event] = callback;
      }
    },
    document: {
      createElement: () => ({
        setAttribute() {},
        remove() {
          status.children = status.children.filter((child) => child !== this);
        },
      }),
      getElementById: () => status,
      querySelectorAll: () => [{ dataset: { paramName: "package_file[]" } }],
    },
    window: { showToast() {} },
  };
  const source = readFileSync(
    new URL("../v-update-api/public/assets/js/uploads.js", import.meta.url),
    "utf8"
  );

  runInNewContext(source, context);
  handlers.success({}, "Plugin uploaded.");
  handlers.error({}, "Theme upload failed.");
  handlers.success({}, "Second plugin uploaded.");

  assert.deepEqual(
    status.children.map((child) => child.textContent),
    ["Plugin uploaded.", "Theme upload failed.", "Second plugin uploaded."]
  );
});
