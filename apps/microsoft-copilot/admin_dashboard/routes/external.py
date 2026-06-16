# routes/external.py
from fastapi import APIRouter, Depends, HTTPException, Query
from models import SessionLocal, User
from deps import get_db
from typing import List

router = APIRouter()

@router.get("/list")
def external_list(key: str = Query(...), db=Depends(get_db)):
    user = db.query(User).filter(User.api_key == key).first()
    if not user:
        raise HTTPException(401, "Invalid API key")
    # return basic list of employees
    users = db.query(User).all()
    return [
        {
            "id": u.id,
            "email": u.email,
            "full_name": u.full_name,
            "role": u.role,
            "department": u.department
        } for u in users
    ]
