from database import SessionLocal, engine, Base
from models import User, Role
from routers.auth import get_password_hash

def create_admin():
    Base.metadata.create_all(bind=engine)
    db = SessionLocal()
    try:
        existing = db.query(User).filter(User.role == Role.admin).first()
        if existing:
            print("Un administrateur existe déjà.")
            return
        admin = User(
            username="admin",
            hashed_password=get_password_hash("admin123"),
            role=Role.admin,
            department="Administration",
            email="admin@example.com",
            full_name="Admin User"
        )
        db.add(admin)
        db.commit()
        print("Administrateur créé : admin / admin123")
    finally:
        db.close()

if __name__ == "__main__":
    create_admin()