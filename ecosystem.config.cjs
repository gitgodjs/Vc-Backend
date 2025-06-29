module.exports = {
    apps: [
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
      {
        name: 'dev-frontend',
        cwd: '/home/vintageclothesarg-dev',
        script: 'npm',
        args: 'run start',
        env: {
          NODE_ENV: 'development',
          PORT: 3000,
        },
        autorestart: true,
        watch: false,
      },
      {
        name: 'frontend',
        cwd: '/home/vc-frontend',
        script: 'npm',
        args: 'run start',
        env: {
          NODE_ENV: 'production',
          PORT: 3000,
        },
        autorestart: true,
        watch: false,
      },
      {
        name: 'webhook-listener',
        cwd: '/home/webhook-listener',
        script: 'index.js',
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
  