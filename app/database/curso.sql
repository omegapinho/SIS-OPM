CREATE TABLE turma (
    id INTEGER PRIMARY KEY NOT NULL,
    nome text,
    dt_inicio date,
    dt_conclusao date,
    carga_horaria int,
    habilita text,
    turno text,
    sigla text,
    cidade_id int NOT NULL,
    concurso_id int NOT NULL);

CREATE TABLE concurso (
    id INTEGER PRIMARY KEY NOT NULL,
    nome_certame text,
    doc_legal text,
    data_abertura date,
    data_conclusao date,
    instituicao text,
    carga_horaria int,
    data_apresentacao date,
    data_encerramento date,
    sigla text);

CREATE TABLE aluno (
    id INTEGER PRIMARY KEY NOT NULL,
    nome text,
    quadro text,
    posto text,
    nome_guerra text,
    rg text,
    cpf text,
    opm_origem text,
    dt_nasc date,
    dt_inclusao date,
    logradouro text,
    bairro text,
    sexo text,
    cnh text,
    categoria_cnh text,
    validade_cnh date,
    titulo_eleitor text,
    zona_eleitoral int,
    secao_eleitoral int,
    cidade_eleitoral text,
    banco text,
    agencia text,
    conta text,
    tipo_conta text,
    cidade_banco text,
    pai text,
    mae text,
    consorte text,
    cidade_id int NOT NULL);

CREATE TABLE disciplina (
    id INTEGER PRIMARY KEY NOT NULL,
    nome text,
    area_conhecimento text,
    carga_horaria int,
    sigla text);

CREATE TABLE professor (
    id INTEGER PRIMARY KEY NOT NULL,
    nome text,
    quadro text,
    posto text,
    rg text,
    cpf text,
    opm_origem text,
    dt_nasc date,
    dt_inclusao date,
    logradouro text,
    bairro text,
    sexo text,
    cnh text,
    categoria_cnh text,
    validade_cnh date,
    nome_guerra text,
    titulo_eleitor text,
    zona_eleitoral int,
    secao_eleitoral int,
    cidade_eleitoral text,
    banco text,
    agencia text,
    conta text,
    tipo_conta text,
    cidade_banco text,
    pai text,
    mae text,
    consorte text,
    cidade_id int NOT NULL);

CREATE TABLE documento (
    id INTEGER PRIMARY KEY NOT NULL,
    tipo text,
    dt_publica date,
    resumo text,
    validade date,
    privado boolean,
    arquivo bytea);

CREATE TABLE uf (
    id INTEGER PRIMARY KEY NOT NULL,
    nome text,
    regiao text,
    sigla text);

CREATE TABLE cidade (
    id INTEGER PRIMARY KEY NOT NULL,
    nome text,
    habitantes int,
    prefeito text,
    tamanho int,
    uf_id int NOT NULL);

CREATE TABLE disciplinaprofessor (
    id INTEGER PRIMARY KEY NOT NULL,
    disciplina_id int NOT NULL,
    professor_id int NOT NULL);

CREATE TABLE professordocumento (
    id INTEGER PRIMARY KEY NOT NULL,
    professor_id int NOT NULL,
    documento_id int NOT NULL);

CREATE TABLE alunodocumento (
    id INTEGER PRIMARY KEY NOT NULL,
    aluno_id int NOT NULL,
    documento_id int NOT NULL);

CREATE TABLE concursodocumento (
    id INTEGER PRIMARY KEY NOT NULL,
    concurso_id int NOT NULL,
    documento_id int NOT NULL);

CREATE TABLE disciplinadocumento (
    id INTEGER PRIMARY KEY NOT NULL,
    disciplina_id int NOT NULL,
    documento_id int NOT NULL);

CREATE TABLE turmadocumento (
    id INTEGER PRIMARY KEY NOT NULL,
    turma_id int NOT NULL,
    documento_id int NOT NULL);

