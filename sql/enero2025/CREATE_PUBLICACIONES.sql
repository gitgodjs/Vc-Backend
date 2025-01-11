CREATE TABLE publicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- ID autoincremental
    id_user INT,  -- ID del usuario que realiza la publicación
    nombre VARCHAR(255) NOT NULL,  -- Nombre de la publicación
    descripcion TEXT NOT NULL,  -- Descripción de la publicación
    estado_producto INT NOT NULL,  -- Nueva columna para el estado del producto
    estado INT DEFAULT 1,  -- Estado de la publicación
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Fecha de creación
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Fecha de actualización automática
    FOREIGN KEY (id_user) REFERENCES users(id),  -- Relación con la tabla 'users'
    FOREIGN KEY (estado_producto) REFERENCES estados(id)  -- Relación con la tabla 'estados'
);