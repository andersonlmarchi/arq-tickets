from urllib.parse import quote_plus

from pydantic import model_validator
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    database_url: str = ""
    postgres_catalog_user: str = "catalog"
    postgres_catalog_password: str = "catalog_secret"
    postgres_catalog_db: str = "catalog_db"
    postgres_catalog_host: str = "postgres-catalog"
    postgres_catalog_port: int = 5432

    api_key: str = "dev-shared-key-change-me"
    log_level: str = "INFO"

    @model_validator(mode="after")
    def assemble_database_url(self) -> "Settings":
        user = quote_plus(self.postgres_catalog_user)
        password = quote_plus(self.postgres_catalog_password)
        self.database_url = (
            f"postgresql+psycopg2://{user}:{password}@"
            f"{self.postgres_catalog_host}:{self.postgres_catalog_port}/{self.postgres_catalog_db}"
        )
        return self


settings = Settings()
