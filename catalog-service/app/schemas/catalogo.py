from pydantic import BaseModel, Field


class EventoOut(BaseModel):
    id: int
    nome: str
    estoque: int

    model_config = {"from_attributes": True}


class EventoListResponse(BaseModel):
    data: list[EventoOut]


class ReservaRequest(BaseModel):
    evento_id: int = Field(gt=0)
    quantidade: int = Field(gt=0)


class DevolverRequest(BaseModel):
    evento_id: int = Field(gt=0)
    quantidade: int = Field(gt=0)


class ReservaResponse(BaseModel):
    evento_id: int
    quantidade_reservada: int
    estoque_restante: int
    correlation_id: str


class DevolverResponse(BaseModel):
    evento_id: int
    quantidade_devolvida: int
    estoque_restante: int
    correlation_id: str
