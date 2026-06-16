from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from app.dependencies import get_db, verify_external_api_key
from app.models import Employee
from app.schemas import EmployeeRead

router = APIRouter(prefix="/external", tags=["API externe"])


@router.get(
    "/employees",
    response_model=list[EmployeeRead],
    dependencies=[Depends(verify_external_api_key)],
)
def external_employee_list(db: Session = Depends(get_db)):
    return db.query(Employee).filter(Employee.is_active == True).order_by(Employee.last_name).all()
