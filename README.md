# Sistema Financeiro

Sistema web para controle financeiro pessoal desenvolvido em PHP, MySQL e Bootstrap.

## Senha Padrão De Login

e-mail: admin@admin.com
Senha: admin@123

## Estrutura do Projeto

```
financeiro/
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
├── config/
│   └── database.php
├── includes/
│   ├── auth.php
│   ├── functions.php
│   └── header.php
├── pages/
│   ├── dashboard.php
│   ├── transactions.php
│   ├── categories.php
│   └── profile.php
├── api/
│   ├── transactions/
│   │   ├── create.php
│   │   ├── read.php
│   │   ├── update.php
│   │   └── delete.php
│   └── categories/
│       ├── create.php
│       ├── read.php
│       ├── update.php
│       └── delete.php
├── database/
│   └── schema.sql
├── index.php
└── login.php
```

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor Web (Apache/Nginx)

## Funcionalidades

- Sistema de autenticação (login/registro)
- Dashboard com resumo financeiro
- Gerenciamento de receitas e despesas
- Categorização de transações
- Relatórios e gráficos
- Perfil de usuário
- API RESTful para operações CRUD

## Instalação

1. Clone o repositório
2. Importe o arquivo `database/schema.sql` no seu MySQL
3. Configure as credenciais do banco de dados em `config/database.php`
4. Acesse através do seu servidor web

## Segurança

- Proteção contra SQL Injection
- Senhas criptografadas
- Validação de sessão
- Sanitização de inputs
- Proteção contra CSRF

## Tecnologias Utilizadas

- PHP
- MySQL
- Bootstrap 5
- jQuery
- Chart.js (para gráficos)
- DataTables
- Font Awesome
