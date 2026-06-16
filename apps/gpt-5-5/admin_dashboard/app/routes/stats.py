import io
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from fastapi import APIRouter, Depends
from fastapi.responses import Response
from sqlalchemy.orm import Session
from sqlalchemy import func
from app.dependencies import get_db, require_roles
from app.models import Employee, Role

router = APIRouter(prefix="/stats", tags=["Statistiques"])


def fig_to_png_response(fig) -> Response:
    buffer = io.BytesIO()
    fig.tight_layout()
    fig.savefig(buffer, format="png")
    plt.close(fig)
    buffer.seek(0)
    return Response(content=buffer.getvalue(), media_type="image/png")


@router.get("/employees-by-department", dependencies=[Depends(require_roles(Role.MANAGER, Role.ADMIN))])
def employees_by_department(db: Session = Depends(get_db)):
    rows = (
        db.query(Employee.department, func.count(Employee.id))
        .group_by(Employee.department)
        .order_by(Employee.department)
        .all()
    )

    departments = [row[0] for row in rows] or ["Aucun"]
    counts = [row[1] for row in rows] or [0]

    fig, ax = plt.subplots(figsize=(9, 5))
    ax.bar(departments, counts)
    ax.set_title("Nombre d'employés par département")
    ax.set_xlabel("Département")
    ax.set_ylabel("Nombre d'employés")
    ax.tick_params(axis="x", rotation=30)

    return fig_to_png_response(fig)


@router.get("/salary-by-department", dependencies=[Depends(require_roles(Role.MANAGER, Role.ADMIN))])
def salary_by_department(db: Session = Depends(get_db)):
    rows = (
        db.query(Employee.department, func.avg(Employee.salary))
        .filter(Employee.salary.isnot(None))
        .group_by(Employee.department)
        .order_by(Employee.department)
        .all()
    )

    departments = [row[0] for row in rows] or ["Aucun"]
    salaries = [float(row[1] or 0) for row in rows] or [0]

    fig, ax = plt.subplots(figsize=(9, 5))
    ax.bar(departments, salaries)
    ax.set_title("Salaire moyen par département")
    ax.set_xlabel("Département")
    ax.set_ylabel("Salaire moyen")
    ax.tick_params(axis="x", rotation=30)

    return fig_to_png_response(fig)
