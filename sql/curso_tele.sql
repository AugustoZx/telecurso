-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 06/07/2026 às 00:39
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
(1, 'adm_augusto', '$2y$10$iz.lEraW7OjwGaQ0dYZiU./907nG7kFo/EJwtNoE6GBvKjEJA.oTC', 'Augusto Oliveira de Araújo', '111111111111', '2004-05-27', 1, 1),
(2, 'laricalari', '$2y$10$0rPzE8eQ85Hsw4raDJGpuegW9euZIAzVWOlVoWV4rH0G39XRXSqLO', 'Lara Vitória Martins Dias', '', '2005-08-05', 0, 1);

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

--
-- Despejando dados para a tabela `aulas_concluidas`
--

INSERT INTO `aulas_concluidas` (`id`, `aluno_id`, `curso_id`, `aula_num`, `data`) VALUES
(1, 1, 'informatica', 1, '2026-07-04 19:47:43'),
(2, 1, 'informatica', 4, '2026-07-04 19:47:51'),
(3, 1, 'informatica', 2, '2026-07-04 19:48:03'),
(4, 1, 'informatica', 3, '2026-07-04 19:48:03'),
(10, 2, 'informatica', 1, '2026-07-04 19:53:12'),
(11, 2, 'informatica', 2, '2026-07-04 19:53:13'),
(12, 2, 'informatica', 3, '2026-07-04 19:53:13'),
(13, 2, 'informatica', 4, '2026-07-04 19:53:14'),
(14, 2, 'atalhos', 1, '2026-07-04 19:53:15'),
(15, 2, 'atalhos', 2, '2026-07-04 19:53:16'),
(16, 2, 'atalhos', 3, '2026-07-04 19:53:16'),
(17, 2, 'word', 1, '2026-07-04 19:53:18'),
(18, 2, 'word', 2, '2026-07-04 19:53:18'),
(19, 2, 'word', 3, '2026-07-04 19:53:18'),
(20, 2, 'excel', 1, '2026-07-04 19:53:22'),
(21, 2, 'excel', 2, '2026-07-04 19:53:22'),
(22, 2, 'excel', 3, '2026-07-04 19:53:23'),
(23, 1, 'atalhos', 1, '2026-07-04 20:40:05'),
(24, 1, 'atalhos', 2, '2026-07-04 20:40:11'),
(25, 1, 'atalhos', 3, '2026-07-04 20:40:15'),
(30, 1, 'word', 1, '2026-07-04 20:41:24'),
(31, 1, 'word', 2, '2026-07-04 20:41:25'),
(32, 1, 'word', 3, '2026-07-04 20:41:26'),
(33, 1, 'excel', 1, '2026-07-04 20:41:28'),
(34, 1, 'excel', 2, '2026-07-04 20:41:29'),
(35, 1, 'excel', 3, '2026-07-04 20:41:30');

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
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alunos`
--
ALTER TABLE `alunos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `aulas_concluidas`
--
ALTER TABLE `aulas_concluidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `aulas_concluidas`
--
ALTER TABLE `aulas_concluidas`
  ADD CONSTRAINT `fk_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
