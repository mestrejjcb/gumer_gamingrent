-- ==========================================
-- GamingRent V3 · Migración de autenticación
-- Crea tabla usuarios (login real) y permisos básicos
-- ==========================================

USE alquiler_gaming;

CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(60) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  rol ENUM('GERENCIA','MOSTRADOR','TECNICO') NOT NULL DEFAULT 'MOSTRADOR',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  fecha_alta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Nota:
-- El usuario admin se crea desde /install.php (usa password_hash de PHP).
-- Si ya existe un admin, no lo duplicará.
