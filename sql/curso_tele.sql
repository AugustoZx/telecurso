-- ATENÇÃO
-- NÃO SE ESQUEÇA DE CRIAR O BANCO DE DADOS ANTES DE IMPORTAR ESTE ARQUIVO SQL, POIS ELE NÃO CRIA O BANCO DE DADOS AUTOMATICAMENTE.
-- DB APENAS COM 1 USUÁRIO ADMINISTRADOR PARA TESTES (USUÁRIO = adm_teste / SENHA = 123, SERÁ NECESSÁRIO ALTERAR SENHA APÓS PRIMEIRO LOGIN)
-- ESTE ARQUIVO JÁ INCLUI O SCHEMA COMPLETO: alunos/aulas_concluidas/certificados/quiz_resultados (progresso e contas)
-- E cursos/aulas/quiz_perguntas/quiz_opcoes (conteúdo editável pelo painel admin_cursos.php). Uma importação só é suficiente.

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16/07/2026 às 22:10
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `curso_tele`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `alunos`
--

CREATE TABLE `alunos` (
  `id` int(11) NOT NULL,
  `user` varchar(25) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `trocar_senha` tinyint(1) NOT NULL DEFAULT 1,
  `nome` varchar(164) NOT NULL,
  `cpf` varchar(15) NOT NULL,
  `data_nasc` date NOT NULL,
  `adm` tinyint(1) NOT NULL DEFAULT 0,
  `aula` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `alunos`
--

INSERT INTO `alunos` (`id`, `user`, `pass`, `trocar_senha`, `nome`, `cpf`, `data_nasc`, `adm`, `aula`) VALUES
(1, 'adm_teste', '123', 1, 'DB Teste', '000.000.000.00', '2000-01-01', 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `aulas_concluidas`
--

CREATE TABLE `aulas_concluidas` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `curso_id` varchar(50) NOT NULL,
  `aula_num` int(11) NOT NULL,
  `data` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `certificados`
--

CREATE TABLE `certificados` (
  `id` int(11) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `curso_id` varchar(50) NOT NULL,
  `data_emissao` datetime DEFAULT current_timestamp(),
  `arquivo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `quiz_resultados`
--

CREATE TABLE `quiz_resultados` (
  `id` int(11) NOT NULL,
  `aluno_id` int(11) NOT NULL,
  `curso_id` varchar(50) NOT NULL,
  `acertos` int(11) NOT NULL,
  `total` int(11) NOT NULL,
  `percentual` decimal(5,2) NOT NULL,
  `aprovado` tinyint(1) NOT NULL DEFAULT 0,
  `data` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `alunos`
--
ALTER TABLE `alunos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user` (`user`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices de tabela `aulas_concluidas`
--
ALTER TABLE `aulas_concluidas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_aluno_curso_aula` (`aluno_id`,`curso_id`,`aula_num`);

--
-- Índices de tabela `certificados`
--
ALTER TABLE `certificados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `aluno_id` (`aluno_id`);

--
-- Índices de tabela `quiz_resultados`
--
ALTER TABLE `quiz_resultados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_curso` (`aluno_id`,`curso_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alunos`
--
ALTER TABLE `alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `aulas_concluidas`
--
ALTER TABLE `aulas_concluidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `certificados`
--
ALTER TABLE `certificados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `quiz_resultados`
--
ALTER TABLE `quiz_resultados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `aulas_concluidas`
--
ALTER TABLE `aulas_concluidas`
  ADD CONSTRAINT `fk_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `certificados`
--
ALTER TABLE `certificados`
  ADD CONSTRAINT `certificados_ibfk_1` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `quiz_resultados`
--
ALTER TABLE `quiz_resultados`
  ADD CONSTRAINT `fk_quiz_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE;
COMMIT;

-- --------------------------------------------------------
-- ============================================================
-- Schema v2: cursos/aulas/quiz editáveis pelo painel admin_cursos.php
-- ------------------------------------------------------------
-- Tabelas ADITIVAS: não alteram alunos, aulas_concluidas,
-- certificados nem quiz_resultados (definidas acima).
--
-- cursos.slug guarda o MESMO valor usado como curso_id em
-- aulas_concluidas/certificados/quiz_resultados (ex.: 'informatica',
-- 'atalhos', 'word', 'excel'), para que o progresso dos alunos
-- continue batendo sem precisar migrar essas tabelas.
-- ============================================================

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(50) NOT NULL,
  `nome` varchar(191) NOT NULL,
  `descricao` text NOT NULL,
  `imagem` varchar(255) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `nota_minima_quiz` tinyint(3) unsigned NOT NULL DEFAULT 70,
  `certifica_conclusao` tinyint(1) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cursos`
-- (os 4 módulos da trilha; as aulas de cada um são cadastradas pelo
-- painel admin_cursos.php depois da instalação)
--

INSERT INTO `cursos` (`id`, `slug`, `nome`, `descricao`, `imagem`, `ordem`, `nota_minima_quiz`, `certifica_conclusao`, `ativo`) VALUES
(1, 'informatica', 'Informática Básica', 'Conhecimentos fundamentais sobre o uso de computadores: navegação na internet, uso de programas e conceitos básicos de segurança.', 'img/computer.png', 1, 70, 0, 1),
(2, 'atalhos', 'Teclado e Mouse', 'Combinações de teclas que agilizam o trabalho no computador e funções do mouse que facilitam a navegação e a execução de tarefas.', 'img/keyboard.png', 2, 70, 0, 1),
(3, 'word', 'Microsoft Word', 'Editor de texto para criar, editar e formatar documentos de forma fácil e eficiente.', 'img/microsoft-word.png', 3, 70, 0, 1),
(4, 'excel', 'Microsoft Excel', 'Planilha eletrônica para organizar, calcular e analisar dados com tabelas, gráficos e fórmulas.', 'img/microsoft-exel.png', 4, 70, 1, 1);

ALTER TABLE `cursos` AUTO_INCREMENT = 5;

CREATE TABLE `aulas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `ordem` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `conteudo` mediumtext NOT NULL,
  `midia_tipo` enum('nenhuma','imagem','video') NOT NULL DEFAULT 'nenhuma',
  `midia_arquivo` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_curso_ordem` (`curso_id`,`ordem`),
  CONSTRAINT `fk_aula_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `quiz_perguntas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `enunciado` text NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pergunta_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `quiz_opcoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pergunta_id` int(11) NOT NULL,
  `texto` varchar(500) NOT NULL,
  `correta` tinyint(1) NOT NULL DEFAULT 0,
  `ordem` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_opcao_pergunta` FOREIGN KEY (`pergunta_id`) REFERENCES `quiz_perguntas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
