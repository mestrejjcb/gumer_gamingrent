# GamingRent · Backoffice (boceto) — PHP + XAMPP + MySQL

## Instalación (rápida)
1) Copia la carpeta del proyecto dentro de:
   `C:\xampp\htdocs\gamingrent`

2) Crea/recarga la base de datos ejecutando:
   `sql/alquiler_gaming_opcionA_fix.sql`

3) Configura la conexión:
- Copia `app/config.example.php` → `app/config.php`
- Edita host/usuario/password si hace falta (por defecto XAMPP: root sin contraseña).

4) Abre en el navegador:
- `http://localhost/gamingrent/login.php`

## Login (boceto)
- admin / admin123 (cámbialo en `app/config.php`)

## Qué incluye
- UI profesional + responsive (Bootstrap 5.3 + CSS propio)
- Sidebar
- Modo claro/oscuro (persistente)
- Páginas conectadas a la BD:
  - Dashboard, Clientes, Productos/Stock, Alquileres, Pagos, Empleados, Reportes (Vistas), Acciones (SPs)

## Siguiente paso (si quieres)
- CRUD completo (alta/edición) + login real con tabla de usuarios y roles.


## Setup (si no conecta)
Abre:
- http://localhost/gamingrent/setup.php


## V3 · Login real (tabla usuarios) + CRUD mínimo
### 1) Importa la BD base
- Ejecuta: `sql/alquiler_gaming_opcionA_fix.sql`

### 2) Crea tabla usuarios
- Ejecuta: `sql/v3_migracion_usuarios.sql`
  (o simplemente abre /install.php y lo creará si no existe)

### 3) Crea el admin
- Abre: `http://localhost/gamingrent/install.php`
- Crea admin (contraseña que tú elijas)

### 4) Login
- `http://localhost/gamingrent/login.php`

### CRUD incluido
- Clientes: alta y edición
- Productos: alta, edición y baja lógica (activo=0)


## V4 · Alquileres funcionales (sin SPs) + Gestión de usuarios + Roles
### Importación recomendada para XAMPP (MariaDB)
- Ejecuta: `sql/alquiler_gaming_opcionA_xampp.sql`

### Login real
1) Abre `http://localhost/gamingrent/install.php` y crea el admin.
2) Login en `http://localhost/gamingrent/login.php`

### Flujo de negocio (Alquiler)
- `Alquileres` → `+ Nuevo alquiler` → entra al detalle → añade productos
- Mientras esté ABIERTO: el stock se reserva (baja stock_disponible)
- Al `Cerrar alquiler`: el stock vuelve y se calcula recargo si hay retraso
- En el detalle puedes registrar pagos y ver pendiente

### Roles
- GERENCIA: todo + usuarios + acciones (SPs)
- MOSTRADOR: clientes, productos, alquileres, pagos, reportes
- TECNICO: productos, empleados, reportes (solo consulta operativa)
