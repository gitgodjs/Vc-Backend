require('dotenv').config();
const express = require('express');
const { createServer } = require('http');
const { Server } = require('socket.io');
const mysql = require('mysql2/promise');
const cors = require('cors');

// Configuración básica
const restApp = express();
const REST_PORT = 3001;
const restServer = createServer(restApp);

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
          AND unread.read_at IS NULL    /* No leídos */
        ) as unread_count
        
      FROM chat_conversations c
      JOIN users u ON 
        CASE 
          WHEN c.emisor_id = ? THEN c.receptor_id 
          ELSE c.emisor_id 
        END = u.id
      LEFT JOIN (
        SELECT 
          m1.*
        FROM chat_messages m1
        INNER JOIN (
          SELECT 
            conversation_id, 
            MAX(created_at) as max_date
          FROM chat_messages
          GROUP BY conversation_id
        ) m2 ON m1.conversation_id = m2.conversation_id AND m1.created_at = m2.max_date
      ) last_msg ON last_msg.conversation_id = c.id
      LEFT JOIN users msg_sender ON last_msg.emisor_id = msg_sender.id
      WHERE c.emisor_id = ? OR c.receptor_id = ?
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
        AND unread.emisor_id != ?  -- mensajes del otro usuario
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
    WHERE c.id = ? AND (c.emisor_id = ? OR c.receptor_id = ?)
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
}


// Agrega este endpoint antes de los middlewares de error
restApp.post('/api/chat/ofertar', async (req, res) => {
  try {
    const { publicacion, mensaje, ofertador } = req.body;
    
    if (!ofertador?.id || !publicacion?.creador?.id) {
      throw new Error('Datos incompletos');
    }

    const emisor_id = ofertador.id;
    const receptor_id = publicacion.creador.id;

    // 1. Buscar o crear conversación
    const [conversation] = await db.query(`
      INSERT INTO chat_conversations (emisor_id, receptor_id, created_at)
      VALUES (?, ?, NOW())
      ON DUPLICATE KEY UPDATE updated_at = NOW()
    `, [emisor_id, receptor_id]);

    let conversation_id = conversation.insertId;
    if (!conversation_id) {
      const [existing] = await db.query(`
        SELECT id FROM chat_conversations 
        WHERE emisor_id = ? AND receptor_id = ?
      `, [emisor_id, receptor_id]);
      conversation_id = existing[0].id;
    }

    // 2. Guardar mensaje
    const [message] = await db.query(`
      INSERT INTO chat_messages 
      (conversation_id, emisor_id, content, created_at)
      VALUES (?, ?, ?, NOW())
    `, [conversation_id, emisor_id, mensaje]);

    // 3. Obtener datos completos del mensaje
    const [messageData] = await db.query(`
      SELECT m.*, u.nombre as emisor_nombre 
      FROM chat_messages m
      JOIN users u ON m.emisor_id = u.id
      WHERE m.id = ?
    `, [message.insertId]);

    // 4. Obtener conversaciones formateadas para ambos usuarios
    const formattedForEmisor = await getFormattedConversation(conversation_id, emisor_id);
    const formattedForReceptor = await getFormattedConversation(conversation_id, receptor_id);

    // 5. Emitir eventos
    // Nuevo mensaje (solo para receptor)
    io.to(`user_${receptor_id}`).emit('new_message', {
      conversation_id,
      emisor_id,
      emisor_nombre: messageData[0].emisor_nombre,
      content: messageData[0].content,
      created_at: messageData[0].created_at
    });

    // Nueva conversación (para ambos usuarios)
    io.to(`user_${emisor_id}`).emit('new_conversation', {
      conversation: formattedForEmisor
    });
    io.to(`user_${receptor_id}`).emit('new_conversation', {
      conversation: formattedForReceptor
    });

    // 6. Respuesta al cliente
    res.json({
      success: true,
      conversation_id,
      message: messageData[0]
    });

  } catch (error) {
    console.error('Error:', error);
    res.status(500).json({
      success: false,
      error: error.message
    });
  }
});

