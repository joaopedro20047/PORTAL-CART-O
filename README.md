# Documentação Completa do Sistema de Solicitação e Gestão de Credenciais para Idosos e PCD

## Índice

1. Introdução
2. Visão Geral do Sistema
3. Arquitetura de Sistema
4. Fluxo de Dados
5. Componentes do Sistema

   * 5.1 Frontend
   * 5.2 Backend PHP
   * 5.3 Banco de Dados MySQL
   * 5.4 Servidor Node.js (API de Notificação)
6. Painel Administrativo
7. Funcionalidades Avançadas
8. Segurança
9. Requisitos do Sistema
10. Processo de Instalação e Deploy
11. APIs Envolvidas
12. Possíveis Expansões Futuras
13. Conclusão

---

## 1. Introdução

Este documento descreve detalhadamente a implementação de um sistema web para solicitação e gestão de credenciais para estacionamento especial voltado a idosos e pessoas com deficiência (PCD). O sistema visa digitalizar, automatizar e organizar o processo de solicitação, análise e resposta.

---

## 2. Visão Geral do Sistema

O sistema permite que o cidadão envie uma solicitação online, selecione o tipo de credencial, anexe documentos obrigatórios e receba um protocolo de acompanhamento. Uma equipe administrativa pode acessar o painel admin, revisar solicitações, aprovar/reprovar, solicitar reenvios e emitir notificações automáticas por e-mail.

---

## 3. Arquitetura de Sistema

* **Frontend**: HTML5, CSS3, Bootstrap, JavaScript puro.
* **Backend**: PHP (processamento, armazenamento, autenticação).
* **Banco de Dados**: MySQL (armazenamento de dados e documentos).
* **Servidor de Notificações**: Node.js com Express + Nodemailer.

Camadas:

* Aplicação do usuário (index.html)
* Backend PHP (processar.php, admin.php)
* Banco de Dados MySQL
* Serviço de notificação (index.js)

---

## 4. Fluxo de Dados

1. Usuário acessa o `index.html` e preenche o formulário.
2. Dados são enviados via POST para `processar.php`.
3. `processar.php` valida e armazena no banco de dados.
4. É enviado e-mail de confirmação via `index.js` (Node).
5. Admin acessa `admin.html` com dados vindos de `admin.php`.
6. Admin pode atualizar status e acionar nova notificação via `index.js`.

---

## 5. Componentes do Sistema

### 5.1 Frontend

* `index.html`: Interface pública com formulário.
* `admin.html`: Interface administrativa.
* `configuracoes.html`: Painel de configurações visuais e operacionais.

### 5.2 Backend PHP

* `processar.php`: Processa dados do formulário, gera protocolo, armazena dados, comunica com a API.
* `admin.php`: Consulta e atualiza solicitações por status.
* `configuracoes.php`: Atualiza preferências e metadados administrativos.

### 5.3 Banco de Dados MySQL

* Tabelas:

  * `solicitacoes`: nome, cpf, email, documentos, status, protocolo
  * `configuracoes`: envio\_automatico, observacao\_personalizada
  * `log`: protocolo, ação, timestamp, operador

### 5.4 Servidor Node.js

* `index.js`: API REST para envio de e-mails
* Rotas:

  * `POST /email/confirmacao`
  * `POST /email/status`

---

## 6. Painel Administrativo

O painel permite:

* Visualizar solicitações por status (tabs)
* Ver detalhes e documentos em modal
* Atualizar status (Aprovado, Reprovado, Reenvio)
* Adicionar observações personalizadas
* Recarregar a página após operações

---

## 7. Funcionalidades Avançadas

* Dropdown com seleção de documentos para reenvio
* Gerador de protocolo único e seguro
* Modal responsivo para detalhes
* Integração com JSON de configuração (config/config.json)

---

## 8. Segurança

* `localStorage` usado para controle de acesso admin (melhor substituir por JWT).
* Uploads restritos a imagens e PDFs.
* CORS configurado no servidor Node.js.
* TLS desativado temporariamente para testes (NÃO recomendado em produção).

---

## 9. Requisitos do Sistema

* PHP 7.4+
* MySQL 5.7+
* Node.js 18+
* Navegador moderno (Chrome, Firefox)
* Servidor com suporte a HTTPS (em produção)

---

## 10. Instalação e Deploy

1. Clonar arquivos em servidor Apache + PHP
2. Criar banco de dados e importar esquema
3. Atualizar credenciais em arquivos PHP e Node.js
4. Executar `npm install && npm start` no diretório Node.js
5. Acessar via navegador o `index.html` e `admin.html`

---

## 11. APIs Envolvidas

### Email (Node.js)

* `/email/confirmacao`: Envia e-mail ao usuário com protocolo
* `/email/status`: Envia notificação de mudança de status

**Payload esperado:**

```json
{
  "email": "exemplo@dominio.com",
  "nome": "João da Silva",
  "protocolo": "ABC123XYZ"
}
```

**Resposta:**

```json
{ "success": true }
```

---

## 12. Possíveis Expansões Futuras

* App móvel (Flutter/Kivy) para acompanhar status
* Login com autenticação JWT + banco de tokens
* Notificações push via WebSocket
* Logs de auditoria com IP e horário
* Integração com sistema de emissão de carteirinhas
* Upload assíncrono com barra de progresso

---

## 13. Conclusão

O sistema oferece uma solução digital robusta, segura e flexível para solicitação, gestão e emissão de credenciais especiais. Facilita o trabalho de administrações públicas e traz mais transparência ao cidadão. Sua arquitetura modular permite manutenção e expansão fáceis para futuras demandas.
