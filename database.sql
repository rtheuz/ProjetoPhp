
CREATE TABLE motoristas (
    id_motorista INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    cnh VARCHAR(20) UNIQUE NOT NULL,
    validade_cnh DATE NOT NULL,
    status_motorista ENUM('disponivel', 'em_rota', 'folga', 'ferias') DEFAULT 'disponivel'
);

CREATE TABLE veiculos (
    id_veiculo INT PRIMARY KEY AUTO_INCREMENT,
    placa VARCHAR(10) UNIQUE NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    capacidade_carga DECIMAL(10,2), -- em kg ou toneladas
    km_atual DECIMAL(10,2) NOT NULL DEFAULT 0,
    data_ultima_manutencao DATE,
    status_veiculo ENUM('disponivel', 'em_rota', 'manutencao') DEFAULT 'disponivel'
);

CREATE TABLE rotas (
    id_rota INT PRIMARY KEY AUTO_INCREMENT,
    origem VARCHAR(150) NOT NULL,
    destino VARCHAR(150) NOT NULL,
    distancia_estimada_km DECIMAL(10,2),
    tempo_estimado_horas DECIMAL(5,2)
);

CREATE TABLE entregas (
    id_entrega INT PRIMARY KEY AUTO_INCREMENT,
    codigo_rastreio VARCHAR(50) UNIQUE NOT NULL,
    cliente_nome VARCHAR(100) NOT NULL,
    endereco_destino TEXT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_entrega ENUM('pendente', 'em_rota', 'entregue', 'cancelado') DEFAULT 'pendente',
    

    id_motorista INT,
    id_veiculo INT,
    id_rota INT,
    
    FOREIGN KEY (id_motorista) REFERENCES motoristas(id_motorista),
    FOREIGN KEY (id_veiculo) REFERENCES veiculos(id_veiculo),
    FOREIGN KEY (id_rota) REFERENCES rotas(id_rota)
);


CREATE TABLE ocorrencias (
    id_ocorrencia INT PRIMARY KEY AUTO_INCREMENT,
    id_entrega INT NOT NULL,
    data_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    tipo_ocorrencia VARCHAR(50) NOT NULL, -- Ex: Atraso, Acidente, Endereço não encontrado
    descricao TEXT,
    
    FOREIGN KEY (id_entrega) REFERENCES entregas(id_entrega)
);

CREATE TABLE manutencoes (
    id_manutencao INT PRIMARY KEY AUTO_INCREMENT,
    id_veiculo INT NOT NULL,
    data_manutencao DATE NOT NULL,
    tipo_manutencao ENUM('preventiva', 'corretiva') NOT NULL,
    km_na_manutencao DECIMAL(10,2) NOT NULL,
    descricao TEXT,
    custo DECIMAL(10,2),
    
    FOREIGN KEY (id_veiculo) REFERENCES veiculos(id_veiculo)
);