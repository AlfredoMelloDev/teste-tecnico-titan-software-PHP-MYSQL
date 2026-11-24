            </div>
        </main>
    </div>

    <!-- Janela de confirmação usada por excluir, finalizar e inativar (data-confirm). -->
    <dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-dialog-title">
        <div class="confirm-dialog__content">
            <div class="confirm-dialog__text">
                <h2 id="confirm-dialog-title">Confirmar operação?</h2>
                <p id="confirm-dialog-message"></p>
                <p class="confirm-dialog__warning" id="confirm-dialog-warning"></p>
            </div>

            <div class="confirm-dialog__actions">
                <button class="button button--secondary" id="confirm-dialog-cancel" type="button">Cancelar</button>
                <button class="button button--primary" id="confirm-dialog-ok" type="button">Confirmar</button>
            </div>
        </div>
    </dialog>
</body>

</html>
