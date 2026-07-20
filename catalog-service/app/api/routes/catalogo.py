import logging
import time

from fastapi import APIRouter, Depends, HTTPException, Request, status
from sqlalchemy.orm import Session

from app.core.security import verify_api_key
from app.db.session import get_db
from app.middleware.correlation import get_correlation_id
from app.repositories.evento_repository import evento_repository
from app.schemas.catalogo import (
    DevolverRequest,
    DevolverResponse,
    EventoListResponse,
    EventoOut,
    ReservaRequest,
    ReservaResponse,
)

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/api/catalogo", tags=["catalogo"])


@router.get("/eventos", response_model=EventoListResponse, dependencies=[Depends(verify_api_key)])
def list_eventos(db: Session = Depends(get_db)) -> EventoListResponse:
    eventos = evento_repository.list_all(db)
    return EventoListResponse(data=[EventoOut.model_validate(e) for e in eventos])


@router.post("/reservar", response_model=ReservaResponse, dependencies=[Depends(verify_api_key)])
def reservar(body: ReservaRequest, request: Request, db: Session = Depends(get_db)) -> ReservaResponse:
    correlation_id = getattr(request.state, "correlation_id", get_correlation_id())
    started = time.perf_counter()

    estoque_restante = evento_repository.reservar(db, body.evento_id, body.quantidade)
    if estoque_restante is None:
        db.rollback()
        if evento_repository.get_by_id(db, body.evento_id) is None:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail={
                    "code": "EVENTO_NAO_ENCONTRADO",
                    "message": "Evento nao encontrado.",
                    "correlation_id": correlation_id,
                },
            )
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail={
                "code": "ESTOQUE_INSUFICIENTE",
                "message": "Estoque insuficiente.",
                "correlation_id": correlation_id,
            },
        )

    db.commit()
    duration_ms = int((time.perf_counter() - started) * 1000)
    logger.info(
        "reserva_ok",
        extra={
            "correlation_id": correlation_id,
            "evento_id": body.evento_id,
            "quantidade": body.quantidade,
            "duration_ms": duration_ms,
        },
    )
    return ReservaResponse(
        evento_id=body.evento_id,
        quantidade_reservada=body.quantidade,
        estoque_restante=estoque_restante,
        correlation_id=correlation_id,
    )


@router.post("/devolver", response_model=DevolverResponse, dependencies=[Depends(verify_api_key)])
def devolver(body: DevolverRequest, request: Request, db: Session = Depends(get_db)) -> DevolverResponse:
    correlation_id = getattr(request.state, "correlation_id", get_correlation_id())

    estoque_restante = evento_repository.devolver(db, body.evento_id, body.quantidade)
    if estoque_restante is None:
        db.rollback()
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail={
                "code": "EVENTO_NAO_ENCONTRADO",
                "message": "Evento nao encontrado.",
                "correlation_id": correlation_id,
            },
        )

    db.commit()
    logger.info(
        "devolver_ok",
        extra={
            "correlation_id": correlation_id,
            "evento_id": body.evento_id,
            "quantidade": body.quantidade,
        },
    )
    return DevolverResponse(
        evento_id=body.evento_id,
        quantidade_devolvida=body.quantidade,
        estoque_restante=estoque_restante,
        correlation_id=correlation_id,
    )
