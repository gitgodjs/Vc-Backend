USE Vc;

INSERT INTO estado_publicacion (estado) VALUES 
('Activa'),
('Pendiente'),
('Vendido');

INSERT INTO estado_ropa (estado) VALUES 
('Nuevo'),
('Como Nuevo'),
('Usado'),
('Usado con detalle'),
('Muy Usado'),
('Restaurada'),
('En Reparación'),
('Antiguo');

-- Tabla de categorías
INSERT INTO `ropa_categorias` (`id`, `category`) VALUES
(1, 'Ropa Superior'),
(2, 'Ropa Inferior'),
(3, 'Calzado'),
(4, 'Ropa de Abrigo'),
(5, 'Ropa Deportiva'),
(6, 'Ropa Formal'),
(7, 'Ropa de Noche'),
(8, 'Accesorios'),
(9, 'Ropa de Baño'),
(10, 'Ropa Casual'),
(11, 'Ropa de Maternidad'),
(12, 'Ropa de Trabajo'),
(13, 'Ropa Interior');

-- Tabla de prendas
INSERT INTO `ropa_prendas` (`id`, `prenda`, `categoria_id`) VALUES
-- Ropa Superior (1)
(1, 'Camisetas / Remeras', 1),
(2, 'Camisas', 1),
(3, 'Blusas', 1),
(4, 'Suéteres / Jerseys', 1),
(5, 'Chalecos', 1),

-- Ropa Inferior (2)
(6, 'Pantalones', 2),
(7, 'Shorts', 2),
(8, 'Faldas', 2),
(9, 'Leggings', 2),

-- Calzado (3)
(10, 'Zapatos Formales', 3),
(11, 'Zapatos Deportivos', 3),
(12, 'Botas', 3),
(13, 'Botines', 3),

-- Ropa de Abrigo (4)
(14, 'Chaquetas', 4),
(15, 'Abrigos', 4),
(16, 'Parkas', 4),

-- Ropa Deportiva (5)
(17, 'Pantalones deportivos', 5),
(18, 'Chándales', 5),
(19, 'Leggings deportivos', 5),

-- Ropa Formal (6)
(20, 'Trajes', 6),
(21, 'Camisas de vestir', 6),
(22, 'Pantalones de vestir', 6),

-- Ropa de Noche (7)
(23, 'Vestidos de noche', 7),
(24, 'Tops de fiesta', 7),

-- Accesorios (8)
(25, 'Bufandas', 8),
(26, 'Gorros / Sombreros', 8),

-- Ropa de Baño (9)
(27, 'Bikinis', 9),
(28, 'Bañadores', 9),

-- Ropa Casual (10)
(29, 'Jeans', 10),
(30, 'Joggers', 10),

-- Ropa de Maternidad (11)
(31, 'Vestidos de maternidad', 11),
(32, 'Pantalones de maternidad', 11),

-- Ropa de Trabajo (12)
(33, 'Overoles', 12),
(34, 'Batas', 12),

-- Ropa Interior (13)
(35, 'Calzoncillos / Bóxers', 13),
(36, 'Sujetadores', 13);

INSERT INTO ropa_tipo (tipo) VALUES 
('De marca'),
('De diseño'),
('Otro');

-- Nuevas prendas para la tabla ropa_prendas

-- Calzado (3)
INSERT INTO `ropa_prendas` (`prenda`, `categoria_id`) VALUES
('Zapatillas casuales', 3),
('Tacos', 3),
('Crocs', 3),

-- Ropa de Abrigo (4)
('Camperones', 4),
('Camperas', 4),
('Sweaters', 4),
('Pilotines', 4),

-- Ropa Deportiva (5)
('Remera deportiva', 5),
('Joggins', 5),
('Campera deportiva', 5),

-- Ropa Formal (6)
('Vestidos', 6),
('Polleras', 6),
('Chalecos', 6),
('Sacos', 6),
('Corbaras', 6),

-- Ropa de Noche (7)
('Bodis', 7),
('Corsets', 7),
('Blazers', 7),

-- Accesorios (8)
('Gorras', 8),
('Lentes', 8),
('Carteras / Bolsos', 8),
('Riñoneras', 8),
('Collares / Anillos', 8),
('Guantes', 8),
('Aros', 8),
('Cinturones', 8),
('Billeteras', 8),

-- Ropa Casual (10)
('Shorts casuales', 10),
('Remeras casuales', 10);
