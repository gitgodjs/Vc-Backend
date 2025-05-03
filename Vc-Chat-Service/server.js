require('dotenv').config({path:'./.env'});
const express = require('express');
const { createServer } = require('http');
const { Server } = require('socket.io');
const mysql = require('mysql2/promise');
const cors = require('cors');

// Redis para guardar users conectados en tiempo real
const { createClient } = require('redis');
const redisClient = createClient({ url: 'redis://redis:6379' });

// Configuración básica
const restApp = express();
const REST_PORT = 3001;
const restServer = createServer(restApp);

// Para redis!
(async () => {
  await redisClient.connect();
  console.log('✅ Redis conectado para manejo de usuarios online');
})();

// Configuración CORS
restApp.use(cors({
  origin: "http://localhost:3000",
  methods: ["GET", "POST"],
  credentials: true
}));

// Middleware para parsear JSON
restApp.use(express.json());

// Conexión a la base de datos
const db = mysql.createPool({
  host: process.env.DB_HOST || 'vc-backend-mysql-1',
  port: process.env.DB_PORT || 3306,
  user: process.env.DB_USERNAME || 'vclothes',
  password: process.env.DB_PASSWORD || 'vintageClothes2025',
  database: process.env.DB_DATABASE || 'Vc',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});

// ==============================================
// ENDPOINTS (DEBEN IR ANTES DE LOS MIDDLEWARES DE ERROR)
// ==============================================

// Endpoint de diagnóstico para ver rutas disponibles
restApp.get('/api/routes', (req, res) => {
  const routes = [];
  restApp._router.stack.forEach((middleware) => {
    if (middleware.route) {
      routes.push({
        path: middleware.route.path,
        methods: Object.keys(middleware.route.methods)
      });
    }
  });
  res.json({ routes });
});

// Endpoint de verificación básica
restApp.get('/api/connection-check', (req, res) => {
  res.json({
    status: '¡Conexión REST exitosa!',
    time: new Date().toISOString()
  });
});

