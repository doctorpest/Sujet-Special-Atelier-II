from typing import List, Optional

from fastapi import APIRouter, Depends, Query
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from core.database import get_db
from core.security import verify_api_key
from models.employee import Employee, EmployeeOut

router = APIRouter(prefix="/api/v1", tags=["API Externe (clé API)"])


@router.get(
    "/employees",
    response_model=List[EmployeeOut],
    summary="Liste des employés via clé API (service externe)",
    description=(
        "Endpoint pour services externes. "
        "Authentification via header `X-API-Key`. "
        "Ne retourne que les employés actifs par défaut."
    ),
)
async def external_list_employees(
    department: Optional[str] = Query(None),
    role: Optional[str] = Query(None),
    db: AsyncSession = Depends(get_db),
    _: str = Depends(verify_api_key),
):
    q = select(Employee).where(Employee.is_active == True)  # noqa: E712
    if department:
        q = q.where(Employee.department == department)
    if role:
        from models.employee import Role
        try:
            r = Role(role)
            q = q.where(Employee.role == r)
        except ValueError:
            pass

    result = await db.execute(q.order_by(Employee.last_name))
    return result.scalars().all()
