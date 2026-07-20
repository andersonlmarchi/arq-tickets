import logging
from typing import Any

from fastapi import FastAPI, HTTPException, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse

from app.api.routes import catalogo, health
from app.core.config import settings
from app.core.logging import setup_logging
from app.middleware.correlation import CorrelationIdMiddleware, get_correlation_id

setup_logging(settings.log_level)
logger = logging.getLogger(__name__)

app = FastAPI(title="catalog-service", version="0.1.0")
app.add_middleware(CorrelationIdMiddleware)
app.include_router(health.router)
app.include_router(catalogo.router)


def _correlation_from_request(request: Request) -> str:
    return getattr(request.state, "correlation_id", get_correlation_id())


def _error_body(code: str, message: str, correlation_id: str) -> dict[str, Any]:
    return {"code": code, "message": message, "correlation_id": correlation_id}


@app.exception_handler(HTTPException)
async def http_exception_handler(request: Request, exc: HTTPException) -> JSONResponse:
    correlation_id = _correlation_from_request(request)
    if isinstance(exc.detail, dict):
        body = {**exc.detail, "correlation_id": exc.detail.get("correlation_id", correlation_id)}
    else:
        body = _error_body("HTTP_ERROR", str(exc.detail), correlation_id)
    return JSONResponse(status_code=exc.status_code, content=body, headers={"X-Correlation-Id": correlation_id})


@app.exception_handler(RequestValidationError)
async def validation_exception_handler(request: Request, exc: RequestValidationError) -> JSONResponse:
    correlation_id = _correlation_from_request(request)
    return JSONResponse(
        status_code=400,
        content=_error_body("VALIDATION_ERROR", "Payload invalido.", correlation_id),
        headers={"X-Correlation-Id": correlation_id},
    )


@app.exception_handler(Exception)
async def unhandled_exception_handler(request: Request, exc: Exception) -> JSONResponse:
    correlation_id = _correlation_from_request(request)
    logger.exception("unhandled_error", extra={"correlation_id": correlation_id})
    return JSONResponse(
        status_code=500,
        content=_error_body("INTERNAL_ERROR", "Erro interno.", correlation_id),
        headers={"X-Correlation-Id": correlation_id},
    )