// Endpoint corregido para conversaciones (CON LA "S")
restApp.get('/api/conversations/:user_id', async (req, res) => {
  try {
    const userId = parseInt(req.params.user_id);
    
    if (isNaN(userId)) {
      return res.status(400).json({ 
        success: false,
        error: 'El ID de usuario debe ser un número' 
      });
    }

    // Consulta mejorada con conteo de mensajes no leídos
    const [conversations] = await db.query(`
      SELECT 
        c.*,
        u.id as other_user_id,
        u.nombre as other_user_nombre,
        u.correo as other_user_email,
        img.url as other_user_image_url,
    
        /* Datos del último mensaje */
        last_msg.id as last_message_id,
        last_msg.content as last_message_content,
        last_msg.emisor_id as last_message_emisor_id,
        last_msg.read_at as last_message_read_at,
        last_msg.created_at as last_message_created_at,
        last_msg.updated_at as last_message_updated_at,
    
        /* Datos del emisor del último mensaje */
        msg_sender.id as last_message_sender_id,
        msg_sender.nombre as last_message_sender_nombre,
    
        /* Contador de mensajes no leídos */
        (
          SELECT COUNT(*) 
          FROM chat_messages unread
          WHERE unread.conversation_id = c.id
          AND unread.emisor_id = u.id  /* Mensajes del otro usuario */
          AND unread.read_at IS NULL   /* No leídos */
        ) as unread_count
    
      FROM chat_conversations c
    
      /* Obtener al otro usuario */
      JOIN users u ON 
        CASE 
          WHEN c.emisor_id = ? THEN c.receptor_id 
          ELSE c.emisor_id 
        END = u.id
    
      /* Imagen del otro usuario (última por fecha) */
      LEFT JOIN (
        SELECT i1.*
        FROM images_users i1
        INNER JOIN (
          SELECT id_usuario, MAX(created_at) as max_date
          FROM images_users
          GROUP BY id_usuario
        ) i2 ON i1.id_usuario = i2.id_usuario AND i1.created_at = i2.max_date
      ) img ON img.id_usuario = u.id
    
      /* Último mensaje de cada conversación */
      LEFT JOIN (
        SELECT m1.*
        FROM chat_messages m1
        INNER JOIN (
          SELECT conversation_id, MAX(created_at) as max_date
          FROM chat_messages
          GROUP BY conversation_id
        ) m2 ON m1.conversation_id = m2.conversation_id AND m1.created_at = m2.max_date
      ) last_msg ON last_msg.conversation_id = c.id
    
      /* Info del emisor del último mensaje */
      LEFT JOIN users msg_sender ON last_msg.emisor_id = msg_sender.id
    
      /* Conversaciones donde el usuario participa */
      WHERE 
        (c.emisor_id = ? OR c.receptor_id = ?)
        AND c.deleted_at IS NULL
    
      ORDER BY last_msg.created_at DESC
    `, [userId, userId, userId]);

    // Función para formatear la fecha
    const formatTimeAgo = (dateString) => {
      if (!dateString) return null;
      
      const date = new Date(dateString);
      const now = new Date();
      const seconds = Math.floor((now - date) / 1000);
      
      const intervals = {
        año: 31536000,
        mes: 2592000,
        semana: 604800,
        día: 86400,
        hora: 3600,
        minuto: 60
      };
      
      for (const [unit, secondsInUnit] of Object.entries(intervals)) {
        const interval = Math.floor(seconds / secondsInUnit);
        if (interval >= 1) {
          return `Hace ${interval} ${unit}${interval !== 1 ? 's' : ''}`;
        }
      }
      
      return 'Hace unos segundos';
    };
    
    const formattedConversations = conversations.map(conv => ({
      id: conv.id,
      other_user: {
        id: conv.other_user_id,
        nombre: conv.other_user_nombre,
        email: conv.other_user_email,
        image_url: `http://localhost:8080/storage/${conv.other_user_image_url}`,
      },
      last_message: conv.last_message_content ? {
        id: conv.last_message_id,
        content: conv.last_message_content,
        emisor: {
          id: conv.last_message_sender_id,
          nombre: conv.last_message_sender_nombre,
        },
        read_at: conv.last_message_read_at,
        created_at: conv.last_message_created_at,
        updated_at: conv.last_message_updated_at,
        time_ago: formatTimeAgo(conv.last_message_created_at)  // Fecha formateada
      } : null,
      unread_count: conv.unread_count || 0,  // Mensajes no leídos
      created_at: conv.created_at,
      updated_at: conv.updated_at
    }));

    res.json({ 
      success: true, 
      conversations: formattedConversations 
    });

  } catch (error) {
    console.error('Error al obtener conversaciones:', error);
    res.status(500).json({ 
      success: false,
      error: 'Error al obtener conversaciones',
      details: error.message 
    });
  }
});

// Nuevo endpoint para verificar estado de usuario
restApp.get('/api/chat/checkUserStatus', async (req, res) => {
  try {
    const userId = req.query.user_id;
    if (!userId) {
      return res.status(400).json({
        success: false,
        error: 'Se requiere user_id'
      });
    }

    const isOnline = await redisClient.hExists('userConnections', userId);
    
    res.json({
      success: true,
      isOnline
    });
    
  } catch (error) {
    console.error('Error al verificar estado:', error);
    res.status(500).json({
      success: false,
      error: 'Error al verificar estado'
    });
  }
});

