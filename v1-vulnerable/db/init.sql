-- ============================================
-- Base de datos: billetera_v1 (VULNERABLE)
-- ============================================

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,          -- texto plano a proposito (VULN #2/#12)
    rol ENUM('user','admin') DEFAULT 'user',
    foto_perfil VARCHAR(255) DEFAULT NULL,
    token_recuperacion VARCHAR(255) DEFAULT NULL,
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
    nota TEXT,                                -- vector XSS almacenado (VULN #8)
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('completada','pendiente','fallida') DEFAULT 'completada'
);

CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    accion VARCHAR(100),
    ip VARCHAR(45),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP
);
-- Nota: esta tabla existe pero la app V1 NUNCA la usa (Logging & Monitoring Failures)

-- ============================================
-- Datos de prueba
-- ============================================

INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@billetera.com', 'Admin123!', 'admin'),
('Jack Torres', 'jack@billetera.com', 'Jack2024!', 'user'),
('Maria Lopez', 'maria@billetera.com', 'Maria2024!', 'user');

INSERT INTO cuentas (usuario_id, saldo, numero_cuenta) VALUES
(1, 50000.00, '0000000001'),
(2, 1200.50, '0000000002'),
(3, 3400.75, '0000000003');

INSERT INTO transacciones (cuenta_origen_id, cuenta_destino_id, monto, nota, estado) VALUES
(2, 3, 100.00, 'Pago de almuerzo', 'completada'),
(3, 2, 50.00, 'Devolucion', 'completada');
