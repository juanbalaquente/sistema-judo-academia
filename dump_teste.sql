-- Dump de teste para sistema judo academia
-- Inclui schema e dados de exemplo

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS documentos_arquivos;
DROP TABLE IF EXISTS documentos_aluno;
DROP TABLE IF EXISTS inscricoes;
DROP TABLE IF EXISTS graduacoes;
DROP TABLE IF EXISTS mensalidades;
DROP TABLE IF EXISTS presencas;
DROP TABLE IF EXISTS campeonatos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS alunos;

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

CREATE TABLE usuarios (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nome VARCHAR(100)
);

CREATE TABLE presencas (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT(11) NOT NULL,
    data_aula DATE NOT NULL,
    status ENUM('presente', 'falta', 'justificada') DEFAULT 'presente',
    registrado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_aluno_data (aluno_id, data_aula),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
);

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

CREATE TABLE campeonatos (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    data_evento DATE NOT NULL,
    local VARCHAR(255),
    taxa DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('aberto', 'fechado', 'realizado') DEFAULT 'aberto'
);

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

CREATE TABLE documentos_aluno (
    aluno_id INT(11) PRIMARY KEY,
    rg_ok TINYINT(1) DEFAULT 0,
    atestado_ok TINYINT(1) DEFAULT 0,
    autorizacao_ok TINYINT(1) DEFAULT 0,
    observacoes VARCHAR(255),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
);

CREATE TABLE documentos_arquivos (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT(11) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    arquivo_path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
);

CREATE TABLE graduacoes (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    aluno_id INT(11) NOT NULL,
    faixa VARCHAR(50) NOT NULL,
    data_exame DATE NOT NULL,
    requisitos VARCHAR(255),
    observacoes VARCHAR(255),
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
);

INSERT INTO alunos
    (id, nome, data_nascimento, peso, kyu, telefone, email, valor_mensal, tipo_sanguineo, nome_pai, nome_mae, telefone_pai, telefone_mae, foto_path, termo_aceito, termo_data, termo_nome)
VALUES
    (1, 'Ana Souza', '2008-03-12', 45.50, 'Amarela', '11990000001', 'ana.souza@example.com', 120.00, 'O+', 'Carlos Souza', 'Mariana Souza', '11980000001', '11970000001', NULL, 1, '2024-01-10', 'Ana Souza'),
    (2, 'Bruno Lima', '2006-07-22', 62.30, 'Laranja', '11990000002', 'bruno.lima@example.com', 120.00, 'A+', 'Paulo Lima', 'Renata Lima', '11980000002', '11970000002', NULL, 1, '2024-01-11', 'Bruno Lima'),
    (3, 'Camila Alves', '2010-11-05', 40.10, 'Branca', '11990000003', 'camila.alves@example.com', 100.00, 'B+', 'Sergio Alves', 'Lucia Alves', '11980000003', '11970000003', NULL, 0, NULL, NULL),
    (4, 'Daniel Rocha', '2005-02-18', 70.20, 'Verde', '11990000004', 'daniel.rocha@example.com', 130.00, 'O-', 'Marcos Rocha', 'Tania Rocha', '11980000004', '11970000004', NULL, 1, '2024-01-12', 'Daniel Rocha'),
    (5, 'Eduarda Nunes', '2009-09-30', 50.00, 'Azul', '11990000005', 'eduarda.nunes@example.com', 110.00, 'AB+', 'Rafael Nunes', 'Carla Nunes', '11980000005', '11970000005', NULL, 1, '2024-01-13', 'Eduarda Nunes'),
    (6, 'Felipe Araujo', '2007-05-14', 64.40, 'Roxa', '11990000006', 'felipe.araujo@example.com', 120.00, 'A-', 'Gustavo Araujo', 'Patricia Araujo', '11980000006', '11970000006', NULL, 1, '2024-01-14', 'Felipe Araujo'),
    (7, 'Gabriela Costa', '2011-12-01', 38.60, 'Branca', '11990000007', 'gabriela.costa@example.com', 100.00, 'O+', 'Roberto Costa', 'Fernanda Costa', '11980000007', '11970000007', NULL, 0, NULL, NULL),
    (8, 'Henrique Melo', '2004-08-19', 78.90, 'Marrom', '11990000008', 'henrique.melo@example.com', 140.00, 'B-', 'Ricardo Melo', 'Sonia Melo', '11980000008', '11970000008', NULL, 1, '2024-01-15', 'Henrique Melo'),
    (9, 'Isabela Freitas', '2008-04-25', 47.20, 'Amarela', '11990000009', 'isabela.freitas@example.com', 120.00, 'A+', 'Andre Freitas', 'Juliana Freitas', '11980000009', '11970000009', NULL, 1, '2024-01-16', 'Isabela Freitas'),
    (10, 'Joao Pereira', '2006-06-10', 66.10, 'Laranja', '11990000010', 'joao.pereira@example.com', 120.00, 'O+', 'Eduardo Pereira', 'Priscila Pereira', '11980000010', '11970000010', NULL, 1, '2024-01-17', 'Joao Pereira'),
    (11, 'Karen Barbosa', '2009-01-08', 49.90, 'Azul', '11990000011', 'karen.barbosa@example.com', 110.00, 'B+', 'Leandro Barbosa', 'Silvia Barbosa', '11980000011', '11970000011', NULL, 1, '2024-01-18', 'Karen Barbosa'),
    (12, 'Lucas Martins', '2007-10-03', 63.00, 'Roxa', '11990000012', 'lucas.martins@example.com', 120.00, 'A-', 'Vitor Martins', 'Cecilia Martins', '11980000012', '11970000012', NULL, 1, '2024-01-19', 'Lucas Martins'),
    (13, 'Mariana Ribeiro', '2010-02-27', 41.70, 'Branca', '11990000013', 'mariana.ribeiro@example.com', 100.00, 'O+', 'Fabio Ribeiro', 'Claudia Ribeiro', '11980000013', '11970000013', NULL, 0, NULL, NULL),
    (14, 'Nathan Silva', '2005-12-15', 72.30, 'Verde', '11990000014', 'nathan.silva@example.com', 130.00, 'AB-', 'Diego Silva', 'Patricia Silva', '11980000014', '11970000014', NULL, 1, '2024-01-20', 'Nathan Silva'),
    (15, 'Olivia Mendes', '2008-05-21', 46.80, 'Amarela', '11990000015', 'olivia.mendes@example.com', 120.00, 'A+', 'Rodrigo Mendes', 'Elaine Mendes', '11980000015', '11970000015', NULL, 1, '2024-01-21', 'Olivia Mendes'),
    (16, 'Pedro Carvalho', '2006-03-04', 67.80, 'Laranja', '11990000016', 'pedro.carvalho@example.com', 120.00, 'O-', 'Mauricio Carvalho', 'Vanessa Carvalho', '11980000016', '11970000016', NULL, 1, '2024-01-22', 'Pedro Carvalho'),
    (17, 'Rafaela Dias', '2009-07-09', 52.00, 'Azul', '11990000017', 'rafaela.dias@example.com', 110.00, 'B+', 'Hugo Dias', 'Monica Dias', '11980000017', '11970000017', NULL, 1, '2024-01-23', 'Rafaela Dias'),
    (18, 'Samuel Oliveira', '2004-11-28', 80.50, 'Preta', '11990000018', 'samuel.oliveira@example.com', 150.00, 'A+', 'Joao Oliveira', 'Marcia Oliveira', '11980000018', '11970000018', NULL, 1, '2024-01-24', 'Samuel Oliveira'),
    (19, 'Tatiana Castro', '2011-06-16', 39.40, 'Branca', '11990000019', 'tatiana.castro@example.com', 100.00, 'O+', 'Alex Castro', 'Renata Castro', '11980000019', '11970000019', NULL, 0, NULL, NULL),
    (20, 'Victor Santos', '2007-09-02', 65.70, 'Roxa', '11990000020', 'victor.santos@example.com', 120.00, 'AB+', 'Bruno Santos', 'Larissa Santos', '11980000020', '11970000020', NULL, 1, '2024-01-25', 'Victor Santos');

