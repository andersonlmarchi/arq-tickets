from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    database_url: str = "postgresql+psycopg2://catalog:catalog_secret@localhost:5434/catalog_db"
    api_key: str = "dev-shared-key-change-me"
    log_level: str = "INFO"


settings = Settings()
