-- ============================================
-- Base de datos: billetera_v2 (REMEDIADA)
-- ============================================

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,          -- SEC-FIX: hash bcrypt
    rol ENUM('user','admin') DEFAULT 'user',
    foto_perfil VARCHAR(255) DEFAULT NULL,
    token_recuperacion VARCHAR(255) DEFAULT NULL,
    token_expira DATETIME DEFAULT NULL,       -- SEC-FIX #9: expiracion real del token
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cuentas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    saldo DECIMAL(10,2) DEFAULT 0.00,
    numero_cuenta VARCHAR(20) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE IF NOT EXISTS transacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cuenta_origen_id INT NOT NULL,
    cuenta_destino_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    nota TEXT,                                -- SEC-FIX #8: se sanea al MOSTRAR, no al guardar
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('completada','pendiente','fallida') DEFAULT 'completada',
    FOREIGN KEY (cuenta_origen_id) REFERENCES cuentas(id),
    FOREIGN KEY (cuenta_destino_id) REFERENCES cuentas(id)
);

CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    accion VARCHAR(150),
    ip VARCHAR(45),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
-- SEC-FIX: esta tabla SI se usa activamente en V2 (login, transferencias, accesos admin)

-- ============================================
-- Datos de prueba (passwords hasheados con bcrypt, ya funcionales)
-- ============================================

-- admin@billetera.com   / password real: Admin123!
-- jack@billetera.com    / password real: Jack2024!
-- maria@billetera.com   / password real: Maria2024!
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@billetera.com', '$2b$12$BlJvtlpBZLv3iBn6ST7gcutkpLkuqtEJWDjjJ7U/sUZq2.OIgB0Ju', 'admin'),
('Jack Torres', 'jack@billetera.com', '$2b$12$n6F0W2GI3mUIjuKXhnNqhOxb9dK79joZbq1f7xrMJAhpmjPhKPk8.', 'user'),
('Maria Lopez', 'maria@billetera.com', '$2b$12$Zu8THz.dKJivpan5MC3UfeW8EpuTFdyfZ32QajwwCzSHyoekNVSiK', 'user');

INSERT INTO cuentas (usuario_id, saldo, numero_cuenta) VALUES
(1, 50000.00, '0000000001'),
(2, 1200.50, '0000000002'),
(3, 3400.75, '0000000003');