CREATE TABLE turmaaluno (
    id INTEGER PRIMARY KEY NOT NULL,
    turma_id int NOT NULL,
    aluno_id int NOT NULL);

CREATE TABLE disciplinaturma (
    id INTEGER PRIMARY KEY NOT NULL,
    disciplina_id int NOT NULL,
    turma_id int NOT NULL);

    ALTER TABLE aluno ADD CONSTRAINT aluno_cidade_id_fk FOREIGN KEY (cidade_id) REFERENCES cidade (id);
    ALTER TABLE professor ADD CONSTRAINT professor_cidade_id_fk FOREIGN KEY (cidade_id) REFERENCES cidade (id);
    ALTER TABLE turma ADD CONSTRAINT turma_cidade_id_fk FOREIGN KEY (cidade_id) REFERENCES cidade (id);
    ALTER TABLE turma ADD CONSTRAINT turma_concurso_id_fk FOREIGN KEY (concurso_id) REFERENCES concurso (id);
    ALTER TABLE cidade ADD CONSTRAINT cidade_uf_id_fk FOREIGN KEY (uf_id) REFERENCES uf (id);
    ALTER TABLE disciplinaprofessor ADD CONSTRAINT disciplinaprofessor_disciplina_id_fk FOREIGN KEY (disciplina_id) REFERENCES disciplina (id);
    ALTER TABLE disciplinaprofessor ADD CONSTRAINT disciplinaprofessor_professor_id_fk FOREIGN KEY (professor_id) REFERENCES professor (id);
    ALTER TABLE professordocumento ADD CONSTRAINT professordocumento_professor_id_fk FOREIGN KEY (professor_id) REFERENCES professor (id);
    ALTER TABLE professordocumento ADD CONSTRAINT professordocumento_documento_id_fk FOREIGN KEY (documento_id) REFERENCES documento (id);
    ALTER TABLE alunodocumento ADD CONSTRAINT alunodocumento_aluno_id_fk FOREIGN KEY (aluno_id) REFERENCES aluno (id);
    ALTER TABLE alunodocumento ADD CONSTRAINT alunodocumento_documento_id_fk FOREIGN KEY (documento_id) REFERENCES documento (id);
    ALTER TABLE concursodocumento ADD CONSTRAINT concursodocumento_concurso_id_fk FOREIGN KEY (concurso_id) REFERENCES concurso (id);
    ALTER TABLE concursodocumento ADD CONSTRAINT concursodocumento_documento_id_fk FOREIGN KEY (documento_id) REFERENCES documento (id);
    ALTER TABLE disciplinadocumento ADD CONSTRAINT disciplinadocumento_disciplina_id_fk FOREIGN KEY (disciplina_id) REFERENCES disciplina (id);
    ALTER TABLE disciplinadocumento ADD CONSTRAINT disciplinadocumento_documento_id_fk FOREIGN KEY (documento_id) REFERENCES documento (id);
    ALTER TABLE turmadocumento ADD CONSTRAINT turmadocumento_turma_id_fk FOREIGN KEY (turma_id) REFERENCES turma (id);
    ALTER TABLE turmadocumento ADD CONSTRAINT turmadocumento_documento_id_fk FOREIGN KEY (documento_id) REFERENCES documento (id);
    ALTER TABLE turmaaluno ADD CONSTRAINT turmaaluno_turma_id_fk FOREIGN KEY (turma_id) REFERENCES turma (id);
    ALTER TABLE turmaaluno ADD CONSTRAINT turmaaluno_aluno_id_fk FOREIGN KEY (aluno_id) REFERENCES aluno (id);
    ALTER TABLE disciplinaturma ADD CONSTRAINT disciplinaturma_disciplina_id_fk FOREIGN KEY (disciplina_id) REFERENCES disciplina (id);
    ALTER TABLE disciplinaturma ADD CONSTRAINT disciplinaturma_turma_id_fk FOREIGN KEY (turma_id) REFERENCES turma (id);