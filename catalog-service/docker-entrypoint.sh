#!/bin/sh
set -e

echo "Aguardando PostgreSQL..."
python <<'PY'
import sys
import time

from sqlalchemy import create_engine, text

from app.core.config import settings

last_error = None
for attempt in range(60):
    try:
        engine = create_engine(settings.database_url, pool_pre_ping=True)
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        print("PostgreSQL disponivel.")
        sys.exit(0)
    except Exception as exc:
        last_error = exc
        time.sleep(1)

print("Timeout aguardando PostgreSQL.", file=sys.stderr)
if last_error is not None:
    print(f"Ultimo erro: {last_error!r}", file=sys.stderr)
print(
    "Confira POSTGRES_CATALOG_USER/PASSWORD/DB no .env (mesmos valores do container postgres-catalog).",
    file=sys.stderr,
)
sys.exit(1)
PY

echo "Rodando migrations..."
alembic upgrade head

echo "Rodando seed..."
python -m app.db.seed

echo "Iniciando uvicorn..."
exec uvicorn app.main:app --host 0.0.0.0 --port 8000
