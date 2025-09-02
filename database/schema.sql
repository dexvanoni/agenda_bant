-- Criação do banco de dados
CREATE DATABASE IF NOT EXISTS agenda_bant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agenda_bant;

-- Tabela de configurações
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    antecedencia_horas INT NOT NULL DEFAULT 24,
    max_horas_consecutivas INT NOT NULL DEFAULT 4,
    email_comunicacao VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de espaços
CREATE TABLE IF NOT EXISTS espacos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    capacidade INT,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de agendamentos
CREATE TABLE IF NOT EXISTS agendamentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    espaco_id INT NOT NULL,
    nome_solicitante VARCHAR(100) NOT NULL,
    posto_graduacao VARCHAR(50) NOT NULL,
    setor VARCHAR(100) NOT NULL,
    ramal VARCHAR(20) NOT NULL,
    nome_evento VARCHAR(255) NOT NULL,
    categoria_evento VARCHAR(100) NOT NULL,
    quantidade_participantes INT NOT NULL,
    apoio_rancho BOOLEAN DEFAULT FALSE,
    apoio_ti BOOLEAN DEFAULT FALSE,
    observacoes TEXT,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    status ENUM('pendente', 'aprovado', 'cancelado') DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (espaco_id) REFERENCES espacos(id)
);

-- Inserir configurações padrão
INSERT INTO configuracoes (antecedencia_horas, max_horas_consecutivas, email_comunicacao)
VALUES (24, 4, 'comunicacao@fab.mil.br');

-- Inserir usuário administrador
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'usuario') DEFAULT 'usuario',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO usuarios (username, password, tipo)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'); 