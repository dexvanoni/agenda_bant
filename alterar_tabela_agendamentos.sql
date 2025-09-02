ALTER TABLE agendamentos
ADD COLUMN militar_id INT,
ADD FOREIGN KEY (militar_id) REFERENCES militares(id); 