from fastapi import APIRouter, Depends, HTTPException, Query, Header
from sqlalchemy.orm import Session
from typing import List, Optional
from dependencies import get_db, get_api_key
from schemas import EmployeePublic
from models import User

router = APIRouter()

@router.get("/employees", response_model=List[EmployeePublic])
def external_employee_list(
    department: Optional[str] = Query(None),
    api_key: str = Depends(get_api_key),  # validation clé via dépendance
    db: Session = Depends(get_db)
):
    query = db.query(User)
    if department:
        query = query.filter(User.department == department)
    return query.all()