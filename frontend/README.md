# frontend

React + Vite + TypeScript. Consome apenas o **sales-service** (`VITE_SALES_API_URL`).

## Local (sem Docker)

```bash
cp .env.example .env
npm install
npm run dev
```

Abra `http://localhost:5173` com o sales-service em `http://localhost:8080`.

## Docker

Servico `frontend` no compose na raiz (porta 5173). A API e chamada pelo browser no host, entao a URL padrao continua `http://localhost:8080`.
