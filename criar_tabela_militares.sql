CREATE TABLE militares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    posto_graduacao VARCHAR(50) NOT NULL,
    nome_guerra VARCHAR(100) NOT NULL,
    esquadrao_setor VARCHAR(100) NOT NULL,
    email_fab VARCHAR(100) NOT NULL,
    ramal VARCHAR(20) NOT NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
); 