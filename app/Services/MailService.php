<?php

declare(strict_types=1);

namespace App\Services;

final class MailService
{
    /**
     * Tenta enviar uma mensagem usando a configuração de e-mail do PHP.
     */
    public function send(
        string $recipient,
        string $subject,
        string $message
    ): bool {
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            $this->writeLog(
                $recipient,
                $subject,
                $message,
                'endereço de e-mail inválido'
            );

            return false;
        }

        $headers = [
            'From: sistema@jminformatica.local',
            'Reply-To: sistema@jminformatica.local',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];

        /*
         * O envio depende da configuração de e-mail do servidor.
         * Em ambiente local, mail() normalmente retorna false.
         */
        $sent = @mail(
            $recipient,
            $subject,
            $message,
            implode("\r\n", $headers)
        );

        $this->writeLog(
            $recipient,
            $subject,
            $message,
            $sent ? 'enviado' : 'não enviado pelo servidor local'
        );

        return $sent;
    }

    /**
     * Mantém um histórico das notificações para facilitar os testes locais.
     */
    private function writeLog(
        string $recipient,
        string $subject,
        string $message,
        string $status
    ): void {
        $content = sprintf(
            "[%s] Status: %s%sDestinatário: %s%sAssunto: %s%sMensagem: %s%s%s",
            date('Y-m-d H:i:s'),
            $status,
            PHP_EOL,
            $recipient,
            PHP_EOL,
            $subject,
            PHP_EOL,
            $message,
            PHP_EOL,
            str_repeat('-', 60) . PHP_EOL
        );

        error_log(
            $content,
            3,
            dirname(__DIR__, 2) . '/storage/logs/mail.log'
        );
    }
}