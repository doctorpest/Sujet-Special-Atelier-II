from fastapi import APIRouter, Depends, HTTPException
from fastapi.responses import Response
from sqlalchemy.orm import Session
from dependencies import get_db, role_required
from models import User, Role
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
from io import BytesIO
from collections import Counter

router = APIRouter()

@router.get("/employees/by-department")
def employee_stats_by_department(
    current_user: User = Depends(role_required([Role.admin, Role.manager])),
    db: Session = Depends(get_db)
):
    # Compter les employés par département
    employees = db.query(User).all()
    dept_counts = Counter(emp.department for emp in employees)
    departments = list(dept_counts.keys())
    counts = list(dept_counts.values())
    
    fig, ax = plt.subplots(figsize=(8, 5))
    ax.bar(departments, counts, color='skyblue')
    ax.set_xlabel('Department')
    ax.set_ylabel('Number of Employees')
    ax.set_title('Employees per Department')
    plt.xticks(rotation=45, ha='right')
    plt.tight_layout()
    
    buf = BytesIO()
    plt.savefig(buf, format='png')
    plt.close()
    buf.seek(0)
    return Response(content=buf.getvalue(), media_type="image/png")