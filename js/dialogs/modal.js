// ui/modal.js

const modal = document.getElementById("app-modal");
const titleEl = modal.querySelector(".modal-title");
const contentEl = modal.querySelector(".modal-content");
const okBtn = modal.querySelector("[data-action='ok']");
const cancelBtn = modal.querySelector("[data-action='cancel']");
const backdrop = modal.querySelector(".modal-backdrop");

function openModal({ title, message, confirm }) {
  titleEl.textContent = title || "";
  contentEl.textContent = message || "";

  cancelBtn.style.display = confirm ? "" : "none";

  modal.classList.remove("hidden");

  return new Promise(resolve => {
    function cleanup(result) {
      modal.classList.add("hidden");
      okBtn.removeEventListener("click", okHandler);
      cancelBtn.removeEventListener("click", cancelHandler);
      backdrop.removeEventListener("click", cancelHandler);
      resolve(result);
    }

    function okHandler() {
      cleanup(true);
    }

    function cancelHandler() {
      cleanup(false);
    }

    okBtn.addEventListener("click", okHandler);
    cancelBtn.addEventListener("click", cancelHandler);
    backdrop.addEventListener("click", cancelHandler);
  });
}

export function showAlert(message, title = "Hinweis") {
  return openModal({ title, message, confirm: false });
}

export function showConfirm(message, title = "Bestätigen") {
  return openModal({ title, message, confirm: true });
}
