<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Service;
use Throwable;

final class ServiceController extends Controller
{
    private Service $services;

    public function __construct()
    {
        // O Model concentra todas as operações da tabela de serviços.
        $this->services = new Service();
    }

    public function showCreate(): void
    {
        // Somente usuários autenticados podem cadastrar serviços.
        Auth::requireLogin();

        // Recupera a mensagem e os campos da tentativa anterior.
        $error = $_SESSION['service_form_error'] ?? null;
        $old = $_SESSION['service_form_old'] ?? [];

        // Esses valores devem permanecer disponíveis por apenas uma requisição.
        unset(
            $_SESSION['service_form_error'],
            $_SESSION['service_form_old']
        );

        $this->view('services/create', [
            'title' => 'Cadastrar serviço',
            'error' => $error,
            'old' => $old,
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();

        // Remove espaços desnecessários antes de validar os campos.
        $description = trim($_POST['description'] ?? '');
        $priceInput = trim($_POST['price'] ?? '');

        // Converte o valor informado para o formato utilizado pelo MySQL.
        $price = $this->normalizePrice($priceInput);

        if (
            $description === ''
            || strlen($description) > 255
            || $price === null
        ) {
            $this->redirectWithError(
                'Informe uma descrição e um valor válido.',
                $description,
                $priceInput
            );
        }

        try {
            // O serviço sempre pertence ao usuário que está logado.
            $this->services->create(
                $description,
                $price,
                (int) Auth::id()
            );

            $_SESSION['dashboard_success'] =
                'Serviço cadastrado com sucesso.';
        } catch (Throwable $exception) {

            /*
             * O usuário recebe uma mensagem simples, enquanto o erro completo     --------
             * fica registrado para ajudar na identificação do problema.
             */

            error_log(
                '[' . date('Y-m-d H:i:s') . '] '
                    . $exception->getMessage()
                    . PHP_EOL,
                3,
                dirname(__DIR__, 2) . '/storage/logs/app.log'
            );

            $_SESSION['dashboard_error'] =
                'Não foi possível cadastrar o serviço.';
        }

        // O redirecionamento evita o reenvio do formulário ao atualizar a página.
        header('Location: /dashboard');
        exit;
    }

    public function showEdit(): void
    {
        Auth::requireLogin();

        $serviceId = filter_var(
            $_GET['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($serviceId === false || $serviceId < 1) {
            $_SESSION['dashboard_error'] =
                'O serviço informado é inválido.';

            header('Location: /dashboard');
            exit;
        }

        $service = $this->services->findById($serviceId);

        if ($service === null) {
            $_SESSION['dashboard_error'] =
                'O serviço não foi encontrado.';

            header('Location: /dashboard');
            exit;
        }

        if ($service['finished_at'] !== null) {
            $_SESSION['dashboard_error'] =
                'Um serviço finalizado não pode ser alterado.';

            header('Location: /dashboard');
            exit;
        }

        $error = $_SESSION['service_edit_error'] ?? null;
        $old = $_SESSION['service_edit_old'] ?? [];

        unset(
            $_SESSION['service_edit_error'],
            $_SESSION['service_edit_old']
        );

        $this->view('services/edit', [
            'title' => 'Editar serviço',
            'service' => $service,
            'error' => $error,
            'old' => $old,
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();

        $serviceId = filter_var(
            $_POST['service_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $description = trim($_POST['description'] ?? '');
        $priceInput = trim($_POST['price'] ?? '');
        $price = $this->normalizePrice($priceInput);

        if (
            $serviceId === false
            || $serviceId < 1
            || $description === ''
            || strlen($description) > 255
            || $price === null
        ) {
            $this->redirectEditWithError(
                (int) $serviceId,
                'Informe uma descrição e um valor válido.',
                $description,
                $priceInput
            );
        }

        $service = $this->services->findById($serviceId);

        if ($service === null || $service['finished_at'] !== null) {
            $_SESSION['dashboard_error'] =
                'O serviço não existe ou já foi finalizado.';

            header('Location: /dashboard');
            exit;
        }

        try {
            $this->services->update(
                $serviceId,
                $description,
                $price
            );

            $_SESSION['dashboard_success'] =
                'Serviço atualizado com sucesso.';
        } catch (Throwable $exception) {
            error_log(
                '[' . date('Y-m-d H:i:s') . '] '
                    . $exception->getMessage()
                    . PHP_EOL,
                3,
                dirname(__DIR__, 2) . '/storage/logs/app.log'
            );

            $_SESSION['dashboard_error'] =
                'Não foi possível atualizar o serviço.';
        }

        header('Location: /dashboard');
        exit;
    }

    private function redirectEditWithError(
        int $serviceId,
        string $message,
        string $description,
        string $price
    ): never {
        $_SESSION['service_edit_error'] = $message;
        $_SESSION['service_edit_old'] = [
            'description' => $description,
            'price' => $price,
        ];

        header('Location: /services/edit?id=' . $serviceId);
        exit;
    }
    
    private function normalizePrice(string $price): ?string
    {
        // Permite que o usuário use vírgula ou ponto como separador decimal.
        $price = str_replace(',', '.', trim($price));

        /*
         * Aceita até nove dígitos inteiros e duas casas decimais.
         * Letras, números negativos e separadores inválidos são rejeitados.
         */
        if (!preg_match('/^\d{1,9}(?:\.\d{1,2})?$/', $price)) {
            return null;
        }

        // Separa a parte inteira e a parte decimal para padronizar o valor.
        [$integer, $decimal] = array_pad(
            explode('.', $price, 2),
            2,
            ''
        );

        // Remove zeros à esquerda, preservando o zero quando necessário.
        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;

        // O banco utiliza sempre duas casas decimais para valores monetários.
        $decimal = str_pad($decimal, 2, '0');

        // Um serviço deve possuir valor maior que zero.
        if ($integer === '0' && $decimal === '00') {
            return null;
        }

        return $integer . '.' . $decimal;
    }

    private function redirectWithError(
        string $message,
        string $description,
        string $price
    ): never {
        // Guarda os dados válidos para preencher novamente o formulário.
        $_SESSION['service_form_error'] = $message;
        $_SESSION['service_form_old'] = [
            'description' => $description,
            'price' => $price,
        ];

        header('Location: /services/create');
        exit;
    }
}
