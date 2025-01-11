CREATE TABLE images_publicaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- ID autoincremental
    id_usuario INT,  -- ID del usuario relacionado
    id_publicacion INT NOT NULL,  -- ID de la publicación relacionada
    url VARCHAR(255) NOT NULL,  -- URL de la imagen
    tamaño INT NOT NULL,  -- Tamaño de la imagen en bytes
    extension VARCHAR(10) NOT NULL,  -- Extensión de la imagen (por ejemplo, jpg, png)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Fecha de creación
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Fecha de actualización automática
    FOREIGN KEY (id_usuario) REFERENCES users(id),  -- Relación con la tabla 'users'
    FOREIGN KEY (id_publicacion) REFERENCES publicaciones(id)  -- Relación con la tabla 'publicaciones'
);