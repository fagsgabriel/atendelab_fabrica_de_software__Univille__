-- AtendeLab — schema e dados iniciais
-- Importe este arquivo no phpMyAdmin ou via CLI após criar o banco.

CREATE DATABASE IF NOT EXISTS atendelab
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE atendelab;

CREATE TABLE usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  perfil ENUM('admin', 'aluno', 'atendente') NOT NULL DEFAULT 'atendente',
  status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pessoas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  documento VARCHAR(30) NOT NULL UNIQUE,
  telefone VARCHAR(20) NULL,
  curso VARCHAR(120) NULL,
  periodo VARCHAR(20) NULL,
  status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo'
) ENGINE=InnoDB;

CREATE TABLE tipos_atendimentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  descricao TEXT NULL,
  status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo'
) ENGINE=InnoDB;

CREATE TABLE atendimentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pessoa_id INT UNSIGNED NOT NULL,
  tipo_atendimento_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  descricao TEXT NOT NULL,
  observacao TEXT NULL,
  data_atendimento DATE NOT NULL,
  hora_atendimento TIME NOT NULL,
  status ENUM('aberto', 'em_andamento', 'concluido') NOT NULL DEFAULT 'aberto',
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_atend_pessoa FOREIGN KEY (pessoa_id) REFERENCES pessoas (id),
  CONSTRAINT fk_atend_tipo FOREIGN KEY (tipo_atendimento_id) REFERENCES tipos_atendimentos (id),
  CONSTRAINT fk_atend_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB;

-- Usuário padrão: admin@atendelab.com / admin123
INSERT INTO usuarios (nome, email, senha, perfil, status) VALUES
('Administrador', 'admin@atendelab.com', '$2y$10$0OzGNJ20sL.FDVmwi0txq.LQL788u16JwZisG6ZdPj4rkiUlBuXoG', 'admin', 'ativo');

INSERT INTO tipos_atendimentos (nome, descricao, status) VALUES
('Declaração', 'Emissão de declarações acadêmicas', 'ativo'),
('Matrícula', 'Dúvidas e ajustes de matrícula', 'ativo'),
('Documentação', 'Entrega e conferência de documentos', 'ativo');
