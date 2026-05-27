FROM node:20-bullseye-slim

WORKDIR /usr/src/app

# Installer dépendances système nécessaires à Prisma (OpenSSL, build tools)
RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		ca-certificates \
		openssl \
		build-essential \
		python3 \
	&& rm -rf /var/lib/apt/lists/*

# Copie les fichiers de l'API et installe les dépendances
COPY package*.json ./
RUN npm install

# Copie le reste du code de l'API
COPY . .

EXPOSE 3000

CMD [ "node", "src/server.js" ]