'use strict';

// Evita que um serviço seja removido por um clique acidental.
const deleteForms = document.querySelectorAll('[data-confirm-delete]');

deleteForms.forEach((form) => {
    form.addEventListener('submit', (event) => {
        const confirmed = window.confirm(
            'Tem certeza que deseja excluir este serviço?'
        );

        if (!confirmed) {
            event.preventDefault();
        }
    });
});