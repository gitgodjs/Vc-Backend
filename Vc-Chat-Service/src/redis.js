

// Vc-Chat-Service/src/redis.js
const { createClient } = require('redis');

const client = createClient({
  url: 'redis://redis:6379'  //"redis" es el nombre del servicio en docker-compose
});

client.on('error', (err) => console.error('Redis error:', err));

(async () => {
  await client.connect();
  console.log('Conectado a Redis');
})();

module.exports = client;