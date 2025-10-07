# Sistema de Reserva de Salas API

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-336791?style=for-the-badge&logo=postgresql)
![Redis](https://img.shields.io/badge/Redis-7.x-DC382D?style=for-the-badge&logo=redis)
![Docker](https://img.shields.io/badge/Docker-20.10%2B-2496ED?style=for-the-badge&logo=docker)

---

## 📋 Sobre o Projeto

Esta é uma API RESTful para um sistema de reserva de salas.

O projeto foi construído utilizando Laravel 12 e Docker, com foco em uma arquitetura robusta, processamento assíncrono com filas e implementação de regras de negócio, como validação de disponibilidade, capacidade e limites de uso por cliente.

### ✨ Funcionalidades Principais

-   **Autenticação Segura:** Sistema de login baseado em token (Laravel Sanctum).
-   **Gestão de Reservas:** Fluxo completo para criar, listar e cancelar reservas.
-   **Confirmação Assíncrona:** As reservas são processadas em segundo plano utilizando filas no Redis para garantir uma resposta rápida da API.
-   **Consulta de Disponibilidade:** Endpoint otimizado com cache para consultar horários livres em uma sala.
-   **Validação de Regras de Negócio:**
    -   Janela de reserva (duração mínima e máxima).
    -   Validação de choque de horário.
    -   Validação de capacidade da sala.
    -   Limite diário de reservas por cliente.
-   **Webhooks:** Notificações automáticas via POST para eventos de confirmação e cancelamento.

---

## 📖 Documentação da API

A documentação completa dos endpoints, incluindo exemplos de requisições e respostas, está disponível no portal SwaggerHub.

-   **[Acessar a Documentação da API](https://donchedev.portal.swaggerhub.com/room-reservation/docs/room-reservation-v-1-0-0)**

Uma coleção do Postman também está disponível no repositório para facilitar os testes: `room-reservation.postman_collection`.

---

## 🚀 Começando

Siga os passos abaixo para configurar e executar o ambiente de desenvolvimento localmente.

### Pré-requisitos

-   [Docker](https://www.docker.com/get-started)
-   [Docker Compose](https://docs.docker.com/compose/install/)

### Instalação Local

1.  **Clone o repositório:**
    ```bash
    git clone https://github.com/RafaelDonche/room-reservation.git
    cd room-reservation
    ```

2.  **Configure as variáveis de ambiente:**
    Copie o arquivo de exemplo `.env.example` para `.env`. As configurações padrão já estão prontas para o ambiente Docker.
    ```bash
    cp .env.example .env
    ```

3.  **Construa e suba os contêineres Docker:**
    Este comando irá construir as imagens e iniciar todos os serviços (`app`, `nginx`, `db`, `redis`, `worker`) em segundo plano.
    ```bash
    docker-compose up -d --build
    ```

4.  **Instale as dependências do Composer:**
    ```bash
    docker-compose exec app composer install
    ```

5.  **Gere a chave da aplicação Laravel:**
    ```bash
    docker-compose exec app php artisan key:generate
    ```

6.  **Execute as Migrations do Banco de Dados:**
    Este comando criará todas as tabelas necessárias no banco de dados PostgreSQL.
    ```bash
    docker-compose exec app php artisan migrate
    ```

7.  **Execute os Seeders para popular o banco:**
    Este comando criará salas de exemplo e um usuário de teste para que você possa usar a API imediatamente.
    ```bash
    docker-compose exec app php artisan db:seed
    ```

---

## ▶️ Uso

Após a instalação, a aplicação estará disponível em `http://localhost:8000`.

-   **URL Base da API:** `http://localhost:8000/api`
-   **Usuário de Teste (criado pelo Seeder):**
    -   **Email:** `test@example.com`
    -   **Senha:** `123456`

    -   **Senha de outros usuários:** `password`

Utilize o Postman ou a documentação no SwaggerHub para interagir com os endpoints.

---

## ✅ Testes

Para executar a suíte de testes automatizados, utilize o seguinte comando:

```bash
docker-compose exec app php artisan test
