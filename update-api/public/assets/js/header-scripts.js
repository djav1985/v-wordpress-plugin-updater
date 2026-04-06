/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Displays a toast message.
 * @param {string} message - The message to display. */
function showToast(message)
{
  const toast = document.createElement("div");
  toast.className = "toast";
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.classList.add("show");
  }, 10);
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => {
      document.body.removeChild(toast);
    }, 500);
  }, 3000);
}

function lockMaskedKey(input)
{
  const maskedValue = input.dataset.maskedValue || "••••••••";
  input.value = maskedValue;
  input.dataset.expiresAt = "0";
  const copyButton = document.querySelector('button[data-copy-target="' + input.id + '"]');
  if (copyButton) {
    copyButton.disabled = true;
  }
}

function scheduleMaskExpiration(input)
{
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

  const copyButton = document.querySelector('button[data-copy-target="' + input.id + '"]');
  if (copyButton) {
    copyButton.disabled = false;
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
});

document.addEventListener("click", (e) => {
  if (e.target.classList.contains("copy-key-btn")) {
    const targetId = e.target.getAttribute("data-copy-target");
    if (!targetId) {
      return;
    }
    const keyInput = document.getElementById(targetId);
    if (!keyInput || keyInput.dataset.expiresAt === "0") {
      showToast("Reveal key before copying");
      return;
    }

    navigator.clipboard.writeText(keyInput.value).then(() => {
      showToast("Key copied to clipboard");
    });
  }
});
