/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Displays an accessible Tabler toast.
 * @param {string} message - The message to display.
 * @param {string} [variant=info] - The message status.
 */
function showToast(message, variant = "info") {
  const container = document.getElementById("toast-container");
  if (!container) {
    return;
  }

  const safeVariant = ["success", "error", "info"].includes(variant)
    ? variant
    : "info";
  const toast = document.createElement("div");
  toast.className = `toast toast-${safeVariant}`;
  toast.setAttribute("role", safeVariant === "error" ? "alert" : "status");
  toast.setAttribute("aria-live", safeVariant === "error" ? "assertive" : "polite");
  toast.setAttribute("aria-atomic", "true");

  const body = document.createElement("div");
  body.className = "toast-body d-flex align-items-start gap-2";
  const text = document.createElement("span");
  text.className = "flex-grow-1";
  text.textContent = String(message);
  const close = document.createElement("button");
  close.type = "button";
  close.className = "btn-close";
  close.setAttribute("aria-label", "Dismiss notification");
  body.append(text, close);
  toast.append(body);
  container.appendChild(toast);

  const dismiss = () => {
    toast.classList.remove("show");
    window.setTimeout(() => toast.remove(), 220);
  };
  close.addEventListener("click", dismiss, { once: true });
  window.requestAnimationFrame(() => toast.classList.add("show"));
  let remaining = 5000;
  let startedAt = Date.now();
  let timeoutId = window.setTimeout(dismiss, remaining);
  toast.addEventListener("mouseenter", () => {
    window.clearTimeout(timeoutId);
    remaining -= Date.now() - startedAt;
  });
  toast.addEventListener("mouseleave", () => {
    startedAt = Date.now();
    timeoutId = window.setTimeout(dismiss, Math.max(remaining, 500));
  });
}

window.showToast = showToast;

async function copyTextToClipboard(value) {
  if (typeof navigator.clipboard?.writeText === "function") {
    try {
      await navigator.clipboard.writeText(value);
      return true;
    } catch {
      // Fall through to the selection-based fallback for HTTP and restricted contexts.
    }
  }

  const previousFocus = document.activeElement;
  const textarea = document.createElement("textarea");
  textarea.value = value;
  textarea.setAttribute("readonly", "");
  textarea.setAttribute("aria-hidden", "true");
  textarea.style.position = "fixed";
  textarea.style.opacity = "0";
  document.body.appendChild(textarea);
  textarea.select();

  let copied;
  try {
    copied = document.execCommand?.("copy") === true;
  } catch {
    copied = false;
  }
  textarea.remove();
  if (typeof previousFocus?.focus === "function") previousFocus.focus({ preventScroll: true });
  return copied;
}

function lockMaskedKey(input) {
  const maskedValue = input.dataset.maskedValue || "••••••••";
  input.value = maskedValue;
  input.dataset.expiresAt = "0";
  const keyButton = document.querySelector(
    'button[data-key-target="' + input.id + '"]',
  );
  if (keyButton) {
    keyButton.textContent = "Reveal";
    keyButton.dataset.keyAction = "reveal";
    keyButton.type = "submit";
  }
}

function scheduleMaskExpiration(input) {
  const expiresAt = Number(input.dataset.expiresAt || "0");
  if (!expiresAt) {
    lockMaskedKey(input);
    return;
  }
  const msRemaining = expiresAt * 1000 - Date.now();
  if (msRemaining <= 0) {
    lockMaskedKey(input);
    return;
  }

  const keyButton = document.querySelector(
    'button[data-key-target="' + input.id + '"]',
  );
  if (keyButton) {
    keyButton.textContent = "Copy";
    keyButton.dataset.keyAction = "copy";
    keyButton.type = "button";
  }
  window.setTimeout(() => {
    lockMaskedKey(input);
    showToast("Key hidden again");
  }, msRemaining);
}

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("input.hosts-key").forEach((input) => {
    scheduleMaskExpiration(input);
  });

  const navWrap = document.querySelector(".admin-nav-wrap");
  const navToggle = document.querySelector(".mobile-menu-toggle");
  const nav = navWrap?.querySelector(".admin-nav");
  if (!navWrap || !navToggle || !nav) return;
  const activeSection = navToggle.dataset.menuSection || "section";

  navToggle.hidden = false;
  navWrap.classList.add("mobile-nav-ready");
  const mobileViewport = window.matchMedia("(max-width: 767.98px)");

  const setNavOpen = (open) => {
    navToggle.setAttribute("aria-expanded", String(open));
    navToggle.setAttribute(
      "aria-label",
      `${open ? "Close" : "Open"} ${activeSection} navigation menu`,
    );
    navWrap.classList.toggle("is-open", open);
    nav.inert = mobileViewport.matches && !open;
  };

  setNavOpen(false);

  navToggle.addEventListener("click", () => {
    setNavOpen(navToggle.getAttribute("aria-expanded") !== "true");
  });

  document.addEventListener("click", (event) => {
    if (!navWrap.contains(event.target) && !navToggle.contains(event.target)) setNavOpen(false);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && navToggle.getAttribute("aria-expanded") === "true") {
      setNavOpen(false);
      navToggle.focus();
    }
  });

  mobileViewport.addEventListener("change", (event) => {
    setNavOpen(false);
    nav.inert = event.matches;
  });
});

document.addEventListener("click", (e) => {
  const keyButton = e.target.closest("button.copy-key-btn");
  if (keyButton && keyButton.dataset.keyAction === "copy") {
    e.preventDefault();
    const targetId = keyButton.getAttribute("data-key-target");
    if (!targetId) {
      return;
    }
    const keyInput = document.getElementById(targetId);
    if (!keyInput || Number(keyInput.dataset.expiresAt || "0") * 1000 <= Date.now()) {
      if (keyInput) {
        lockMaskedKey(keyInput);
      }
      showToast("Reveal key before copying");
      return;
    }

    copyTextToClipboard(keyInput.value)
      .then((copied) => {
        showToast(
          copied ? "Key copied to clipboard" : "Could not copy the key to the clipboard",
          copied ? "success" : "error",
        );
      })
      .catch(() => showToast("Could not copy the key to the clipboard", "error"));
  }
});
