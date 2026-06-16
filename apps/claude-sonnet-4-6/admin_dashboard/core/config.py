import os
from dotenv import load_dotenv

load_dotenv()

SECRET_KEY = os.getenv("SECRET_KEY", "super-secret-dev-key-change-in-prod")
ALGORITHM = "HS256"
ACCESS_TOKEN_EXPIRE_MINUTES = 60

DATABASE_URL = "sqlite+aiosqlite:///./hr_dashboard.db"

UPLOAD_DIR = "uploads"
os.makedirs(UPLOAD_DIR, exist_ok=True)

# API keys allowed for external service access
EXTERNAL_API_KEYS = set(
    os.getenv("EXTERNAL_API_KEYS", "ext-key-demo-1234").split(",")
)
