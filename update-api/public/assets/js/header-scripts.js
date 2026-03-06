/**
 * @package UpdateAPI
 * @author  Vontainment <services@vontainment.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    https://vontainment.com
 *
 * Displays a toast message.
 * @param {string} message - The message to display. */
function showToast(message) {
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

document.addEventListener("click", (e) => {
    if (e.target.classList.contains("hosts-key")) {
        const key = e.target.value;
        navigator.clipboard.writeText(key).then(() => {
            showToast("Key copied to clipboard");
        });
    }
});