INSERT INTO usuarios (id, username, password_hash, nome) VALUES
    (1, 'admin', '$2y$10$examplehashadminxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Administrador'),
    (2, 'professor', '$2y$10$examplehashprofxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Professor'),
    (3, 'recepcao', '$2y$10$examplehashrecepxxxxxxxxxxxxxxxxxxxxxxxxxx', 'Recepcao');

INSERT INTO campeonatos (id, nome, data_evento, local, taxa, status) VALUES
    (1, 'Copa Regional de Judo', '2024-06-15', 'Ginasio Municipal', 50.00, 'realizado'),
    (2, 'Open Estadual', '2024-09-20', 'Centro Esportivo', 70.00, 'aberto'),
    (3, 'Festival Kids', '2024-11-05', 'Quadra do Colegio', 20.00, 'aberto');

INSERT INTO inscricoes (id, campeonato_id, aluno_id, data_inscricao, status_pagamento, colocacao) VALUES
    (1, 1, 4, '2024-06-01 10:00:00', 'pago', '2o'),
    (2, 1, 8, '2024-06-02 14:00:00', 'pago', '1o'),
    (3, 2, 10, '2024-09-01 09:30:00', 'pendente', NULL),
    (4, 2, 12, '2024-09-02 11:15:00', 'pago', NULL),
    (5, 3, 1, '2024-10-10 08:00:00', 'pendente', NULL),
    (6, 3, 3, '2024-10-10 08:10:00', 'pendente', NULL);

