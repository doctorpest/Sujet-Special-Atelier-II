from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List
from dependencies import get_db, role_required
from models import APIKey, Role
from schemas import APIKeyCreate, APIKeyOut
import secrets

router = APIRouter()

@router.get("/", response_model=List[APIKeyOut])
def list_api_keys(
    current_user: User = Depends(role_required([Role.admin])),
    db: Session = Depends(get_db)
):
    return db.query(APIKey).all()

@router.post("/", response_model=APIKeyOut, status_code=201)
def create_api_key(
    key_data: APIKeyCreate,
    current_user: User = Depends(role_required([Role.admin])),
    db: Session = Depends(get_db)
):
    api_key = APIKey(
        key=secrets.token_urlsafe(32),
        description=key_data.description
    )
    db.add(api_key)
    db.commit()
    db.refresh(api_key)
    return api_key

@router.put("/{key_id}/deactivate", response_model=APIKeyOut)
def deactivate_api_key(
    key_id: int,
    current_user: User = Depends(role_required([Role.admin])),
    db: Session = Depends(get_db)
):
    key = db.query(APIKey).filter(APIKey.id == key_id).first()
    if not key:
        raise HTTPException(status_code=404, detail="API Key not found")
    key.is_active = False
    db.commit()
    db.refresh(key)
    return key