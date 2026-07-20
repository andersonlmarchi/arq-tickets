import uuid
from contextvars import ContextVar
from typing import Callable

from starlette.middleware.base import BaseHTTPMiddleware
from starlette.requests import Request
from starlette.responses import Response

correlation_id_ctx: ContextVar[str] = ContextVar("correlation_id", default="")


def get_correlation_id() -> str:
    value = correlation_id_ctx.get()
    if value:
        return value
    return str(uuid.uuid4())


class CorrelationIdMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next: Callable) -> Response:
        incoming = request.headers.get("X-Correlation-Id")
        correlation_id = incoming.strip() if incoming else str(uuid.uuid4())
        token = correlation_id_ctx.set(correlation_id)
        request.state.correlation_id = correlation_id
        try:
            response = await call_next(request)
        finally:
            correlation_id_ctx.reset(token)
        response.headers["X-Correlation-Id"] = correlation_id
        return response