async function getFormattedConversation(conversationId, userId) {
  const [result] = await db.query(`
    SELECT 
      c.*,
      u.id as other_user_id,
      u.nombre as other_user_nombre,
      u.correo as other_user_email,
  
      last_msg.id as last_message_id,
      last_msg.content as last_message_content,
      last_msg.emisor_id as last_message_emisor_id,
      last_msg.read_at as last_message_read_at,
      last_msg.created_at as last_message_created_at,
      last_msg.updated_at as last_message_updated_at,
  
      msg_sender.id as last_message_sender_id,
      msg_sender.nombre as last_message_sender_nombre,
  
      (
        SELECT COUNT(*) 
        FROM chat_messages unread
        WHERE unread.conversation_id = c.id
        AND unread.emisor_id != ? 
        AND unread.read_at IS NULL
      ) as unread_count
  
    FROM chat_conversations c
    JOIN users u ON 
      CASE 
        WHEN c.emisor_id = ? THEN c.receptor_id 
        ELSE c.emisor_id 
      END = u.id
    LEFT JOIN (
      SELECT m1.*
      FROM chat_messages m1
      INNER JOIN (
        SELECT conversation_id, MAX(created_at) as max_date
        FROM chat_messages
        GROUP BY conversation_id
      ) m2 ON m1.conversation_id = m2.conversation_id AND m1.created_at = m2.max_date
    ) last_msg ON last_msg.conversation_id = c.id
    LEFT JOIN users msg_sender ON last_msg.emisor_id = msg_sender.id
    WHERE c.id = ? 
      AND (c.emisor_id = ? OR c.receptor_id = ?)
      AND c.deleted_at IS NULL 
  `, [userId, userId, conversationId, userId, userId]);
  

  if (!result.length) return null;

  const conv = result[0];

  const formatTimeAgo = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    const intervals = {
      año: 31536000,
      mes: 2592000,
      semana: 604800,
      día: 86400,
      hora: 3600,
      minuto: 60
    };
    for (const [unit, secondsInUnit] of Object.entries(intervals)) {
      const interval = Math.floor(seconds / secondsInUnit);
      if (interval >= 1) {
        return `Hace ${interval} ${unit}${interval !== 1 ? 's' : ''}`;
      }
    }
    return 'Hace unos segundos';
  };

  return {
    id: conv.id,
    other_user: {
      id: conv.other_user_id,
      nombre: conv.other_user_nombre,
      email: conv.other_user_email,
    },
    last_message: conv.last_message_content ? {
      id: conv.last_message_id,
      content: conv.last_message_content,
      emisor: {
        id: conv.last_message_sender_id,
        nombre: conv.last_message_sender_nombre,
      },
      read_at: conv.last_message_read_at,
      created_at: conv.last_message_created_at,
      updated_at: conv.last_message_updated_at,
      time_ago: formatTimeAgo(conv.last_message_created_at)
    } : null,
    unread_count: conv.unread_count || 0,
    created_at: conv.created_at,
    updated_at: conv.updated_at
  };
};

function formatMessage(m) {
  return {
    id: m.id,
    content: m.content,
    created_at: m.created_at,
    read_at: m.read_at,
    emisor: {
      id: m.emisor_id,
      nombre: m.emisor_nombre
    },
    conversation_id: m.conversation_id
  };
};

