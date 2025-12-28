-- ============================================================
-- TIENDA DE ALQUILER DE VIDEOJUEGOS Y CONSOLAS (MySQL 8)
-- Script completo: tablas + datos de ejemplo + vistas + SPs
-- ============================================================

DROP DATABASE IF EXISTS alquiler_gaming;
CREATE DATABASE alquiler_gaming CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE alquiler_gaming;

-- -------------------------
-- 1) TABLAS PRINCIPALES
-- -------------------------

CREATE TABLE clientes (
  id_cliente INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(80) NOT NULL,
  email       VARCHAR(120) UNIQUE,
  telefono    VARCHAR(20),
  fecha_alta  DATE NOT NULL,
  puntos_fidelidad INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE empleados (
  id_empleado INT AUTO_INCREMENT PRIMARY KEY,
  nombre      VARCHAR(80) NOT NULL,
  rol         ENUM('GERENCIA','MOSTRADOR','TECNICO') NOT NULL DEFAULT 'MOSTRADOR',
  activo      TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE productos (
  id_producto INT AUTO_INCREMENT PRIMARY KEY,
  tipo        ENUM('JUEGO','CONSOLA') NOT NULL,
  nombre      VARCHAR(120) NOT NULL,
  plataforma  ENUM('PS5','PS4','XBOX_SERIES','XBOX_ONE','SWITCH','PC') NOT NULL,
  genero      VARCHAR(40) NULL,
  pegi        TINYINT NULL,
  precio_dia  DECIMAL(6,2) NOT NULL,
  stock_total INT NOT NULL,
  stock_disponible INT NOT NULL,
  activo      TINYINT NOT NULL DEFAULT 1,
  CONSTRAINT ck_stock CHECK (stock_total >= 0 AND stock_disponible >= 0 AND stock_disponible <= stock_total)
) ENGINE=InnoDB;

CREATE TABLE alquileres (
  id_alquiler INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente  INT NOT NULL,
  id_empleado INT NOT NULL,
  fecha_inicio DATE NOT NULL,
  fecha_fin_prevista DATE NOT NULL,
  fecha_fin_real DATE NULL,
  estado ENUM('ABIERTO','CERRADO') NOT NULL DEFAULT 'ABIERTO',
  deposito DECIMAL(7,2) NOT NULL DEFAULT 0,
  total_base DECIMAL(9,2) NOT NULL DEFAULT 0,
  recargo    DECIMAL(9,2) NOT NULL DEFAULT 0,
  total_final DECIMAL(9,2) NOT NULL DEFAULT 0,
  observaciones VARCHAR(255),
  CONSTRAINT fk_alq_cliente  FOREIGN KEY (id_cliente)  REFERENCES clientes(id_cliente),
  CONSTRAINT fk_alq_empleado FOREIGN KEY (id_empleado) REFERENCES empleados(id_empleado),
  CONSTRAINT ck_fechas CHECK (fecha_fin_prevista >= fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE alquiler_detalle (
  id_alquiler INT NOT NULL,
  id_producto INT NOT NULL,
  cantidad INT NOT NULL,
  precio_dia_aplicado DECIMAL(6,2) NOT NULL,
  descuento_pct DECIMAL(5,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_alquiler, id_producto),
  CONSTRAINT fk_det_alq FOREIGN KEY (id_alquiler) REFERENCES alquileres(id_alquiler) ON DELETE CASCADE,
  CONSTRAINT fk_det_prod FOREIGN KEY (id_producto) REFERENCES productos(id_producto),
  CONSTRAINT ck_detalle CHECK (cantidad > 0 AND descuento_pct >= 0 AND descuento_pct <= 100)
) ENGINE=InnoDB;

CREATE TABLE pagos (
  id_pago INT AUTO_INCREMENT PRIMARY KEY,
  id_alquiler INT NOT NULL,
  fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  importe DECIMAL(9,2) NOT NULL,
  metodo ENUM('EFECTIVO','TARJETA','BIZUM') NOT NULL,
  concepto VARCHAR(120) NOT NULL DEFAULT 'Pago alquiler',
  CONSTRAINT fk_pago_alq FOREIGN KEY (id_alquiler) REFERENCES alquileres(id_alquiler) ON DELETE CASCADE,
  CONSTRAINT ck_pago CHECK (importe > 0)
) ENGINE=InnoDB;

CREATE INDEX idx_alq_estado ON alquileres(estado);
CREATE INDEX idx_prod_tipo_plat ON productos(tipo, plataforma);

-- -------------------------
-- 2) DATOS DE EJEMPLO
-- -------------------------

INSERT INTO empleados(nombre, rol) VALUES
('Laura Sanz','GERENCIA'),
('Miguel Torres','MOSTRADOR'),
('Aina Vidal','MOSTRADOR'),
('Sergio Peña','TECNICO'),
('Noa Campos','MOSTRADOR');

INSERT INTO clientes(nombre, email, telefono, fecha_alta, puntos_fidelidad) VALUES
('Carlos Núñez','carlos.nunez@mail.com','600111222','2025-09-10',40),
('María Pérez','maria.perez@mail.com','600222333','2025-09-18',15),
('Javier López','jlopez@mail.com','600333444','2025-10-02',0),
('Lucía Romero','lucia.romero@mail.com','600444555','2025-10-10',25),
('Álvaro Gil','alvaro.gil@mail.com','600555666','2025-10-25',5),
('Sofía Martín','sofia.martin@mail.com','600666777','2025-11-04',10),
('Pablo Ruiz','pablo.ruiz@mail.com','600777888','2025-11-20',0),
('Elena Navarro','elena.navarro@mail.com','600888999','2025-12-01',20),
('Hugo Molina','hugo.molina@mail.com','600999000','2025-12-03',0),
('Irene Costa','irene.costa@mail.com','601000111','2025-12-12',12);

INSERT INTO productos(tipo,nombre,plataforma,genero,pegi,precio_dia,stock_total,stock_disponible) VALUES
('CONSOLA','PlayStation 5','PS5',NULL,NULL,9.50,5,5),
('CONSOLA','Nintendo Switch OLED','SWITCH',NULL,NULL,7.00,4,4),
('CONSOLA','Xbox Series X','XBOX_SERIES',NULL,NULL,9.00,3,3),
('CONSOLA','PlayStation 4 Slim','PS4',NULL,NULL,5.50,4,4),

('JUEGO','EA Sports FC 25','PS5','Deportes',3,4.00,10,10),
('JUEGO','The Legend of Zelda: TOTK','SWITCH','Aventura',12,4.50,8,8),
('JUEGO','Mario Kart 8 Deluxe','SWITCH','Carreras',3,3.50,12,12),
('JUEGO','Elden Ring','PS5','RPG',16,4.50,6,6),
('JUEGO','Hogwarts Legacy','PS5','Aventura',12,4.00,7,7),
('JUEGO','Forza Horizon 5','XBOX_SERIES','Carreras',3,4.00,6,6),
('JUEGO','Minecraft','PC','Sandbox',7,2.50,20,20),
('JUEGO','Cyberpunk 2077','PC','RPG',18,3.50,8,8);

-- Alquileres de ejemplo (algunos cerrados y otros abiertos)
INSERT INTO alquileres(id_cliente,id_empleado,fecha_inicio,fecha_fin_prevista,fecha_fin_real,estado,deposito,observaciones)
VALUES
(1,2,'2025-12-05','2025-12-08','2025-12-08','CERRADO',20,'Todo ok'),
(2,3,'2025-12-10','2025-12-12','2025-12-14','CERRADO',10,'Devolucion con retraso'),
(4,2,'2025-12-18','2025-12-20',NULL,'ABIERTO',15,'En curso'),
(6,5,'2025-12-20','2025-12-22',NULL,'ABIERTO',0,'En curso'),
(8,3,'2025-12-01','2025-12-03','2025-12-03','CERRADO',10,''),
(9,2,'2025-12-22','2025-12-24',NULL,'ABIERTO',20,'');

-- Detalle de cada alquiler (precio_dia_aplicado: congelamos el precio del dia para ese alquiler)
INSERT INTO alquiler_detalle(id_alquiler,id_producto,cantidad,precio_dia_aplicado,descuento_pct) VALUES
(1,1,1,9.50,0),
(1,5,1,4.00,10),

(2,2,1,7.00,0),
(2,6,1,4.50,0),
(2,7,1,3.50,0),

(3,8,1,4.50,0),
(3,1,1,9.50,0),

(4,10,1,4.00,0),
(4,3,1,9.00,0),

(5,4,1,5.50,0),
(5,7,1,3.50,0),

(6,11,1,2.50,0),
(6,12,1,3.50,0);

-- Ajuste manual de stock_disponible para reflejar alquileres ABIERTO (para que el ejemplo sea coherente)
UPDATE productos SET stock_disponible = stock_disponible - 2 WHERE id_producto IN (1,3); -- PS5 y Xbox Series X en alquileres abiertos (3 y 4)
UPDATE productos SET stock_disponible = stock_disponible - 1 WHERE id_producto IN (8,10,11,12); -- juegos en alquiler abierto

-- Pagos de ejemplo
INSERT INTO pagos(id_alquiler,fecha_pago,importe,metodo,concepto) VALUES
(1,'2025-12-08 18:10:00',25.00,'TARJETA','Pago cierre alquiler 1'),
(2,'2025-12-12 12:00:00',10.00,'EFECTIVO','Pago parcial'),
(2,'2025-12-14 20:30:00',15.00,'BIZUM','Pago cierre alquiler 2'),
(5,'2025-12-03 19:00:00',20.00,'TARJETA','Pago cierre alquiler 5');

-- -------------------------
-- 3) VISTAS (REPORTING)
-- -------------------------

CREATE OR REPLACE VIEW vw_alquileres_detalle AS
SELECT
  a.id_alquiler,
  a.estado,
  c.nombre AS cliente,
  e.nombre AS empleado,
  a.fecha_inicio,
  a.fecha_fin_prevista,
  a.fecha_fin_real,
  d.id_producto,
  p.tipo,
  p.nombre AS producto,
  p.plataforma,
  d.cantidad,
  d.precio_dia_aplicado,
  d.descuento_pct,
  GREATEST(DATEDIFF(a.fecha_fin_prevista, a.fecha_inicio) + 1, 1) AS dias_previstos,
  ROUND(d.cantidad * d.precio_dia_aplicado * GREATEST(DATEDIFF(a.fecha_fin_prevista, a.fecha_inicio) + 1, 1) * (1 - d.descuento_pct/100), 2) AS importe_previsto_linea,
  a.total_final
FROM alquileres a
JOIN clientes c  ON c.id_cliente = a.id_cliente
JOIN empleados e ON e.id_empleado = a.id_empleado
JOIN alquiler_detalle d ON d.id_alquiler = a.id_alquiler
JOIN productos p ON p.id_producto = d.id_producto;

CREATE OR REPLACE VIEW vw_productos_ranking AS
SELECT
  p.id_producto,
  p.tipo,
  p.nombre AS producto,
  p.plataforma,
  COUNT(*) AS veces_en_alquiler,
  SUM(d.cantidad) AS unidades_alquiladas,
  ROUND(SUM(
    d.cantidad * d.precio_dia_aplicado
    * GREATEST(DATEDIFF(IFNULL(a.fecha_fin_real, a.fecha_fin_prevista), a.fecha_inicio) + 1, 1)
    * (1 - d.descuento_pct/100)
  ), 2) AS ingresos_estimados
FROM productos p
LEFT JOIN alquiler_detalle d ON d.id_producto = p.id_producto
LEFT JOIN alquileres a ON a.id_alquiler = d.id_alquiler
GROUP BY p.id_producto, p.tipo, p.nombre, p.plataforma;

-- -------------------------
-- 4) PROCEDIMIENTOS (OPERATIVA)
-- -------------------------

DELIMITER $$

CREATE PROCEDURE sp_nuevo_alquiler(
  IN p_id_cliente INT,
  IN p_id_empleado INT,
  IN p_fecha_inicio DATE,
  IN p_fecha_fin_prevista DATE,
  IN p_deposito DECIMAL(7,2),
  OUT p_id_alquiler INT
)
BEGIN
  IF p_fecha_fin_prevista < p_fecha_inicio THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha fin prevista no puede ser anterior a la fecha inicio';
  END IF;

  IF NOT EXISTS (SELECT 1 FROM clientes WHERE id_cliente = p_id_cliente) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cliente inexistente';
  END IF;

  IF NOT EXISTS (SELECT 1 FROM empleados WHERE id_empleado = p_id_empleado AND activo = 1) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Empleado inexistente o inactivo';
  END IF;

  INSERT INTO alquileres(id_cliente,id_empleado,fecha_inicio,fecha_fin_prevista,deposito,estado)
  VALUES(p_id_cliente,p_id_empleado,p_fecha_inicio,p_fecha_fin_prevista,p_deposito,'ABIERTO');

  SET p_id_alquiler = LAST_INSERT_ID();
END$$

CREATE PROCEDURE sp_agregar_producto_alquiler(
  IN p_id_alquiler INT,
  IN p_id_producto INT,
  IN p_cantidad INT,
  IN p_descuento_pct DECIMAL(5,2)
)
BEGIN
  DECLARE v_precio DECIMAL(6,2);
  DECLARE v_stock INT;

  IF p_cantidad <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cantidad debe ser mayor que 0';
  END IF;

  IF p_descuento_pct < 0 OR p_descuento_pct > 100 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Descuento fuera de rango (0-100)';
  END IF;

  IF NOT EXISTS (SELECT 1 FROM alquileres WHERE id_alquiler = p_id_alquiler AND estado = 'ABIERTO') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alquiler inexistente o no esta ABIERTO';
  END IF;

  IF NOT EXISTS (SELECT 1 FROM productos WHERE id_producto = p_id_producto AND activo = 1) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Producto inexistente o inactivo';
  END IF;

  IF EXISTS (SELECT 1 FROM alquiler_detalle WHERE id_alquiler = p_id_alquiler AND id_producto = p_id_producto) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Este producto ya esta incluido en el alquiler';
  END IF;

  SELECT precio_dia, stock_disponible INTO v_precio, v_stock
  FROM productos
  WHERE id_producto = p_id_producto;

  IF v_stock < p_cantidad THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stock insuficiente para este producto';
  END IF;

  INSERT INTO alquiler_detalle(id_alquiler,id_producto,cantidad,precio_dia_aplicado,descuento_pct)
  VALUES(p_id_alquiler,p_id_producto,p_cantidad,v_precio,p_descuento_pct);

  UPDATE productos
  SET stock_disponible = stock_disponible - p_cantidad
  WHERE id_producto = p_id_producto;
END$$

CREATE PROCEDURE sp_registrar_devolucion(
  IN p_id_alquiler INT,
  IN p_fecha_fin_real DATE
)
BEGIN
  DECLARE v_inicio DATE;
  DECLARE v_fin_prev DATE;
  DECLARE v_dias INT;
  DECLARE v_retraso INT;
  DECLARE v_total_base DECIMAL(9,2);
  DECLARE v_recargo DECIMAL(9,2);

  IF NOT EXISTS (SELECT 1 FROM alquileres WHERE id_alquiler = p_id_alquiler AND estado = 'ABIERTO') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alquiler inexistente o ya cerrado';
  END IF;

  SELECT fecha_inicio, fecha_fin_prevista INTO v_inicio, v_fin_prev
  FROM alquileres
  WHERE id_alquiler = p_id_alquiler;

  IF p_fecha_fin_real < v_inicio THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha fin real no puede ser anterior a la fecha inicio';
  END IF;

  SET v_dias = GREATEST(DATEDIFF(p_fecha_fin_real, v_inicio) + 1, 1);
  SET v_retraso = GREATEST(DATEDIFF(p_fecha_fin_real, v_fin_prev), 0);

  SELECT ROUND(SUM(
      d.cantidad * d.precio_dia_aplicado * v_dias * (1 - d.descuento_pct/100)
    ), 2)
  INTO v_total_base
  FROM alquiler_detalle d
  WHERE d.id_alquiler = p_id_alquiler;

  IF v_total_base IS NULL THEN
    SET v_total_base = 0;
  END IF;

  -- Regla simple de negocio: 5.00 EUR por dia de retraso (penalizacion fija)
  SET v_recargo = ROUND(v_retraso * 5.00, 2);

  UPDATE alquileres
  SET fecha_fin_real = p_fecha_fin_real,
      estado = 'CERRADO',
      total_base = v_total_base,
      recargo = v_recargo,
      total_final = v_total_base + v_recargo
  WHERE id_alquiler = p_id_alquiler;

  -- Devolver stock al inventario
  UPDATE productos p
  JOIN alquiler_detalle d ON d.id_producto = p.id_producto
  SET p.stock_disponible = p.stock_disponible + d.cantidad
  WHERE d.id_alquiler = p_id_alquiler;
END$$

CREATE PROCEDURE sp_registrar_pago(
  IN p_id_alquiler INT,
  IN p_importe DECIMAL(9,2),
  IN p_metodo VARCHAR(10),
  IN p_concepto VARCHAR(120)
)
BEGIN
  IF p_importe <= 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El importe debe ser mayor que 0';
  END IF;

  IF NOT EXISTS (SELECT 1 FROM alquileres WHERE id_alquiler = p_id_alquiler) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alquiler inexistente';
  END IF;

  IF p_metodo NOT IN ('EFECTIVO','TARJETA','BIZUM') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Metodo no valido (EFECTIVO, TARJETA, BIZUM)';
  END IF;

  IF p_concepto IS NULL OR p_concepto = '' THEN
    SET p_concepto = 'Pago alquiler';
  END IF;

  INSERT INTO pagos(id_alquiler,importe,metodo,concepto)
  VALUES(p_id_alquiler,p_importe,p_metodo,p_concepto);
END$$

DELIMITER ;

-- -------------------------
-- 5) PRUEBAS RAPIDAS (opcional)
-- -------------------------
-- CALL sp_nuevo_alquiler(3,2,'2025-12-25','2025-12-27',10,@id);
-- SELECT @id;
-- CALL sp_agregar_producto_alquiler(@id,6,1,0);
-- CALL sp_registrar_devolucion(@id,'2025-12-27');
-- CALL sp_registrar_pago(@id,15,'TARJETA','Pago final');
