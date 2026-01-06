// ui/modal.js
console.log("modal.js is loaded");

const modal = document.getElementById("app-modal");
const titleEl = modal.querySelector(".modal-title");
const contentEl = modal.querySelector(".modal-content");
const okBtn = modal.querySelector("[data-action='ok']");
const cancelBtn = modal.querySelector("[data-action='cancel']");
const backdrop = modal.querySelector(".modal-backdrop");

function openModal({ title, message, confirm }) {
  console.log("MODAL OPEN");
  titleEl.textContent = title || "";
  contentEl.textContent = message || "";

  cancelBtn.style.display = confirm ? "" : "none";

  modal.classList.remove("hidden");

  return new Promise(resolve => {
    function cleanup(result) {
      console.log("MODAL CLOSED", result);
      modal.classList.add("hidden");
      okBtn.removeEventListener("click", okHandler);
      cancelBtn.removeEventListener("click", cancelHandler);
      backdrop.removeEventListener("click", backdropFeedback);
      resolve(result);
    }

    function okHandler() {
      cleanup(true);
    }

    function cancelHandler() {
      cleanup(false);
    }

    function backdropFeedback(e) {
      e.preventDefault();
      e.stopPropagation();

      const box = modal.querySelector(".modal-box");

      box.classList.remove("shake");
      void box.offsetWidth;
      box.classList.add("shake");
    }

    okBtn.addEventListener("click", okHandler);
    cancelBtn.addEventListener("click", cancelHandler);
    backdrop.addEventListener("click", backdropFeedback);
  });
}

export function showAlert(message, title = "Hinweis") {
  return openModal({ title, message, confirm: false });
}

export function showConfirm(message, title = "Bestätigen") {
  console.log("call to showConfirm");
  return openModal({ title, message, confirm: true });
}
