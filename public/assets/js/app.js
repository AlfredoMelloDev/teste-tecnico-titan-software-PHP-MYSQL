'use strict';

/**
 * Mantém a confirmação simples para finalizar um serviço.
 */
const finishForms = document.querySelectorAll('[data-confirm-finish]');

finishForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
        const confirmed = window.confirm(
            'Deseja finalizar este serviço? Ele não poderá mais ser editado.'
        );

        if (!confirmed) {
            event.preventDefault();
        }
    });
});

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