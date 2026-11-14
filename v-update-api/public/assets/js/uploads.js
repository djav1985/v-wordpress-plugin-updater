/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Initializes the plugin and theme package upload forms.
 */
Dropzone.autoDiscover = false;

document.querySelectorAll("form[data-dropzone]").forEach((form) => {
  const status = document.getElementById("message-container");
  if (!status) {
    return;
  }

  const appendStatus = (message, level) => {
    const emptyState = status.querySelector("#upload-status-placeholder");
    if (emptyState) {
      emptyState.remove();
    }
    const item = document.createElement("p");
    item.className = level === "error" ? "error-message" : "success-message";
    item.setAttribute("role", level === "error" ? "alert" : "status");
    item.textContent = String(message);
    status.appendChild(item);
  };

  new Dropzone(form, {
    paramName: form.dataset.paramName,
    maxFilesize: 200,
    acceptedFiles: "application/zip,application/x-zip-compressed,multipart/x-zip",
    autoProcessQueue: true,
    parallelUploads: 6,
    init: function () {
      this.on("success", (_file, response) => {
        const message = String(response || "Upload completed.");
        appendStatus(message, "success");
        window.showToast(message, "success");
      });

      this.on("error", (_file, errorMessage) => {
        const message =
          typeof errorMessage === "string"
            ? errorMessage
            : errorMessage?.message || "The upload failed. Please try again.";
        appendStatus(message, "error");
        window.showToast(message, "error");
      });
    },
  });
});
