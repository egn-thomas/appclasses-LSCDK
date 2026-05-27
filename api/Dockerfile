FROM node:20-alpine

WORKDIR /usr/src/app

# Copie les fichiers de l'API
COPY package*.json ./
RUN npm install

# Copie le reste du code de l'API
COPY . .

EXPOSE 3000

CMD [ "node", "src/server.js" ]