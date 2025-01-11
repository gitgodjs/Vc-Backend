CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID autoincremental
    correo VARCHAR(255) UNIQUE NOT NULL, -- Correo electrónico único
    nombre VARCHAR(100) NOT NULL, -- Nombre del usuario
    apellido VARCHAR(100) NOT NULL, -- Apellido del usuario
    descripcion TEXT, -- Descripción del usuario (opcional)
    ubicacion_id INT, -- ID de la ubicación (relación con otra tabla)
    image_id INT, -- ID de la imagen (relación con otra tabla)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Fecha de creación
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Fecha de actualización automática
    email_verified_at TIMESTAMP NULL, -- Fecha de verificación de correo (puede ser nula)
    telefono VARCHAR(20), -- Teléfono del usuario (opcional)
    red_social VARCHAR(255), -- Red social del usuario (opcional)
    genero ENUM('masculino', 'femenino', 'otro') NULL, -- Género (opcional)
    fecha_nacimiento DATE -- Fecha de nacimiento (opcional)
);
