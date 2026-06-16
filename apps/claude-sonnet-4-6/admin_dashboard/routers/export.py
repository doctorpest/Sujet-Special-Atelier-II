import io
from typing import Optional

import matplotlib
matplotlib.use("Agg")  # headless — no display required
import matplotlib.pyplot as plt
import pandas as pd
from fastapi import APIRouter, Depends, Query
from fastapi.responses import StreamingResponse
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from core.database import get_db
from core.security import require_role
from models.employee import Employee, Role

router = APIRouter(tags=["Export & Statistiques"])

_admin_or_manager = require_role(Role.admin, Role.manager)


# ── CSV Export ────────────────────────────────────────────────────────────────

@router.get(
    "/export/csv",
    summary="Export CSV des employés (filtrable par département)",
    response_class=StreamingResponse,
)
async def export_csv(
    department: Optional[str] = Query(None, description="Filtrer par département"),
    active_only: bool = Query(True),
    db: AsyncSession = Depends(get_db),
    _: Employee = Depends(_admin_or_manager),
):
    q = select(Employee)
    if department:
        q = q.where(Employee.department == department)
    if active_only:
        q = q.where(Employee.is_active == True)  # noqa: E712

    result = await db.execute(q.order_by(Employee.department, Employee.last_name))
    employees = result.scalars().all()

    data = [
        {
            "ID": e.id,
            "Prénom": e.first_name,
            "Nom": e.last_name,
            "Email": e.email,
            "Département": e.department,
            "Poste": e.position or "",
            "Rôle": e.role.value,
            "Actif": "Oui" if e.is_active else "Non",
            "Créé le": e.created_at.strftime("%Y-%m-%d"),
        }
        for e in employees
    ]

    df = pd.DataFrame(data)
    buffer = io.StringIO()
    df.to_csv(buffer, index=False, encoding="utf-8-sig")  # utf-8-sig for Excel compat
    buffer.seek(0)

    filename = f"employes_{department or 'tous'}.csv"
    return StreamingResponse(
        io.BytesIO(buffer.getvalue().encode("utf-8-sig")),
        media_type="text/csv",
        headers={"Content-Disposition": f'attachment; filename="{filename}"'},
    )


# ── Statistics PNG ────────────────────────────────────────────────────────────

@router.get(
    "/stats/chart",
    summary="Graphique statistiques (PNG)",
    response_class=StreamingResponse,
)
async def stats_chart(
    chart_type: str = Query("department", enum=["department", "role", "activity"]),
    db: AsyncSession = Depends(get_db),
    _: Employee = Depends(_admin_or_manager),
):
    result = await db.execute(select(Employee))
    employees = result.scalars().all()

    if not employees:
        # Return a simple "no data" image
        fig, ax = plt.subplots(figsize=(6, 3))
        ax.text(0.5, 0.5, "Aucune donnée disponible", ha="center", va="center", fontsize=14)
        ax.axis("off")
    elif chart_type == "department":
        df = pd.DataFrame([{"Département": e.department} for e in employees])
        counts = df["Département"].value_counts()
        fig, ax = plt.subplots(figsize=(8, 5))
        bars = ax.bar(counts.index, counts.values, color="#4A90D9", edgecolor="white")
        ax.bar_label(bars, padding=3, fontsize=10)
        ax.set_title("Effectif par département", fontsize=14, pad=15)
        ax.set_xlabel("Département")
        ax.set_ylabel("Nombre d'employés")
        ax.spines[["top", "right"]].set_visible(False)
        plt.xticks(rotation=30, ha="right")
        plt.tight_layout()

    elif chart_type == "role":
        role_counts = {}
        for e in employees:
            role_counts[e.role.value] = role_counts.get(e.role.value, 0) + 1
        colors = {"employee": "#4A90D9", "manager": "#F5A623", "admin": "#D0021B"}
        fig, ax = plt.subplots(figsize=(6, 6))
        wedges, texts, autotexts = ax.pie(
            role_counts.values(),
            labels=role_counts.keys(),
            autopct="%1.1f%%",
            colors=[colors.get(k, "#888") for k in role_counts.keys()],
            startangle=90,
        )
        for t in autotexts:
            t.set_fontsize(11)
        ax.set_title("Répartition par rôle", fontsize=14, pad=15)
        plt.tight_layout()

    else:  # activity
        active = sum(1 for e in employees if e.is_active)
        inactive = len(employees) - active
        fig, ax = plt.subplots(figsize=(6, 5))
        bars = ax.bar(["Actifs", "Inactifs"], [active, inactive], color=["#27AE60", "#E74C3C"])
        ax.bar_label(bars, padding=3, fontsize=12)
        ax.set_title("Employés actifs vs inactifs", fontsize=14, pad=15)
        ax.set_ylabel("Nombre")
        ax.spines[["top", "right"]].set_visible(False)
        plt.tight_layout()

    buf = io.BytesIO()
    plt.savefig(buf, format="png", dpi=120, bbox_inches="tight")
    plt.close("all")
    buf.seek(0)

    return StreamingResponse(buf, media_type="image/png")


# ── JSON stats summary ────────────────────────────────────────────────────────

@router.get("/stats/summary", summary="Résumé statistique JSON")
async def stats_summary(
    db: AsyncSession = Depends(get_db),
    _: Employee = Depends(_admin_or_manager),
):
    result = await db.execute(select(Employee))
    employees = result.scalars().all()

    by_dept: dict = {}
    by_role: dict = {}
    for e in employees:
        by_dept[e.department] = by_dept.get(e.department, 0) + 1
        by_role[e.role.value] = by_role.get(e.role.value, 0) + 1

    return {
        "total": len(employees),
        "active": sum(1 for e in employees if e.is_active),
        "inactive": sum(1 for e in employees if not e.is_active),
        "by_department": by_dept,
        "by_role": by_role,
    }
