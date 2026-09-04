'use strict';


// Exibe um modal próprio antes de finalizar um serviço.
 
const finishDialog = document.querySelector('#finish-dialog');
const finishForms = document.querySelectorAll('[data-confirm-finish]');
const cancelFinishButton = document.querySelector('#cancel-finish');
const confirmFinishButton = document.querySelector('#confirm-finish');
const finishDescription = document.querySelector(
    '#finish-service-description'
);

let pendingFinishForm = null;

if (
    finishDialog instanceof HTMLDialogElement
    && cancelFinishButton instanceof HTMLButtonElement
    && confirmFinishButton instanceof HTMLButtonElement
    && finishDescription instanceof HTMLElement
) {
    finishForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();
            pendingFinishForm = form;

            finishDescription.textContent =
                form.dataset.serviceDescription || 'este serviço';

            finishDialog.showModal();
        });
    });

    cancelFinishButton.addEventListener('click', () => {
        finishDialog.close();
    });

    confirmFinishButton.addEventListener('click', () => {
        const formToSubmit = pendingFinishForm;

        if (!(formToSubmit instanceof HTMLFormElement)) {
            return;
        }

        // Evita que o envio abra o modal novamente.
        formToSubmit.dataset.confirmed = 'true';
        pendingFinishForm = null;

        finishDialog.close();
        formToSubmit.requestSubmit();
    });

    // Um clique na área escura também cancela a operação.
    finishDialog.addEventListener('click', (event) => {
        if (event.target === finishDialog) {
            finishDialog.close();
        }
    });

    finishDialog.addEventListener('close', () => {
        pendingFinishForm = null;
    });
}

/**
 * Exibe um modal próprio antes de enviar a exclusão.
 */
const deleteDialog = document.querySelector('#delete-dialog');
const deleteForms = document.querySelectorAll('[data-confirm-delete]');
const cancelDeleteButton = document.querySelector('#cancel-delete');
const confirmDeleteButton = document.querySelector('#confirm-delete');
const deleteDescription = document.querySelector(
    '#delete-service-description'
);

let pendingDeleteForm = null;

if (
    deleteDialog instanceof HTMLDialogElement
    && cancelDeleteButton instanceof HTMLButtonElement
    && confirmDeleteButton instanceof HTMLButtonElement
    && deleteDescription instanceof HTMLElement
) {
    deleteForms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();
            pendingDeleteForm = form;

            deleteDescription.textContent =
                form.dataset.serviceDescription || 'este serviço';

            deleteDialog.showModal();
        });
    });

    cancelDeleteButton.addEventListener('click', () => {
        deleteDialog.close();
    });

    confirmDeleteButton.addEventListener('click', () => {
        const formToSubmit = pendingDeleteForm;

        if (!(formToSubmit instanceof HTMLFormElement)) {
            return;
        }

        // A marca evita que o formulário abra o modal novamente.
        formToSubmit.dataset.confirmed = 'true';
        pendingDeleteForm = null;

        deleteDialog.close();
        formToSubmit.requestSubmit();
    });

    // Um clique na área escura também cancela a operação.
    deleteDialog.addEventListener('click', (event) => {
        if (event.target === deleteDialog) {
            deleteDialog.close();
        }
    });

    deleteDialog.addEventListener('close', () => {
        pendingDeleteForm = null;
    });
}