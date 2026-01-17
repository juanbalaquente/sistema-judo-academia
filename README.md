# 🥋 Sistema de Gestão para Academia de Judô (KODOKAN JUDO MANAGER)

Este é um sistema web completo desenvolvido para gerenciar alunos, presenças, mensalidades e campeonatos de uma academia de Judô.

---

## 💻 Tecnologias Utilizadas

Este projeto foi construído utilizando a clássica pilha de desenvolvimento web (LAMP/XAMPP Stack):

* **Frontend:** HTML5, CSS3, JavaScript
* **Backend:** PHP 7.x/8.x
* **Banco de Dados:** MySQL (gerenciado via phpMyAdmin no XAMPP)
* **Ambiente Local:** XAMPP (Apache + MySQL + PHP)
* **Controle de Versão:** Git e GitHub
* **IDE:** Visual Studio Code (VS Code)

---

## ✨ Funcionalidades Principais

O sistema oferece uma solução robusta com as seguintes funcionalidades:

### 👤 Gestão de Alunos (CRUD)
* **Cadastro e Edição:** Inclusão e modificação completa dos dados do Judoca (Nome, Faixa, Peso, Contato).
* **Filtro e Busca:** Lista de alunos com paginação e filtros por Nome ou Faixa.
* **Currículo do Judoca:** Página de perfil unificada que exibe todos os dados do aluno, histórico de presença e histórico de campeonatos.

### 💰 Controle Financeiro
* **Mensalidades:** Lançamento automático de cobranças mensais.
* **Valores Variáveis:** Possibilidade de definir e editar o valor da mensalidade individualmente (isentos, valores promocionais, etc.).
* **Painel Financeiro:** Visão de pagamentos **Pendentes**, **Atrasados** e **Pagos** (mês atual).
* **Lançamento Detalhado:** Formulário para registrar o pagamento com valor e data customizáveis.

### 📅 Presença e Frequência
* **Registro de Presença:** Tela simples para marcar a presença dos alunos por data (Lista de Chamada).
* **Relatório de Presença:** Histórico de frequência integrado ao currículo do aluno.

### 🏆 Campeonatos
* **Gestão de Eventos:** Cadastro de novos campeonatos (Nome, Data, Taxa).
* **Inscrição de Judocas:** Inscrição de alunos em eventos, com controle de status de pagamento da taxa.
* **Registro de Colocação:** Campo para registrar o resultado final (ex: "1º Lugar", "Bronze") para fins de currículo.

### ⚙️ Segurança e UX
* **Módulo de Login:** Acesso restrito por usuário e senha criptografada (`password_hash`).
* **Sidebar:** Menu de navegação lateral consistente em todas as páginas.
* **Exportação CSV:** Botão para exportar a lista completa de alunos para planilhas (Excel/Google Sheets).

---

## 🚀 Como Iniciar o Projeto Localmente

Siga estes passos para configurar o ambiente de desenvolvimento usando o XAMPP.

### 1. Configuração do XAMPP e Diretório

1.  **Baixe e instale o XAMPP.**
2.  Inicie os módulos **Apache** e **MySQL** no **XAMPP Control Panel**.
3.  Clone ou mova todos os arquivos do projeto para o diretório de projetos do XAMPP:
    ```
    C:\xampp\htdocs\sistema-judo-academia
    ```

### 2. Configuração do Banco de Dados (MySQL)

1.  Acesse o **phpMyAdmin** no seu navegador: `http://localhost/phpmyadmin/`.
2.  Crie um novo banco de dados chamado **`academia_judo`**.

#### Criação das Tabelas

Execute os seguintes comandos SQL (na aba SQL do phpMyAdmin):

