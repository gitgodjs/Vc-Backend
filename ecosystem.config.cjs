module.exports = {
  apps: [
    // WebSocket del chat
    {
      name: 'chat-ws',
      cwd: '/home/vc-backend/Vc-Chat-Service',
      script: 'server.js',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '250M',
      env: {
        NODE_ENV: 'production',
        PORT: 6001,
      },
      error_file: '/root/.pm2/logs/chat-ws-error.log',
      out_file: '/root/.pm2/logs/chat-ws-out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss',
    },

    // Frontend de desarrollo
    {
      name: 'dev-frontend',
      cwd: '/home/vintageclothesarg-dev',
      script: 'bash',
      args: '-c "\
        echo [dev-frontend] Iniciando; \
        if [ ! -d .next ]; then \
          echo [dev-frontend] No existe .next, corriendo build...; \
          npm install && npm run build || { echo [dev-frontend] Falló el build; exit 1; }; \
        fi; \
        echo [dev-frontend] Build listo. Ejecutando next dev; \
        npm run dev"',
      env: {
        NODE_ENV: 'development',
        PORT: 3006,
      },
      autorestart: true,
      watch: false,
      max_restarts: 3,
      restart_delay: 10000,
    },

    // Frontend de producción
    {
      name: 'frontend',
      cwd: '/home/vc-frontend',
      script: 'bash',
      args: '-c "\
        echo [frontend] Iniciando; \
        if [ ! -d .next ]; then \
          echo [frontend] No existe .next, corriendo build...; \
          npm install && npm run build || { echo [frontend] Falló el build; exit 1; }; \
        fi; \
        echo [frontend] Build listo. Ejecutando next start; \
        npm run start"',
      env: {
        NODE_ENV: 'production',
        PORT: 3002,
      },
      autorestart: true,
      watch: false,
      max_restarts: 3,
      restart_delay: 10000,
    },

    // Webhook listener
    {
      name: 'webhook-listener',
      cwd: '/home/webhook-listener',
      script: 'server.js',
      instances: 1,
      autorestart: true,
      watch: false,
      env: {
        PORT: 3030,
        NODE_ENV: 'production',
      },
    },
  ],
};
