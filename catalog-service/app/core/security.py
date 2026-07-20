from fastapi import Header, HTTPException, status

from app.core.config import settings
from app.middleware.correlation import get_correlation_id


def verify_api_key(x_api_key: str | None = Header(default=None, alias="X-API-Key")) -> None:
    if not x_api_key or x_api_key != settings.api_key:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail={
                "code": "UNAUTHORIZED",
                "message": "API Key invalida ou ausente.",
                "correlation_id": get_correlation_id(),
            },
        )
