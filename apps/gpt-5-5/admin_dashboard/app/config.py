import os


class Settings:
    SECRET_KEY: str = os.getenv("SECRET_KEY", "change-this-secret-key-in-production")
    ALGORITHM: str = os.getenv("ALGORITHM", "HS256")
    ACCESS_TOKEN_EXPIRE_MINUTES: int = int(os.getenv("ACCESS_TOKEN_EXPIRE_MINUTES", "120"))
    EXTERNAL_API_KEY: str = os.getenv("EXTERNAL_API_KEY", "change-this-api-key")
    DATABASE_URL: str = os.getenv("DATABASE_URL", "sqlite:///./employees.db")
    UPLOAD_DIR: str = os.getenv("UPLOAD_DIR", "uploads")


settings = Settings()
