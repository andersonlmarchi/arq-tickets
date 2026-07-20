#!/bin/sh
set -e

echo "Aguardando PostgreSQL..."
python <<'PY'
import sys
import time

from sqlalchemy import create_engine, text

from app.core.config import settings

for attempt in range(60):
    try:
        engine = create_engine(settings.database_url, pool_pre_ping=True)
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        print("PostgreSQL disponivel.")
        sys.exit(0)
    except Exception:
        time.sleep(1)

print("Timeout aguardando PostgreSQL.", file=sys.stderr)
sys.exit(1)
PY

alembic upgrade head
python -m app.db.seed
exec uvicorn app.main:app --host 0.0.0.0 --port 8000
