-- ============================================================
--  Migração: tabela de progresso dos alunos
--  Banco: curso_tele
--  Rode este script uma vez no phpMyAdmin (ou no cliente MySQL).
--  NÃO altera suas tabelas existentes (alunos etc).
-- ============================================================

-- Guarda quais aulas cada aluno já concluiu.
-- O progresso de cada curso é calculado contando as linhas aqui.
-- "curso_id" é o identificador de texto definido em cursos.php
-- (ex: 'informatica', 'atalhos', 'word', 'excel').

CREATE TABLE IF NOT EXISTS aulas_concluidas (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id  INT NOT NULL,
    curso_id  VARCHAR(50) NOT NULL,
    aula_num  INT NOT NULL,
    data      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- impede duplicar a mesma aula concluída pelo mesmo aluno
    UNIQUE KEY uniq_aluno_curso_aula (aluno_id, curso_id, aula_num),

    -- se o aluno for apagado, o progresso dele também some
    CONSTRAINT fk_aluno
        FOREIGN KEY (aluno_id) REFERENCES alunos(id)
        ON DELETE CASCADE
);
