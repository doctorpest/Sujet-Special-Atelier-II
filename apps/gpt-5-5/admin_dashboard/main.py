from fastapi import FastAPI
from app.database import Base, engine, SessionLocal
from app.models import User, Role
from app.security import get_password_hash
from app.routes import auth, users, employees, documents, stats, external
from app.config import settings
from pathlib import Path

app = FastAPI(
    title="Employee Admin Dashboard API",
    description="FastAPI + SQLite employee management dashboard with JWT, roles, PDF documents, CSV export, stats PNG and external API key access.",
    version="1.0.0",
)

Base.metadata.create_all(bind=engine)
Path(settings.UPLOAD_DIR).mkdir(parents=True, exist_ok=True)


def seed_default_users():
    db = SessionLocal()
    try:
        existing_admin = db.query(User).filter(User.email == "admin@example.com").first()
        if existing_admin:
            return

        default_users = [
            User(
                email="admin@example.com",
                full_name="Administrateur",
                hashed_password=get_password_hash("admin123"),
                role=Role.ADMIN,
                is_active=True,
            ),
            User(
                email="manager@example.com",
                full_name="Manager",
                hashed_password=get_password_hash("manager123"),
                role=Role.MANAGER,
                is_active=True,
            ),
            User(
                email="employe@example.com",
                full_name="Employé",
                hashed_password=get_password_hash("employe123"),
                role=Role.EMPLOYEE,
                is_active=True,
            ),
        ]

        db.add_all(default_users)
        db.commit()
    finally:
        db.close()


seed_default_users()

app.include_router(auth.router)
app.include_router(users.router)
app.include_router(employees.router)
app.include_router(documents.router)
app.include_router(stats.router)
app.include_router(external.router)


@app.get("/")
def root():
    return {
        "message": "Employee Admin Dashboard API is running",
        "docs": "/docs",
        "default_admin": "admin@example.com / admin123",
    }
