from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from app.dependencies import get_db, require_roles
from app.models import User, Role
from app.schemas import UserCreate, UserRead
from app.security import get_password_hash

router = APIRouter(prefix="/users", tags=["Utilisateurs"])


@router.post("/", response_model=UserRead, dependencies=[Depends(require_roles(Role.ADMIN))])
def create_user(payload: UserCreate, db: Session = Depends(get_db)):
    existing = db.query(User).filter(User.email == payload.email).first()
    if existing:
        raise HTTPException(status_code=400, detail="Email déjà utilisé")

    user = User(
        email=payload.email,
        full_name=payload.full_name,
        hashed_password=get_password_hash(payload.password),
        role=payload.role,
        is_active=payload.is_active,
    )
    db.add(user)
    db.commit()
    db.refresh(user)
    return user


@router.get("/", response_model=list[UserRead], dependencies=[Depends(require_roles(Role.ADMIN))])
def list_users(db: Session = Depends(get_db)):
    return db.query(User).order_by(User.created_at.desc()).all()
