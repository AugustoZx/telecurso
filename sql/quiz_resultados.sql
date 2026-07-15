-- ============================================================
--  Tabela para guardar os resultados dos quizzes
--  Rode este SQL uma vez no phpMyAdmin (banco: curso_tele)
--
--  Guardamos TODAS as tentativas (histórico). O desbloqueio do
--  próximo curso usa a MELHOR nota que o aluno já tirou no quiz.
-- ============================================================

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

ALTER TABLE `quiz_resultados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aluno_curso` (`aluno_id`,`curso_id`);

ALTER TABLE `quiz_resultados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `quiz_resultados`
  ADD CONSTRAINT `fk_quiz_aluno` FOREIGN KEY (`aluno_id`) REFERENCES `alunos` (`id`) ON DELETE CASCADE;
