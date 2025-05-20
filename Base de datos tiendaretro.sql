CREATE DATABASE tiendaretro;

USE tiendaretro;

CREATE TABLE productos (
    referencia INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL
);

CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    product_id INT NOT NULL,
    fecha_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES productos(referencia)
);

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    usuario VARCHAR(50) NOT NULL UNIQUE, 
    contrasena VARCHAR(255) NOT NULL 
);

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL, 
    nombre VARCHAR(100) NOT NULL, 
    apellidos VARCHAR(100) NOT NULL, 
    correo VARCHAR(100) NOT NULL, 
    fecha_nacimiento DATE NOT NULL, 
    genero CHAR(1) NOT NULL, 
    FOREIGN KEY (usuario) REFERENCES usuarios(usuario) 
);



INSERT INTO productos (nombre, precio) VALUES
('NES', 100.00),
('SNES', 120.00),
('Mega Drive', 110.00),
('PS1', 150.00),
('Nintendo 64', 130.00),
('Super Mario Bros 3', 50.00),
('The Legend of Zelda: A Link to the Past', 60.00),
('Sonic the Hedgehog 2', 40.00),
('Final Fantasy VII', 70.00),
('Super Smash Bros 64', 80.00);

ALTER TABLE clientes
DROP FOREIGN KEY clientes_ibfk_1;

ALTER TABLE clientes
ADD CONSTRAINT clientes_ibfk_1
FOREIGN KEY (usuario) REFERENCES usuarios(usuario)
ON DELETE CASCADE;

ALTER TABLE compras
DROP FOREIGN KEY compras_ibfk_1;

ALTER TABLE compras
ADD CONSTRAINT compras_ibfk_1
FOREIGN KEY (user_id) REFERENCES usuarios(usuario)
ON DELETE CASCADE;

ALTER TABLE compras
DROP FOREIGN KEY compras_ibfk_2;

ALTER TABLE compras
ADD CONSTRAINT compras_ibfk_2
FOREIGN KEY (product_id) REFERENCES productos(referencia)
ON DELETE CASCADE;
