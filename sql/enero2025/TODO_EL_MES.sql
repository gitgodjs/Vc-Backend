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

CREATE TABLE images_users (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID autoincremental
    id_usuario INT, -- ID del usuario relacionado
    url VARCHAR(255) NOT NULL, -- URL de la imagen
    tamaño INT NOT NULL, -- Tamaño de la imagen en bytes
    extension VARCHAR(10) NOT NULL, -- Extensión de la imagen (por ejemplo, jpg, png)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Fecha de creación
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Fecha de actualización automática
    FOREIGN KEY (id_usuario) REFERENCES users(id) -- Relación con la tabla 'users'
);

CREATE TABLE images_portada_users (
    id INT AUTO_INCREMENT PRIMARY KEY, -- ID autoincremental
    id_usuario INT, -- ID del usuario relacionado
    url VARCHAR(255) NOT NULL, -- URL de la imagen
    tamaño INT NOT NULL, -- Tamaño de la imagen en bytes
    extension VARCHAR(10) NOT NULL, -- Extensión de la imagen (por ejemplo, jpg, png)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Fecha de creación
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Fecha de actualización automática
    FOREIGN KEY (id_usuario) REFERENCES users(id) -- Relación con la tabla 'users'
);

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

CREATE TABLE publicaciones_tipo (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- ID autoincremental
    nombre VARCHAR(255) NOT NULL  -- Nombre del tipo de publicación (por ejemplo, ropa superior, ropa inferior, etc.)
);

CREATE TABLE estado_publicacion (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- ID autoincremental
    estado VARCHAR(255) NOT NULL  -- Estado de la publicación (activa, pendiente, vendido)
);

CREATE TABLE estado_ropa (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- ID autoincremental
    estado VARCHAR(255) NOT NULL  -- Estado de la ropa (nuevo, como nuevo, buen estado, usado, mal estado, muy mal estado)
);


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

CREATE TABLE usuarios_publicaciones_guardadas (
    id INT AUTO_INCREMENT PRIMARY KEY,  -- ID autoincremental
    id_publicacion INT NOT NULL,  -- ID de la publicación guardada
    user_id INT NOT NULL,  -- ID del usuario que guarda la publicación
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Fecha de creación
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Fecha de actualización automática
    FOREIGN KEY (id_publicacion) REFERENCES publicaciones(id),  -- Relación con la tabla 'publicaciones'
    FOREIGN KEY (user_id) REFERENCES users(id)  -- Relación con la tabla 'users'
);

CREATE TABLE users_codigos (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(255) NOT NULL,
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    update_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE users_tallas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    remeras VARCHAR(10),
    pantalones VARCHAR(10),
    shorts VARCHAR(10),
    vestidos VARCHAR(10),
    abrigos VARCHAR(10),
    calzados INT,
    accesorios VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id) 
);

ALTER TABLE `publicaciones`
ADD COLUMN `prenda` varchar(255) NOT NULL AFTER `estado_publicacion`,
ADD COLUMN `talle` varchar(255) NOT NULL AFTER `prenda`,
ADD COLUMN `marca` varchar(255) NOT NULL AFTER `talle`;

CREATE TABLE `ropa` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `prenda` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
)

CREATE TABLE `ropa_categorias` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `category` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id`)
)

ALTER TABLE `users`
  DROP COLUMN `apellido`,  
  DROP COLUMN `ubicacion_id`,   
  DROP COLUMN `image_id`,   
  ADD COLUMN `ubicacion` VARCHAR(255) AFTER `descripcion`, 
  CHANGE `fecha_nacimiento` `fecha_nacimiento` TIMESTAMP NULL DEFAULT NULL;