```sql
-- Tabela ALUNOS
CREATE TABLE alunos (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    data_nascimento DATE,
    peso DECIMAL(5, 2),
    kyu VARCHAR(50), 
    telefone VARCHAR(15),
    email VARCHAR(100) UNIQUE,
    valor_mensal DECIMAL(10, 2) DEFAULT 100.00,
    tipo_sanguineo VARCHAR(10),
    nome_pai VARCHAR(120),
    nome_mae VARCHAR(120),
    telefone_pai VARCHAR(20),
    telefone_mae VARCHAR(20),
    foto_path VARCHAR(255),
    termo_aceito TINYINT(1) DEFAULT 0,
    termo_data DATE,
    termo_nome VARCHAR(120)
);

-- Tabela USUARIOS (Para Login)
CREATE TABLE usuarios (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100)
);

-- Tabela PRESENCAS
CREATE TABLE presencas (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT(11) NOT NULL,
    data_aula DATE NOT NULL,
    status ENUM('presente', 'falta', 'justificada') DEFAULT 'presente',
    registrado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_aluno_data (aluno_id, data_aula), 
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
);

-- Tabela MENSALIDADES
CREATE TABLE mensalidades (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT(11) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    status ENUM('pago', 'pendente', 'atrasado') DEFAULT 'pendente',
    registrado_por VARCHAR(50),
    UNIQUE KEY uk_aluno_vencimento (aluno_id, data_vencimento), 
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
);

-- Tabela CAMPEONATOS
CREATE TABLE campeonatos (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    data_evento DATE NOT NULL,
    local VARCHAR(255),
    taxa DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('aberto', 'fechado', 'realizado') DEFAULT 'aberto'
);

-- Tabela INSCRICOES
CREATE TABLE inscricoes (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    campeonato_id INT(11) NOT NULL,
    aluno_id INT(11) NOT NULL,
    data_inscricao DATETIME DEFAULT CURRENT_TIMESTAMP,
    status_pagamento ENUM('pago', 'pendente') DEFAULT 'pendente',
    colocacao VARCHAR(50) DEFAULT NULL,
    UNIQUE KEY uk_inscricao_unica (campeonato_id, aluno_id),
    FOREIGN KEY (campeonato_id) REFERENCES campeonatos(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
);

-- Tabela DOCUMENTOS DO ALUNO
CREATE TABLE documentos_aluno (
    aluno_id INT(11) PRIMARY KEY,
    rg_ok TINYINT(1) DEFAULT 0,
    atestado_ok TINYINT(1) DEFAULT 0,
    autorizacao_ok TINYINT(1) DEFAULT 0,
    observacoes VARCHAR(255),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
);

-- Tabela ARQUIVOS DE DOCUMENTOS
CREATE TABLE documentos_arquivos (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT(11) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    arquivo_path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
);

-- Tabela GRADUACOES
CREATE TABLE graduacoes (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT(11) NOT NULL,
    faixa VARCHAR(50) NOT NULL,
    data_exame DATE NOT NULL,
    requisitos VARCHAR(255),
    observacoes VARCHAR(255),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
);
```

#### Atualizacao da tabela alunos (se voce ja tem o banco criado)

```sql
ALTER TABLE alunos
  ADD COLUMN tipo_sanguineo VARCHAR(10),
  ADD COLUMN nome_pai VARCHAR(120),
  ADD COLUMN nome_mae VARCHAR(120),
  ADD COLUMN telefone_pai VARCHAR(20),
  ADD COLUMN telefone_mae VARCHAR(20),
  ADD COLUMN foto_path VARCHAR(255),
  ADD COLUMN termo_aceito TINYINT(1) DEFAULT 0,
  ADD COLUMN termo_data DATE,
  ADD COLUMN termo_nome VARCHAR(120);
```

---

## Acesso (cadastro e login)

### Cadastro de usuario
Nao existe tela de cadastro. O usuario deve ser criado diretamente no banco.

1. Gere o hash da senha:
   - Abra `gerar_hash.php`
   - Troque a senha dentro do arquivo (ex.: `minha_senha`)
   - Acesse `http://localhost/sistema-judo-academia-main/gerar_hash.php`
   - Copie o hash exibido
2. Insira o usuario no banco (phpMyAdmin ou MySQL):
```sql
INSERT INTO usuarios (username, password_hash, nome)
VALUES ('meu_login', 'COLE_O_HASH_AQUI', 'Meu Nome');
```
3. Depois do cadastro, apague ou proteja o arquivo `gerar_hash.php`.

### Login
- Acesse `login.php`
- Informe o `username` e a senha cadastrados

### Admin padrao (opcional)
```sql
INSERT INTO usuarios (username, password_hash, nome)
VALUES ('admin', '$2y$10$G4jd5G5qDIpVim6EiXZN5e0sMOiQi7yGAYjgnrLpDdx4C7tOZQqCm', 'Administrador');
```
