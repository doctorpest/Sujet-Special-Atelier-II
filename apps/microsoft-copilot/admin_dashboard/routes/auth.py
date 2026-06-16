# routes/auth.py
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.exc import IntegrityError
from models import User, SessionLocal
from schemas import UserCreate, Token, Login, UserOut
from deps import get_password_hash, verify_password, create_access_token, get_db
import secrets

router = APIRouter()

@router.post("/register", response_model=UserOut)
def register(payload: UserCreate, db=Depends(get_db)):
    user = User(
        email=payload.email,
        full_name=payload.full_name,
        hashed_password=get_password_hash(payload.password),
        role=payload.role,
        department=payload.department,
        api_key=secrets.token_urlsafe(32)
    )
    db.add(user)
    try:
        db.commit()
        db.refresh(user)
    except IntegrityError:
        db.rollback()
        raise HTTPException(status_code=400, detail="Email already registered")
    return user

@router.post("/login", response_model=Token)
def login(payload: Login, db=Depends(get_db)):
    user = db.query(User).filter(User.email == payload.email).first()
    if not user or not verify_password(payload.password, user.hashed_password):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Invalid credentials")
    token = create_access_token({"sub": user.email, "role": user.role})
    return {"access_token": token, "token_type": "bearer"}
