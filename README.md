# Sistema de Controle de Serviços

Sistema web para gerenciamento dos serviços prestados pelos funcionários da JM Informática.

A aplicação foi desenvolvida em PHP orientado a objetos, com arquitetura MVC própria, acesso ao MySQL por PDO e JavaScript puro. O projeto não utiliza frameworks, Composer ou gerenciadores externos de dependências.

## Funcionalidades

- Cadastro de usuários;
- Autenticação por e-mail e senha;
- Controle de acesso por sessão;
- Encerramento seguro da sessão;
- Dashboard com informações do usuário autenticado;
- Indicadores individuais de serviços, valores, pendências e finalizações;
- Cadastro de serviços para o usuário autenticado;
- Listagem dos serviços e seus responsáveis;
- Edição de serviços pendentes;
- Exclusão de serviços com confirmação;
- Finalização de serviços;
- Registro da data de finalização;
- Cálculo automático da comissão;
- Notificação por e-mail após a finalização;
- Filtro por descrição do serviço;
- Filtro por nome do usuário;
- Filtro por status;
- Filtro por período;
- Mensagens temporárias de sucesso e erro;
- Proteção CSRF nos formulários;
- Registro de erros e tentativas de e-mail;
- Interface responsiva para computadores, tablets e celulares.

## Regras de negócio

### Situação do serviço

O status não é armazenado em um campo separado:

- Sem data de finalização: `Pendente`;
- Com data de finalização: `Finalizado`.

Depois de finalizado, o serviço não pode mais ser editado pela aplicação.

### Comissão

A comissão é calculada no momento da finalização:

| Valor do serviço | Comissão |
|---|---:|
| Até R$ 1.000,00 | 5% |
| Acima de R$ 1.000,00 até R$ 10.000,00 | 10% |
| Acima de R$ 10.000,00 | 20% |

Exemplos:

| Serviço | Percentual | Comissão |
|---:|---:|---:|
| R$ 800,00 | 5% | R$ 40,00 |
| R$ 2.500,00 | 10% | R$ 250,00 |
| R$ 12.000,00 | 20% | R$ 2.400,00 |

### Indicadores do dashboard

Os cards e a lista de pendências consideram os serviços do usuário autenticado. A tabela geral permite consultar os serviços de todos os funcionários.

Os filtros da tabela não alteram os indicadores individuais.

## Tecnologias

- PHP 8;
- MySQL;
- PDO;
- HTML5;
- CSS3;
- JavaScript;
- Arquitetura MVC;
- Servidor interno do PHP.

## Restrições respeitadas

- Sem framework de backend;
- Sem framework de frontend;
- Sem Composer;
- Sem ORM;
- Consultas realizadas diretamente com PDO;
- JavaScript sem bibliotecas externas.

## Arquitetura

```text
projeto/
├── app/
│   ├── Controllers/
│   ├── Core/
│   ├── Models/
│   ├── Services/
│   └── Views/
├── config/
│   └── database.example.php
├── database/
│   └── schema.sql
├── public/
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── index.php
├── storage/
│   └── logs/
├── .gitignore
└── README.md
```

### Responsabilidades

- `app/Controllers`: recebe as requisições e coordena as operações;
- `app/Core`: contém roteamento, autenticação, CSRF, autoload e conexão;
- `app/Models`: executa consultas e alterações no banco;
- `app/Services`: concentra regras como comissão e envio de e-mail;
- `app/Views`: contém as páginas apresentadas ao usuário;
- `config`: contém o modelo de configuração do banco;
- `database`: contém o script de criação das tabelas;
- `public`: ponto de entrada e arquivos públicos;
- `storage/logs`: armazena registros locais da aplicação.

## Requisitos

- PHP 8 ou superior;
- MySQL;
- Git;
- Extensão PHP `PDO`;
- Extensão PHP `pdo_mysql`;
- Extensão PHP `session`.

Para conferir as extensões:

```powershell
php -m
```

## Instalação

### 1. Clonar o repositório

```powershell
git clone https://github.com/AlfredoMelloDev/teste-tecnico-titan-software-PHP-MYSQL.git
cd teste-tecnico-titan-software-PHP-MYSQL
```

