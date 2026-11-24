'use strict';

/*
 * Janela de confirmação única para as operações irreversíveis
 * (finalizar, excluir, inativar). O formulário informa os textos em atributos:
 * data-confirm (título), data-confirm-message, data-confirm-warning,
 * data-confirm-button e data-confirm-variant ("danger" para botão vermelho).
 * A confirmação é só uma ajuda: o servidor valida tudo de novo.
 */
const confirmDialog = document.querySelector('#confirm-dialog');

if (confirmDialog instanceof HTMLDialogElement) {
    const titleElement = document.querySelector('#confirm-dialog-title');
    const messageElement = document.querySelector('#confirm-dialog-message');
    const warningElement = document.querySelector('#confirm-dialog-warning');
    const cancelButton = document.querySelector('#confirm-dialog-cancel');
    const okButton = document.querySelector('#confirm-dialog-ok');

    let pendingForm = null;

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();
            pendingForm = form;

            titleElement.textContent = form.dataset.confirm || 'Confirmar operação?';
            messageElement.textContent = form.dataset.confirmMessage || '';
            warningElement.textContent = form.dataset.confirmWarning || '';
            warningElement.hidden = !form.dataset.confirmWarning;
            okButton.textContent = form.dataset.confirmButton || 'Confirmar';
            okButton.className = 'button ' + (form.dataset.confirmVariant === 'danger'
                ? 'button--danger'
                : 'button--primary');

            confirmDialog.showModal();
        });
    });

    cancelButton.addEventListener('click', () => confirmDialog.close());

    okButton.addEventListener('click', () => {
        const form = pendingForm;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        // Evita que o envio abra a janela novamente.
        form.dataset.confirmed = 'true';
        pendingForm = null;
        confirmDialog.close();
        form.requestSubmit();
    });

    // Um clique na área escura também cancela.
    confirmDialog.addEventListener('click', (event) => {
        if (event.target === confirmDialog) {
            confirmDialog.close();
        }
    });

    confirmDialog.addEventListener('close', () => {
        pendingForm = null;
    });
}

/*
 * Busca de cliente por nome no formulário de serviço (RF06):
 * o texto digitado filtra as opções da lista de clientes ativos.
 */
document.querySelectorAll('[data-filter-select]').forEach((input) => {
    const select = document.getElementById(input.dataset.filterSelect);

    if (!(select instanceof HTMLSelectElement)) {
        return;
    }

    input.addEventListener('input', () => {
        const term = input.value.trim().toLocaleLowerCase('pt-BR');
        let firstVisible = null;

        Array.from(select.options).forEach((option) => {
            if (option.value === '') {
                return;
            }

            const visible = option.text.toLocaleLowerCase('pt-BR').includes(term);
            option.hidden = !visible;

            if (visible && firstVisible === null) {
                firstVisible = option;
            }
        });

        // Seleciona automaticamente o primeiro cliente encontrado quando nenhum está escolhido.
        const selected = select.selectedOptions[0];

        if (term !== '' && (!selected || selected.value === '' || selected.hidden) && firstVisible !== null) {
            select.value = firstVisible.value;
        }
    });
});
