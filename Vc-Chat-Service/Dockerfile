FROM node:18.20.2

WORKDIR /app  

COPY package*.json ./
RUN npm install
COPY . .  

EXPOSE 3001 3002
CMD ["node", "server.js"]