### 2. Criar o banco

Entre no MySQL com um usuário autorizado:

```powershell
mysql -h 127.0.0.1 -P 3306 -u seu_usuario -p
```

Quando aparecer `mysql>`, execute:

```sql
CREATE DATABASE IF NOT EXISTS teste_titan
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE teste_titan;
```

### 3. Importar as tabelas

Ainda no monitor do MySQL, utilize o caminho absoluto do projeto:

```sql
SOURCE C:/caminho/do/projeto/database/schema.sql;
```

No Windows, utilize barras `/` no comando `SOURCE`.

Também é possível abrir `database/schema.sql` no SQLTools, selecionar o banco `teste_titan` e executar o arquivo.

O script cria:

- Tabela `user`;
- Tabela `service`;
- Chave estrangeira entre serviço e usuário;
- Índices utilizados nas consultas.

### 4. Configurar a conexão

Crie uma cópia do arquivo de exemplo.

No PowerShell:

```powershell
Copy-Item config/database.example.php config/database.php
```

No Linux ou macOS:

```bash
cp config/database.example.php config/database.php
```

Edite `config/database.php`:

```php
<?php

declare(strict_types=1);

return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'teste_titan',
    'charset' => 'utf8mb4',
    'username' => 'seu_usuario',
    'password' => 'sua_senha',
];
```

O arquivo `config/database.php` está no `.gitignore` e não deve ser enviado ao repositório.

### 5. Iniciar a aplicação

Na raiz do projeto:

```powershell
php -S 127.0.0.1:8000 -t public public/index.php
```

Acesse:

```text
http://127.0.0.1:8000
```

Mantenha o terminal aberto enquanto estiver utilizando o sistema.

### 6. Criar o primeiro usuário

Na página de login:

1. Clique em **Cadastrar usuário**;
2. Informe nome, e-mail e senha;
3. Conclua o cadastro;
4. Entre com as credenciais criadas.

O projeto não fornece senhas ou usuários fixos.

## Verificação da conexão

Na raiz do projeto:

```powershell
php -r "require 'app/Core/Database.php'; App\Core\Database::connection(); echo 'Conexao PDO realizada com sucesso.' . PHP_EOL;"
```

Resultado esperado:

```text
Conexao PDO realizada com sucesso.
```

## Verificação da sintaxe

Para verificar um arquivo:

```powershell
php -l public/index.php
```

Para verificar todos os arquivos PHP no PowerShell:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
    php -l $_.FullName
}
```

## Envio de e-mail

A aplicação tenta enviar uma notificação quando um serviço é finalizado.

O envio usa a função nativa `mail()` do PHP e depende da configuração de e-mail do servidor. Em ambientes locais sem essa configuração, o serviço é finalizado normalmente e a tentativa fica registrada em:

```text
storage/logs/mail.log
```

Para consultar no Windows PowerShell:

```powershell
Get-Content .\storage\logs\mail.log -Tail 20 -Encoding UTF8
```

Em um ambiente de produção, o servidor deve possuir um transporte de e-mail configurado.

## Segurança

O projeto utiliza:

- `password_hash()` para armazenar senhas;
- `password_verify()` durante a autenticação;
- Renovação do identificador após o login;
- Consultas preparadas com PDO;
- Emulação de prepared statements desabilitada;
- Tokens CSRF nas requisições `POST`;
- Escape de conteúdo com `htmlspecialchars`;
- Validação dos dados recebidos;
- Controle de acesso às páginas autenticadas;
- Credenciais locais ignoradas pelo Git;
- Mensagens de erro técnico registradas fora da interface.

## Logs

Os registros locais ficam em:

```text
storage/logs/
```

Os arquivos gerados não são enviados ao Git. Apenas `.gitkeep` preserva a pasta no repositório.

## Banco de dados

O script solicitado para criação das tabelas está disponível em:

```text
database/schema.sql
```

As tabelas principais são:

- `user`: usuários e credenciais do sistema;
- `service`: serviços, valores, finalização, comissão e responsável.

## Autor

Desenvolvido por [Alfredo Mello](https://github.com/AlfredoMelloDev).