restApp.post('/api/chat/ofertar', async (req, res) => {
  try {
    const { publicacion, mensaje, ofertador, precio } = req.body;

    if (!ofertador?.id || !publicacion?.creador?.id) {
      throw new Error('Datos incompletos');
    }

    const emisor_id = ofertador.id;
    const receptor_id = publicacion.creador.id;
    const publicacion_id = publicacion.id;
    const precioFormateado = Number(precio);

    // 1. Buscar conversación existente
    const [existing] = await db.query(`
      SELECT id FROM chat_conversations 
      WHERE (emisor_id = ? AND receptor_id = ?)
         OR (emisor_id = ? AND receptor_id = ?)
    `, [emisor_id, receptor_id, receptor_id, emisor_id]);

    let conversation_id;

    if (existing.length > 0) {
      conversation_id = existing[0].id;
      await db.query(`
        UPDATE chat_conversations 
        SET updated_at = NOW() 
        WHERE id = ?
      `, [conversation_id]);
    } else {
      const [conversation] = await db.query(`
        INSERT INTO chat_conversations (emisor_id, receptor_id, created_at)
        VALUES (?, ?, NOW())
      `, [emisor_id, receptor_id]);
      conversation_id = conversation.insertId;
    }

    // 2. Guardar mensaje SIN publicacion_id ni oferta_precio
    const [message] = await db.query(`
      INSERT INTO chat_messages 
      (conversation_id, emisor_id, content, created_at)
      VALUES (?, ?, ?, NOW())
    `, [conversation_id, emisor_id, mensaje]);

    const mensaje_id = message.insertId;

    // 3. Insertar la oferta en publicaciones_ofertas
    await db.query(`
      INSERT INTO publicaciones_ofertas 
      (mensaje_id, publicacion_id, precio, estado_oferta_id, created_at)
      VALUES (?, ?, ?, 1, NOW())
    `, [mensaje_id, publicacion_id, precioFormateado]);

    // 4. Obtener datos completos del mensaje
    const [messageData] = await db.query(`
      SELECT m.*, u.nombre as emisor_nombre 
      FROM chat_messages m
      JOIN users u ON m.emisor_id = u.id
      WHERE m.id = ?
        AND m.deleted_at IS NULL
    `, [mensaje_id]);

    // 5. Obtener conversaciones formateadas para ambos usuarios
    const formattedForEmisor = await getFormattedConversation(conversation_id, emisor_id);
    const formattedForReceptor = await getFormattedConversation(conversation_id, receptor_id);

    // 6. Emitir eventos
    io.to(`user_${receptor_id}`).emit('new_message', formatMessage(messageData[0]));

    io.to(`user_${emisor_id}`).emit('new_conversation', {
      conversation: formattedForEmisor
    });
    io.to(`user_${receptor_id}`).emit('new_conversation', {
      conversation: formattedForReceptor
    });

    io.to(`user_${receptor_id}`).emit('oferta:recibida', {
      ofertador: ofertador.nombre,
      mensaje,
      precio: req.body.precio,
      publicacion: publicacion.nombre
    });

    res.json({
      success: true,
      conversation_id,
      message: messageData[0],
    });

  } catch (error) {
    console.error('Error:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

restApp.post('/api/chat/oferta/aceptar', async (req, res) => {
  let connection;
  try {   
    const { oferta_id, comprador_id, vendedor_id, publicacion_id, precio } = req.body;

    if (!oferta_id || !comprador_id || !vendedor_id || !publicacion_id || !precio) {
      throw new Error('Datos incompletos para aceptar la oferta');
    }

    connection = await db.getConnection();
    await connection.beginTransaction();

    // Obtener la oferta aceptada
    const [oferta] = await connection.query(`
      SELECT po.*, cm.conversation_id 
      FROM publicaciones_ofertas po
      JOIN chat_messages cm ON po.mensaje_id = cm.id
      WHERE po.id = ? AND po.estado_oferta_id = 1
      FOR UPDATE
    `, [oferta_id]);

    if (oferta.length === 0) {
      throw new Error('La oferta no existe o ya fue procesada');
    }

    // Verificar si ya existe venta
    const [venta] = await connection.query(`
      SELECT * FROM publicaciones_ventas WHERE oferta_id = ?
    `, [oferta_id]);

    if (venta.length > 0) {
      throw new Error(`Ya existe una venta con esta oferta ${oferta_id}`);
    }

    // 1. Marcar la publicación como vendida
    await connection.query(`
      UPDATE publicaciones
      SET estado_publicacion = 2, updated_at = NOW()
      WHERE id = ?
    `, [publicacion_id]);

    // 2. Marcar la oferta actual como aceptada
    await connection.query(`
      UPDATE publicaciones_ofertas
      SET estado_oferta_id = 2, oferta_respondida_at = NOW()
      WHERE id = ?
    `, [oferta_id]);

    // 3. Registrar venta
    await connection.query(`
      INSERT INTO publicaciones_ventas 
      (id_publicacion, id_vendedor, id_comprador, oferta_id, precio, created_at)
      VALUES (?, ?, ?, ?, ?, NOW())
    `, [publicacion_id, vendedor_id, comprador_id, oferta_id, precio]);

    // 4. Rechazar todas las otras ofertas activas de esta publicación
    const [otrasOfertas] = await connection.query(`
      SELECT po.*, cm.conversation_id, cm.emisor_id 
      FROM publicaciones_ofertas po
      JOIN chat_messages cm ON po.mensaje_id = cm.id
      WHERE po.id != ? AND po.estado_oferta_id = 1 AND po.publicacion_id = ?
    `, [oferta_id, publicacion_id]);    

    for (const ofertaRechazada of otrasOfertas) {
      // Cambiar el estado a rechazada
      await connection.query(`
        UPDATE publicaciones_ofertas
        SET estado_oferta_id = 3, oferta_respondida_at = NOW()
        WHERE id = ?
      `, [ofertaRechazada.id]);

      // Solo enviar mensaje si la oferta fue hecha en los últimos 7 días
      const creada = new Date(ofertaRechazada.created_at);
      const hace7Dias = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);

      if (creada > hace7Dias) {
        const [msg] = await connection.query(`
          INSERT INTO chat_messages 
          (conversation_id, emisor_id, content, created_at)
          VALUES (?, ?, ?, NOW())
        `, [ofertaRechazada.conversation_id, vendedor_id, `Hola! Gracias por tu oferta, pero ya fue aceptada otra propuesta.`]);

        const [messageData] = await connection.query(`
          SELECT m.*, u.nombre as emisor_nombre 
          FROM chat_messages m
          JOIN users u ON m.emisor_id = u.id
          WHERE m.id = ?
        `, [msg.insertId]);

        const m = {
          id: messageData[0].id,
          conversation_id: messageData[0].conversation_id,
          emisor_id: messageData[0].emisor_id,
          emisor_nombre: messageData[0].emisor_nombre,
          content: messageData[0].content,
          created_at: messageData[0].created_at,
        };

        io.to(`user_${ofertaRechazada.emisor_id}`).emit('new_message', formatMessage(m));
        io.to(`user_${vendedor_id}`).emit('new_message', formatMessage(m));
        io.to(`user_${ofertaRechazada.emisor_id}`).emit('oferta:rechazada', { oferta_id: ofertaRechazada.id });
      }
    }

    // 5. Crear mensaje para la oferta aceptada
    const [message] = await connection.query(`
      INSERT INTO chat_messages 
      (conversation_id, emisor_id, content, created_at)
      VALUES (?, ?, ?, NOW())
    `, [oferta[0].conversation_id, vendedor_id, `Hola! He aceptado tu oferta de $${precio}. Puedes consultarme por cualquier cosa! Gracias.`]);

    const [messageData] = await connection.query(`
      SELECT m.*, u.nombre as emisor_nombre 
      FROM chat_messages m
      JOIN users u ON m.emisor_id = u.id
      WHERE m.id = ?
    `, [message.insertId]);

    await connection.commit();

    const m = {
      id: messageData[0].id,
      conversation_id: messageData[0].conversation_id,
      emisor_id: messageData[0].emisor_id,
      emisor_nombre: messageData[0].emisor_nombre,
      content: messageData[0].content,
      created_at: messageData[0].created_at,
    };

    // Enviar mensajes al comprador y vendedor
    io.to(`user_${comprador_id}`).emit('new_message', formatMessage(m));
    io.to(`user_${vendedor_id}`).emit('new_message', formatMessage(m));
    io.to(`user_${comprador_id}`).emit('oferta:aceptada', { oferta_id });
    io.to(`user_${vendedor_id}`).emit('oferta:aceptada', { oferta_id });

    res.json({ 
      success: true, 
      message: 'Oferta aceptada y venta registrada',
      message_id: message.insertId
    });

  } catch (error) {
    if (connection) await connection.rollback();
    console.error('Error al aceptar oferta:', error);
    res.status(500).json({ success: false, error: error.message });
  } finally {
    if (connection) connection.release();
  }
});

restApp.post('/api/chat/oferta/rechazar', async (req, res) => {
  let connection;
  try {
    const { oferta_id, comprador_id, vendedor_id, publicacion_id, precio } = req.body;

    if (!oferta_id || !comprador_id || !vendedor_id || !publicacion_id || !precio) {
      throw new Error('Datos incompletos para rechazar la oferta');
    }

    connection = await db.getConnection();
    await connection.beginTransaction();

    const [oferta] = await connection.query(`
      SELECT po.*, cm.conversation_id 
      FROM publicaciones_ofertas po
      JOIN chat_messages cm ON po.mensaje_id = cm.id
      WHERE po.id = ? AND po.estado_oferta_id = 1
      FOR UPDATE
    `, [oferta_id]);

    if (oferta.length === 0) {
      throw new Error('La oferta no existe o ya fue procesada');
    }

    await connection.query(`
      UPDATE publicaciones_ofertas
      SET estado_oferta_id = 3, oferta_respondida_at = NOW()
      WHERE id = ?
    `, [oferta_id]);

    const [message] = await connection.query(`
      INSERT INTO chat_messages 
      (conversation_id, emisor_id, content, created_at)
      VALUES (?, ?, ?, NOW())
    `, [oferta[0].conversation_id, vendedor_id, `Hola! Gracias por tu oferta, pero la he rechazado.`]);

    const [messageData] = await connection.query(`
      SELECT m.*, u.nombre as emisor_nombre 
      FROM chat_messages m
      JOIN users u ON m.emisor_id = u.id
      WHERE m.id = ?
    `, [message.insertId]);

    await connection.commit();

    const m = {
      id: messageData[0].id,
      conversation_id: messageData[0].conversation_id,
      emisor_id: messageData[0].emisor_id,
      emisor_nombre: messageData[0].emisor_nombre,
      content: messageData[0].content,
      created_at: messageData[0].created_at,
    };

    // Enviar el nuevo mensaje a ambos usuarios
    io.to(`user_${comprador_id}`).emit('new_message', formatMessage(m));
    io.to(`user_${vendedor_id}`).emit('new_message', formatMessage(m));

    // Enviar evento de oferta rechazada a ambos
    io.to(`user_${comprador_id}`).emit('oferta:rechazada', { oferta_id });
    io.to(`user_${vendedor_id}`).emit('oferta:rechazada', { oferta_id });

    res.json({ 
      success: true, 
      message: 'Oferta rechazada con éxito',
      message_id: message.insertId
    });

  } catch (error) {
    if (connection) await connection.rollback();
    console.error('Error al rechazar oferta:', error);
    res.status(500).json({ success: false, error: error.message });
  } finally {
    if (connection) connection.release();
  }
});

restApp.get('/api/chat/obtenerConversation/:conversation_id', async (req, res) => {
  try {
    const conversationId = parseInt(req.params.conversation_id);
    const userId = parseInt(req.query.user_id);

    if (isNaN(conversationId) || isNaN(userId)) {
      return res.status(400).json({
        success: false,
        error: 'conversation_id y user_id deben ser numéricos'
      });
    }

    const [convCheck] = await db.query(`
      SELECT emisor_id, receptor_id FROM chat_conversations 
      WHERE id = ? AND (emisor_id = ? OR receptor_id = ?)
    `, [conversationId, userId, userId]);

    if (!convCheck.length) {
      return res.status(403).json({
        success: false,
        error: 'No tenés permiso para ver esta conversación'
      });
    }

    const { emisor_id, receptor_id } = convCheck[0];
    const otherUserId = userId === emisor_id ? receptor_id : emisor_id;

    const [otherUserData] = await db.query(`
      SELECT u.id, u.nombre, u.correo, img.url as image_url
      FROM users u
      LEFT JOIN images_users img ON img.id_usuario = u.id
      WHERE u.id = ?
      LIMIT 1
    `, [otherUserId]);

    // 1️⃣ Obtener mensajes
    const [messages] = await db.query(`
      SELECT 
        m.id,
        m.content,
        m.created_at,
        m.read_at,
        u.id as emisor_id,
        u.nombre as emisor_nombre
      FROM chat_messages m
      JOIN users u ON u.id = m.emisor_id
      WHERE m.conversation_id = ?
        AND m.deleted_at IS NULL
      ORDER BY m.id ASC
    `, [conversationId]);

    // 2️⃣ Consultar ofertas relacionadas a los mensajes
    const mensajeIds = messages.map(m => m.id);
    let ofertasByMensaje = {};

    if (mensajeIds.length) {
      const [ofertas] = await db.query(`
        SELECT o.*, p.nombre as publicacion_nombre, p.descripcion, p.talle, p.tipo, 
               p.ubicacion, p.precio as precio_publicacion,
               (SELECT img.url FROM images_publicaciones img WHERE img.id_publicacion = p.id ORDER BY img.created_at ASC LIMIT 1) as imagen_url
        FROM publicaciones_ofertas o
        JOIN publicaciones p ON p.id = o.publicacion_id
        WHERE o.mensaje_id IN (?)
      `, [mensajeIds]);

      // Asignamos la oferta al mensaje correspondiente
      ofertas.forEach(oferta => {
        if (!ofertasByMensaje[oferta.mensaje_id]) {
          ofertasByMensaje[oferta.mensaje_id] = []; // Inicializamos un array para este mensaje si no existe
        }

        // Aquí solo estamos añadiendo la oferta y la publicación asociada
        ofertasByMensaje[oferta.mensaje_id].push({
          oferta_id: oferta.id,
          precio_oferta: oferta.precio,
          estado_oferta: oferta.estado_oferta_id,
          oferta_respondida_at: oferta.oferta_respondida_at,
          publicacion: {
            id: oferta.publicacion_id,
            nombre: oferta.publicacion_nombre,
            descripcion: oferta.descripcion,
            precio: oferta.precio,
            talle: oferta.talle,
            tipo: oferta.tipo,
            ubicacion: oferta.ubicacion,
            imagen_url: oferta.imagen_url ? `http://localhost:8080/storage/${oferta.imagen_url}` : null
          }
        });
      });
    }

    // 3️⃣ Devolver la respuesta con los mensajes y sus ofertas relacionadas (si existen)
    res.json({
      success: true,
      conversation_id: conversationId,
      other_user: otherUserData[0] || null,
      image_url: `http://localhost:8080/storage/${otherUserData[0].image_url}`,
      messages: messages.map(m => ({
        id: m.id,
        content: m.content,
        created_at: m.created_at,
        read_at: m.read_at,
        emisor: {
          id: m.emisor_id,
          nombre: m.emisor_nombre
        },
        oferta: ofertasByMensaje[m.id] || null // Incluir las ofertas asociadas, si existen
      }))
    });

  } catch (error) {
    console.error('❌ Error al obtener conversación:', error);
    res.status(500).json({
      success: false,
      error: 'Error interno',
      details: error.message
    });
  }
});

restApp.post('/api/chat/marcarComoLeido', async (req, res) => {
  try {
    const { conversation_id, user_id } = req.body;

    if (!conversation_id || !user_id) {
      return res.status(400).json({
        success: false,
        error: "Faltan datos"
      });
    }

    // Marcar como leídos
    await db.query(`
      UPDATE chat_messages
      SET read_at = NOW()
      WHERE conversation_id = ? 
      AND emisor_id != ?
      AND read_at IS NULL
    `, [conversation_id, user_id]);

    // 🔍 Obtener los IDs de los mensajes actualizados
    const [updatedMessages] = await db.query(`
      SELECT id FROM chat_messages 
      WHERE conversation_id = ? AND emisor_id != ? AND read_at IS NOT NULL
    `, [conversation_id, user_id]);

    const messageIds = updatedMessages.map(m => m.id);

    // 🧠 Obtener el receptor (o sea el otro usuario en la conversación)
    const [conv] = await db.query(`
      SELECT emisor_id, receptor_id FROM chat_conversations WHERE id = ?
    `, [conversation_id]);

    if (conv.length) {
      const { emisor_id, receptor_id } = conv[0];
      const receptorSocketId = emisor_id === user_id ? receptor_id : emisor_id;

      // 📡 Emitir el evento al otro usuario
      io.to(`user_${receptorSocketId}`).emit('messages_read', {
        message_ids: messageIds
      });
    }

    res.json({ success: true });
  } catch (error) {
    console.error("❌ Error al marcar como leído:", error);
    res.status(500).json({
      success: false,
      error: "Error interno"
    });
  }
});



// ==============================================
// MIDDLEWARES DE ERROR (DEBEN IR AL FINAL)
// ==============================================

// Middleware para manejar rutas no encontradas
restApp.use((req, res) => {
  res.status(404).json({
    success: false,
    error: 'Endpoint no encontrado',
    requestedPath: req.path,
    suggestion: 'Prueba con /api/conversations/1 o /api/routes'
  });
});

// Middleware para manejar errores
restApp.use((err, req, res, next) => {
  console.error('Error interno:', err);
  res.status(500).json({
    success: false,
    error: 'Error interno del servidor'
  });
});

// ==============================================
// CONFIGURACIÓN DE WEBSOCKET (opcional)
// ==============================================

const io = new Server(restServer, {  
  cors: {
    origin: "http://localhost:3000",
    methods: ["GET", "POST"],
    credentials: true
  },
  transports: ['websocket', 'polling'] 
});

io.on('connection', async (socket) => {
  console.log('✅ Cliente WebSocket conectado:', socket.id);
  
  // 1. Verificar y obtener userId desde el handshake
  const { userId } = socket.handshake.query;
  if (!userId) return socket.disconnect();

  // 2. Registrar usuario como conectado en Redis
  try {
    await redisClient.sAdd('onlineUsers', userId);
    await redisClient.hSet('userSockets', userId, socket.id); // Guardar relación userId → socket.id
    
    // 3. Notificar a todos los usuarios actualizados
    const onlineUsers = await redisClient.sMembers('onlineUsers');
    io.emit('onlineUsers', onlineUsers);
    
    console.log(`👤 Usuario ${userId} conectado. Online: ${onlineUsers.length}`);
  } catch (err) {
    console.error('Error en Redis al conectar:', err);
    return socket.disconnect();
  }

  // 4. Emitir estado de conexión al cliente
  socket.emit('connection_status', { 
    status: 'connected',
    socketId: socket.id,
    userId,
    timestamp: new Date().toISOString()
  });

  // 5. Manejar suscripción a canales privados
  socket.on('subscribe', (channel) => {
    socket.join(`user_${channel}`);
    console.log(`👂 Usuario ${userId} suscrito a ${channel}`);
  });

  // 6. Manejar envío de mensajes (con verificación de usuario conectado)
  socket.on('send_message', async (data, callback) => {
    try {

      // Verificar si el receptor está conectado
      const isReceiverOnline = await redisClient.sIsMember('onlineUsers', String(data.receptor_id));
      
      // 1. Guardar mensaje en DB
      const [message] = await db.query(`
        INSERT INTO chat_messages 
        (conversation_id, emisor_id, content, created_at) 
        VALUES (?, ?, ?, NOW())
      `, [data.conversation_id, data.emisor_id, data.content]);

      // 2. Obtener datos completos del mensaje
      const [completeMessage] = await db.query(`
        SELECT m.*, u.nombre as emisor_nombre 
        FROM chat_messages m
        JOIN users u ON m.emisor_id = u.id
        WHERE m.id = ?
          AND m.deleted_at IS NULL
      `, [message.insertId]);
      
      const mensajeFormateado = formatMessage(completeMessage[0]);

      io.to(`user_${data.receptor_id}`).emit('new_message', mensajeFormateado);
      io.to(`user_${data.emisor_id}`).emit('new_message', mensajeFormateado);

      io.to(`user_${data.receptor_id}`).emit('update_conversations');

      // 4. Confirmar al emisor
      callback({ 
        status: 'success', 
        message: completeMessage[0],
        receiverOnline: isReceiverOnline 
      });
      
    } catch (error) {
      console.error('Error al enviar mensaje:', error);
      callback({ status: 'error', error: error.message });
    }
  });  

  // 7. Manejar lectura de mensajes
  socket.on('messages_read', async ({ conversation_id, user_id }) => {
    try {
      // 1. Marcar en DB los mensajes como leídos (los del otro usuario)
      await db.query(`
        UPDATE chat_messages 
        SET read_at = NOW()
        WHERE conversation_id = ? AND emisor_id != ? AND read_at IS NULL
      `, [conversation_id, user_id]);
  
      // 2. Obtener IDs de los mensajes actualizados
      const [leidos] = await db.query(`
        SELECT id FROM chat_messages 
        WHERE conversation_id = ? AND emisor_id != ? AND read_at IS NOT NULL
      `, [conversation_id, user_id]);
  
      const message_ids = leidos.map(m => m.id);
  
      // 3. Emitir evento al emisor para que marque como leídos
      const [conv] = await db.query(`
        SELECT emisor_id, receptor_id FROM chat_conversations 
        WHERE id = ?
      `, [conversation_id]);
  
      const emisorId = conv[0].emisor_id === user_id ? conv[0].receptor_id : conv[0].emisor_id;
  
      io.to(`user_${emisorId}`).emit('messages_read', { 
        message_ids, 
        conversation_id 
      });
      
    } catch (err) {
      console.error('❌ Error al manejar messages_read:', err);
    }
  });  
  
  // 9. Revisar si un usuario esta online
  socket.on('get_online_users', async () => {
    try {
      const onlineUsers = await redisClient.sMembers('onlineUsers');
      socket.emit('onlineUsers', onlineUsers); // Solo a quien lo pidió
    } catch (err) {
      console.error('Error al obtener usuarios online:', err);
    }
  });

  // 10. Manejar desconexión
  socket.on('disconnect', async () => {
    try {
      await redisClient.sRem('onlineUsers', userId);
      await redisClient.hDel('userSockets', userId);
      
      const onlineUsers = await redisClient.sMembers('onlineUsers');
      io.emit('onlineUsers', onlineUsers);
      
      console.log(`❌ Usuario ${userId} desconectado. Online: ${onlineUsers.length}`);
    } catch (err) {
      console.error('Error en Redis al desconectar:', err);
    }
  });

  // 8. Heartbeat para detectar conexiones caídas
  let heartbeatInterval = setInterval(async () => {
    try {
      await redisClient.sAdd('onlineUsers', userId); // Renovar registro
    } catch (err) {
      clearInterval(heartbeatInterval);
    }
  }, 25000); // Cada 25 segundos

  socket.on('disconnect', () => {
    clearInterval(heartbeatInterval);
  });
});

// ==============================================
// INICIAR SERVIDORES
// ==============================================

restServer.listen(REST_PORT, '0.0.0.0', () => {
  console.log(`🚀 Servidor combinado (REST + WS) en puerto ${REST_PORT}`);
  console.log(`📡 Endpoints REST disponibles:`);
  console.log(`- http://localhost:${REST_PORT}/api/conversations/:user_id`);
  console.log(`🎧 WebSocket disponible en ws://localhost:${REST_PORT}`);
});