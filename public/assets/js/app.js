'use strict';

/**
 * Adiciona uma confirmação aos formulários encontrados pelo seletor.
 */
function addFormConfirmation(selector, message) {
    const forms = document.querySelectorAll(selector);

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            const confirmed = window.confirm(message);

            if (!confirmed) {
                event.preventDefault();
            }
        });
    });
}

// Evita que um serviço seja removido por um clique acidental.
addFormConfirmation(
    '[data-confirm-delete]',
    'Tem certeza que deseja excluir este serviço?'
);

// A finalização registra a data e a comissão, não podendo ser desfeita pela tela.
addFormConfirmation(
    '[data-confirm-finish]',
    'Deseja finalizar este serviço? Ele não poderá mais ser editado.'
);