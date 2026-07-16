-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 16/07/2026 às 14:42
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
  `nome` varchar(164) NOT NULL,
  `cpf` varchar(15) NOT NULL,
  `data_nasc` date NOT NULL,
  `adm` tinyint(1) NOT NULL DEFAULT 0,
  `aula` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `alunos`
--

INSERT INTO `alunos` (`id`, `user`, `pass`, `nome`, `cpf`, `data_nasc`, `adm`, `aula`) VALUES
(1, 'adm_teste', '$2y$10$7CNYdM2D/VH9G7fF/rtft.6dWAb6ZHtweynvJYjIJHDjtXgM9aJVi', 'Teste DB', '000.000.000-00', '2000-01-01', 1, 1),
(2, 'joao.silva', '123', 'João Silva', '123.456.789-01', '1998-03-15', 0, 1),
(3, 'maria.souza', '123', 'Maria Souza', '234.567.890-12', '1995-07-22', 0, 1),
(4, 'pedro.lima', '123', 'Pedro Lima', '345.678.901-23', '2000-01-10', 0, 1),
(5, 'ana.costa', '123', 'Ana Costa', '456.789.012-34', '1997-11-05', 0, 1),
(6, 'lucas.oliveira', '123', 'Lucas Oliveira', '567.890.123-45', '1999-06-18', 0, 1),
(7, 'juliana.rocha', '123', 'Juliana Rocha', '678.901.234-56', '1996-04-27', 0, 1),
(8, 'rafael.almeida', '123', 'Rafael Almeida', '789.012.345-67', '1994-09-12', 0, 1),
(9, 'camila.ribeiro', '123', 'Camila Ribeiro', '890.123.456-78', '2001-02-08', 0, 1),
(10, 'bruno.martins', '123', 'Bruno Martins', '901.234.567-89', '1993-12-30', 0, 1),
(11, 'fernanda.gomes', '123', 'Fernanda Gomes', '012.345.678-90', '1998-08-14', 0, 1),
(12, 'thiago.barros', '123', 'Thiago Barros', '135.246.357-91', '1997-05-03', 0, 1),
(13, 'patricia.melo', '123', 'Patrícia Melo', '246.357.468-02', '1992-10-19', 0, 1),
(14, 'gabriel.santos', '123', 'Gabriel Santos', '357.468.579-13', '2002-01-25', 0, 1),
(15, 'beatriz.ferreira', '123', 'Beatriz Ferreira', '468.579.680-24', '1999-07-09', 0, 1),
(16, 'felipe.cardoso', '123', 'Felipe Cardoso', '579.680.791-35', '1996-03-21', 0, 1),
(17, 'larissa.teixeira', '123', 'Larissa Teixeira', '680.791.802-46', '1995-12-11', 0, 1),
(18, 'daniel.pereira', '123', 'Daniel Pereira', '791.802.913-57', '1994-04-06', 0, 1),
(19, 'renata.nunes', '123', 'Renata Nunes', '802.913.024-68', '2000-09-28', 0, 1),
(20, 'vinicius.araujo', '123', 'Vinícius Araújo', '913.024.135-79', '1998-02-17', 0, 1),
(21, 'carolina.moraes', '123', 'Carolina Moraes', '024.135.246-80', '1997-06-01', 0, 1);

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
  ADD UNIQUE KEY `user` (`user`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
