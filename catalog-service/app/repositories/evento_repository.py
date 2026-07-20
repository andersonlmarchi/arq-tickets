from sqlalchemy import func, select, update
from sqlalchemy.orm import Session

from app.models.evento import Evento


class EventoRepository:
    def list_all(self, db: Session) -> list[Evento]:
        return list(db.scalars(select(Evento).order_by(Evento.id)))

    def get_by_id(self, db: Session, evento_id: int) -> Evento | None:
        return db.get(Evento, evento_id)

    def reservar(self, db: Session, evento_id: int, quantidade: int) -> int | None:
        stmt = (
            update(Evento)
            .where(Evento.id == evento_id, Evento.estoque >= quantidade)
            .values(estoque=Evento.estoque - quantidade, updated_at=func.now())
            .returning(Evento.estoque)
        )
        return db.scalar(stmt)

    def devolver(self, db: Session, evento_id: int, quantidade: int) -> int | None:
        stmt = (
            update(Evento)
            .where(Evento.id == evento_id)
            .values(estoque=Evento.estoque + quantidade, updated_at=func.now())
            .returning(Evento.estoque)
        )
        return db.scalar(stmt)


evento_repository = EventoRepository()
