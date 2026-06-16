import csv
import io
from fastapi import APIRouter, Depends, HTTPException, Query
from fastapi.responses import StreamingResponse
from sqlalchemy.orm import Session
from app.dependencies import get_db, require_roles
from app.models import Employee, Role
from app.schemas import EmployeeCreate, EmployeeRead, EmployeeUpdate

router = APIRouter(prefix="/employees", tags=["Employés"])


@router.post(
    "/",
    response_model=EmployeeRead,
    dependencies=[Depends(require_roles(Role.MANAGER, Role.ADMIN))],
)
def create_employee(payload: EmployeeCreate, db: Session = Depends(get_db)):
    existing = db.query(Employee).filter(Employee.email == payload.email).first()
    if existing:
        raise HTTPException(status_code=400, detail="Email employé déjà utilisé")

    employee = Employee(**payload.model_dump())
    db.add(employee)
    db.commit()
    db.refresh(employee)
    return employee


@router.get("/", response_model=list[EmployeeRead])
def list_employees(
    department: str | None = Query(None),
    is_active: bool | None = Query(None),
    db: Session = Depends(get_db),
    _=Depends(require_roles(Role.EMPLOYEE, Role.MANAGER, Role.ADMIN)),
):
    query = db.query(Employee)

    if department:
        query = query.filter(Employee.department.ilike(f"%{department}%"))

    if is_active is not None:
        query = query.filter(Employee.is_active == is_active)

    return query.order_by(Employee.created_at.desc()).all()


@router.get("/export/csv", dependencies=[Depends(require_roles(Role.MANAGER, Role.ADMIN))])
def export_employees_csv(
    department: str | None = Query(None),
    db: Session = Depends(get_db),
):
    query = db.query(Employee)
    if department:
        query = query.filter(Employee.department.ilike(f"%{department}%"))

    employees = query.order_by(Employee.department, Employee.last_name).all()

    output = io.StringIO()
    writer = csv.writer(output)
    writer.writerow(["id", "first_name", "last_name", "email", "department", "position", "salary", "is_active", "created_at"])

    for emp in employees:
        writer.writerow([
            emp.id,
            emp.first_name,
            emp.last_name,
            emp.email,
            emp.department,
            emp.position,
            emp.salary,
            emp.is_active,
            emp.created_at.isoformat() if emp.created_at else "",
        ])

    output.seek(0)
    filename = "employees.csv" if not department else f"employees_{department}.csv"

    return StreamingResponse(
        iter([output.getvalue()]),
        media_type="text/csv",
        headers={"Content-Disposition": f"attachment; filename={filename}"},
    )


@router.get("/{employee_id}", response_model=EmployeeRead)
def get_employee(
    employee_id: int,
    db: Session = Depends(get_db),
    _=Depends(require_roles(Role.EMPLOYEE, Role.MANAGER, Role.ADMIN)),
):
    employee = db.query(Employee).filter(Employee.id == employee_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="Employé introuvable")
    return employee


@router.put(
    "/{employee_id}",
    response_model=EmployeeRead,
    dependencies=[Depends(require_roles(Role.MANAGER, Role.ADMIN))],
)
def update_employee(employee_id: int, payload: EmployeeUpdate, db: Session = Depends(get_db)):
    employee = db.query(Employee).filter(Employee.id == employee_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="Employé introuvable")

    data = payload.model_dump(exclude_unset=True)

    if "email" in data:
        existing = db.query(Employee).filter(Employee.email == data["email"], Employee.id != employee_id).first()
        if existing:
            raise HTTPException(status_code=400, detail="Email déjà utilisé par un autre employé")

    for key, value in data.items():
        setattr(employee, key, value)

    db.commit()
    db.refresh(employee)
    return employee


@router.delete(
    "/{employee_id}",
    dependencies=[Depends(require_roles(Role.ADMIN))],
)
def delete_employee(employee_id: int, db: Session = Depends(get_db)):
    employee = db.query(Employee).filter(Employee.id == employee_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="Employé introuvable")

    db.delete(employee)
    db.commit()
    return {"message": "Employé supprimé avec succès"}
