CREATE TABLE opiniones_users (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID autoincremental
    id_comentador INT, -- ID del usuario que hace el comentario
    id_comentado INT, -- ID del usuario que recibe el comentario
    comentario TEXT NOT NULL, -- Comentario que realiza el usuario
    rate_general INT NOT NULL, -- Calificación general (1-5)
    rate_calidad_precio INT NOT NULL, -- Calificación de calidad-precio (1-5)
    rate_atencion INT NOT NULL, -- Calificación de atención (1-5)
    rate_flexibilidad INT NOT NULL, -- Calificación de flexibilidad (1-5)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Fecha de creación
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Fecha de actualización automática
    FOREIGN KEY (id_comentador) REFERENCES users(id), -- Relación con la tabla 'users' (comentador)
    FOREIGN KEY (id_comentado) REFERENCES users(id) -- Relación con la tabla 'users' (comentado)
);
