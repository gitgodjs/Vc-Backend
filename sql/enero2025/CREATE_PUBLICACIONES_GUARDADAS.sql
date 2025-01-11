CREATE TABLE usuarios_publicaciones_guardadas (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- ID autoincremental
    id_publicacion INT NOT NULL,  -- ID de la publicación guardada
    user_id INT NOT NULL,  -- ID del usuario que guarda la publicación
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Fecha de creación
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Fecha de actualización automática
    FOREIGN KEY (id_publicacion) REFERENCES publicaciones(id),  -- Relación con la tabla 'publicaciones'
    FOREIGN KEY (user_id) REFERENCES users(id)  -- Relación con la tabla 'users'
);