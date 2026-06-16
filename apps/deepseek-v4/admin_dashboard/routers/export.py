from fastapi import APIRouter, Depends, HTTPException, Query
from fastapi.responses import StreamingResponse
from sqlalchemy.orm import Session
from dependencies import get_db, role_required
from models import User, Role
import csv
import io

router = APIRouter()

@router.get("/employees/csv")
def export_employees_csv(
    department: str = Query(None),
    current_user: User = Depends(role_required([Role.admin, Role.manager])),
    db: Session = Depends(get_db)
):
    query = db.query(User)
    if current_user.role == Role.manager:
        # Forcer le département du manager
        department = current_user.department
    if department:
        query = query.filter(User.department == department)
    employees = query.all()

    output = io.StringIO()
    writer = csv.writer(output)
    writer.writerow(["id", "username", "email", "full_name", "department", "role"])
    for emp in employees:
        writer.writerow([emp.id, emp.username, emp.email, emp.full_name, emp.department, emp.role.value])
    output.seek(0)
    return StreamingResponse(
        iter([output.getvalue()]),
        media_type="text/csv",
        headers={"Content-Disposition": "attachment; filename=employees.csv"}
    )