restApp.get('/api/chat/obtenerConversation/:conversation_id', async (req, res) => {
  try {
    const conversationId = parseInt(req.params.conversation_id);
    const userId = parseInt(req.query.user_id); // lo pasás por query string

    if (isNaN(conversationId) || isNaN(userId)) {
      return res.status(400).json({
        success: false,
        error: 'conversation_id y user_id deben ser numéricos'
      });
    }

    // 1. Verificar si el usuario forma parte de la conversación
    const [convCheck] = await db.query(`
      SELECT * FROM chat_conversations 
      WHERE id = ? AND (emisor_id = ? OR receptor_id = ?)
    `, [conversationId, userId, userId]);

    if (!convCheck.length) {
      return res.status(403).json({
        success: false,
        error: 'No tenés permiso para ver esta conversación'
      });
    }

    // 2. Obtener mensajes de la conversación
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
      ORDER BY m.id ASC
    `, [conversationId]);
    
    res.json({
      success: true,
      conversation_id: conversationId,
      messages: messages.map(m => ({
        id: m.id,
        content: m.content,
        created_at: m.created_at,
        read_at: m.read_at,
        emisor: {
          id: m.emisor_id,
          nombre: m.emisor_nombre
        }
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

    await db.query(`
      UPDATE chat_messages
      SET read_at = NOW()
      WHERE conversation_id = ? 
      AND emisor_id != ?
      AND read_at IS NULL
    `, [conversation_id, user_id]);

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

const WS_PORT = process.env.WS_PORT || 3002;
const wsServer = createServer();
const io = new Server(wsServer, {
  cors: {
    origin: "http://localhost:3000",
    methods: ["GET", "POST"],
    credentials: true
  },
  transports: ['websocket']
});

io.on('connection', (socket) => {
  console.log('✅ Cliente WebSocket conectado:', socket.id);
  socket.emit('connection_status', { 
    status: 'connected',
    socketId: socket.id,
    timestamp: new Date().toISOString()
  });

  socket.on('subscribe', (userId) => {
    socket.join(`user_${userId}`);
    console.log(`👂 Usuario ${userId} escuchando updates`);
  });

  socket.on('send_message', async (data, callback) => {
    try {
      // 1. Primero crear en la base de datos
      const [message] = await db.query(`
        INSERT INTO chat_messages 
        (conversation_id, emisor_id, content) 
        VALUES (?, ?, ?)
      `, [data.conversation_id, data.emisor_id, data.content]);
      
      // 2. Obtener datos completos
      const [completeMessage] = await db.query(`
        SELECT m.*, u.nombre as emisor_nombre 
        FROM chat_messages m
        JOIN users u ON m.emisor_id = u.id
        WHERE m.id = ?
      `, [message.insertId]);
      
      // 3. Emitir solo cuando tengamos todos los datos
      const mensajeFormateado = formatMessage(completeMessage[0]);

// Emitir al receptor
      io.to(`user_${data.receptor_id}`).emit('new_message', mensajeFormateado);

      // Emitir también al emisor (por si quiere ver su mensaje reflejado de inmediato)
      io.to(`user_${data.emisor_id}`).emit('new_message', mensajeFormateado);


      // 4. Confirmar al emisor
      callback({ status: 'success', message: completeMessage[0] });
      
    } catch (error) {
      console.error('Error:', error);
      callback({ status: 'error', error: error.message });
    }
  });
  
  socket.on('disconnect', () => {
    console.log('❌ Cliente WebSocket desconectado:', socket.id);
  });
});

// ==============================================
// INICIAR SERVIDORES
// ==============================================

restServer.listen(REST_PORT, '0.0.0.0', () => {
  console.log(`🚀 API REST escuchando en puerto ${REST_PORT}`);
  console.log(`📡 Endpoints disponibles:`);
  console.log(`- http://localhost:${REST_PORT}/api/conversations/:user_id`);
  console.log(`- http://localhost:${REST_PORT}/api/routes`);
});

wsServer.listen(WS_PORT, '0.0.0.0', () => {
  console.log(`🎧 WebSocket escuchando en puerto ${WS_PORT}`);
});