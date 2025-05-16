# Mini ERP
Um sistema de gerenciamento de e-commerce com funcionalidades de produtos, carrinho, cupons, checkout e webhook.
Funcionalidades

* Produtos: Listar, adicionar produtos ao carrinho.
* Cupons: Criar e aplicar cupons de desconto.
* Checkout: Finalizar pedidos com cálculo de frete e envio de e-mail.
* Webhook: Atualizar ou cancelar pedidos via API.

## Pré-requisitos

* PHP >= 8.4
* MySQL
* PHPMailer (incluído em libs/)
* Mailtrap (para teste de e-mails)

## Instalação

Clone o repositório:  
```
git clone https://github.com/seu_usuario/mini_erp.git
cd mini_erp
```


Configure o banco de dados:  
```
mysql -u root -p < database.sql
```


Atualize as credenciais em config/database.php e config/email.php (use Mailtrap para e-mails).
Inicie o servidor:
```
cd public
php -S localhost:8000
```


## Endpoints

* Produtos: http://localhost:8000/produtos.php
* Cupons: http://localhost:8000/cupons.php
* Checkout: http://localhost:8000/checkout.php
* Webhook: http://localhost:8000/webhook.php 



## Testes
    (POST, JSON: {"id": 1, "status": "cancelado"})  

### Navegação:  
Acesse os endpoints acima no navegador.  
Webhook: Use Postman ou cURL:  
```
curl -X POST http://localhost:8000/  
webhook.php -H "Content-Type: application/json" -d '{"id": 1, "status": "cancelado"}'
```


### E-mails: 
Verifique e-mails de confirmação no Mailtrap.

## Estrutura do Projeto  
mini_erp/  
├── config/           # Configurações (banco, e-mail)  
├── libs/            # Bibliotecas (PHPMailer)  
├── public/          # Arquivos acessíveis (PHP, HTML)  
├── logs/            # Logs de erros  
├── database.sql     # Script do banco  
├── .gitignore  
├── README.md  
├── LICENSE 



