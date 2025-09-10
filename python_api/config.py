import os
from dataclasses import dataclass


@dataclass
class Settings:
    jwt_secret_key: str = os.getenv("JWT_SECRET_KEY", "change-me")
    mysql_host: str = os.getenv("MYSQL_HOST", "localhost")
    mysql_port: int = int(os.getenv("MYSQL_PORT", "3306"))
    mysql_user: str = os.getenv("MYSQL_USER", "root")
    mysql_password: str = os.getenv("MYSQL_PASSWORD", "")
    mysql_db: str = os.getenv("MYSQL_DB", "hr34")


settings = Settings()