INSERT INTO mensalidades (id, aluno_id, valor, data_vencimento, data_pagamento, status, registrado_por) VALUES
    (1, 1, 120.00, '2024-03-05', '2024-03-02', 'pago', 'recepcao'),
    (2, 2, 120.00, '2024-03-05', NULL, 'pendente', 'recepcao'),
    (3, 3, 100.00, '2024-03-05', NULL, 'pendente', 'recepcao'),
    (4, 4, 130.00, '2024-03-05', '2024-03-06', 'pago', 'recepcao'),
    (5, 5, 110.00, '2024-03-05', NULL, 'atrasado', 'recepcao'),
    (6, 6, 120.00, '2024-03-05', '2024-03-04', 'pago', 'recepcao'),
    (7, 7, 100.00, '2024-03-05', NULL, 'pendente', 'recepcao'),
    (8, 8, 140.00, '2024-03-05', '2024-03-03', 'pago', 'recepcao'),
    (9, 9, 120.00, '2024-03-05', NULL, 'pendente', 'recepcao'),
    (10, 10, 120.00, '2024-03-05', '2024-03-05', 'pago', 'recepcao'),
    (11, 11, 110.00, '2024-03-05', NULL, 'pendente', 'recepcao'),
    (12, 12, 120.00, '2024-03-05', '2024-03-07', 'pago', 'recepcao'),
    (13, 13, 100.00, '2024-03-05', NULL, 'pendente', 'recepcao'),
    (14, 14, 130.00, '2024-03-05', '2024-03-08', 'pago', 'recepcao'),
    (15, 15, 120.00, '2024-03-05', NULL, 'pendente', 'recepcao'),
    (16, 16, 120.00, '2024-03-05', NULL, 'pendente', 'recepcao'),
    (17, 17, 110.00, '2024-03-05', '2024-03-09', 'pago', 'recepcao'),
    (18, 18, 150.00, '2024-03-05', '2024-03-10', 'pago', 'recepcao'),
    (19, 19, 100.00, '2024-03-05', NULL, 'pendente', 'recepcao'),
    (20, 20, 120.00, '2024-03-05', NULL, 'pendente', 'recepcao');

INSERT INTO presencas (id, aluno_id, data_aula, status, registrado_em) VALUES
    (1, 1, '2024-03-01', 'presente', '2024-03-01 19:00:00'),
    (2, 1, '2024-03-03', 'presente', '2024-03-03 19:00:00'),
    (3, 2, '2024-03-01', 'falta', '2024-03-01 19:00:00'),
    (4, 2, '2024-03-03', 'justificada', '2024-03-03 19:00:00'),
    (5, 4, '2024-03-01', 'presente', '2024-03-01 19:00:00'),
    (6, 4, '2024-03-03', 'presente', '2024-03-03 19:00:00'),
    (7, 8, '2024-03-01', 'presente', '2024-03-01 19:00:00'),
    (8, 8, '2024-03-03', 'presente', '2024-03-03 19:00:00'),
    (9, 10, '2024-03-01', 'falta', '2024-03-01 19:00:00'),
    (10, 10, '2024-03-03', 'presente', '2024-03-03 19:00:00');

INSERT INTO documentos_aluno (aluno_id, rg_ok, atestado_ok, autorizacao_ok, observacoes) VALUES
    (1, 1, 1, 1, 'OK'),
    (2, 1, 0, 1, 'Falta atestado'),
    (3, 0, 0, 0, 'Pendencias'),
    (4, 1, 1, 1, 'OK'),
    (5, 1, 1, 0, 'Falta autorizacao'),
    (6, 1, 1, 1, 'OK'),
    (7, 0, 0, 0, 'Pendencias'),
    (8, 1, 1, 1, 'OK'),
    (9, 1, 0, 1, 'Falta atestado'),
    (10, 1, 1, 1, 'OK'),
    (11, 1, 1, 1, 'OK'),
    (12, 1, 1, 1, 'OK'),
    (13, 0, 0, 0, 'Pendencias'),
    (14, 1, 1, 1, 'OK'),
    (15, 1, 1, 1, 'OK'),
    (16, 1, 1, 0, 'Falta autorizacao'),
    (17, 1, 1, 1, 'OK'),
    (18, 1, 1, 1, 'OK'),
    (19, 0, 0, 0, 'Pendencias'),
    (20, 1, 1, 1, 'OK');

INSERT INTO documentos_arquivos (id, aluno_id, tipo, arquivo_path, uploaded_at) VALUES
    (1, 1, 'rg', 'uploads/rg_1.pdf', '2024-01-10 09:00:00'),
    (2, 1, 'atestado', 'uploads/atestado_1.pdf', '2024-01-10 09:05:00'),
    (3, 2, 'rg', 'uploads/rg_2.pdf', '2024-01-11 09:00:00'),
    (4, 4, 'rg', 'uploads/rg_4.pdf', '2024-01-12 09:00:00'),
    (5, 4, 'atestado', 'uploads/atestado_4.pdf', '2024-01-12 09:05:00'),
    (6, 8, 'rg', 'uploads/rg_8.pdf', '2024-01-15 09:00:00');

INSERT INTO graduacoes (id, aluno_id, faixa, data_exame, requisitos, observacoes) VALUES
    (1, 4, 'Verde', '2023-12-10', 'Kata basico', 'Aprovado'),
    (2, 6, 'Roxa', '2023-11-20', 'Quedas e imobilizacoes', 'Aprovado'),
    (3, 8, 'Marrom', '2023-10-05', 'Randori', 'Aprovado'),
    (4, 18, 'Preta', '2022-08-15', 'Exame completo', 'Aprovado');

SET FOREIGN_KEY_CHECKS=1;
