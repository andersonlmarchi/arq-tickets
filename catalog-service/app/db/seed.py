"""Seed de eventos para demo do lab (ids fixos para testes manuais)."""

from sqlalchemy import select, text
from sqlalchemy.orm import Session

from app.db.session import SessionLocal
from app.models.evento import Evento

SEED_EVENTOS: list[tuple[int, str, int]] = [
    (1, "Show Rock", 100),
    (2, "Festival Jazz", 50),
    (3, "Teatro Clássico", 5),
]


def run_seed(db: Session) -> None:
    existing = db.scalar(select(Evento.id).limit(1))
    if existing is not None:
        return

    for evento_id, nome, estoque in SEED_EVENTOS:
        db.add(Evento(id=evento_id, nome=nome, estoque=estoque))
    db.commit()

    db.execute(
        text(
            "SELECT setval(pg_get_serial_sequence('eventos', 'id'), "
            "(SELECT COALESCE(MAX(id), 1) FROM eventos))"
        )
    )
    db.commit()


def main() -> None:
    db = SessionLocal()
    try:
        run_seed(db)
    finally:
        db.close()


if __name__ == "__main__":
    main()
