from typing import List, Optional

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from core.database import get_db
from core.security import get_current_user, require_role, hash_password
from models.employee import Employee, EmployeeCreate, EmployeeOut, EmployeeUpdate, Role

router = APIRouter(prefix="/employees", tags=["Employés"])

_admin_or_manager = require_role(Role.admin, Role.manager)
_admin_only = require_role(Role.admin)


@router.get("/", response_model=List[EmployeeOut], summary="Lister les employés")
async def list_employees(
    department: Optional[str] = Query(None),
    role: Optional[Role] = Query(None),
    active_only: bool = Query(True),
    db: AsyncSession = Depends(get_db),
    _: Employee = Depends(_admin_or_manager),
):
    q = select(Employee)
    if department:
        q = q.where(Employee.department == department)
    if role:
        q = q.where(Employee.role == role)
    if active_only:
        q = q.where(Employee.is_active == True)  # noqa: E712
    result = await db.execute(q.order_by(Employee.last_name))
    return result.scalars().all()


@router.post("/", response_model=EmployeeOut, status_code=201, summary="Créer un employé")
async def create_employee(
    payload: EmployeeCreate,
    db: AsyncSession = Depends(get_db),
    _: Employee = Depends(_admin_only),
):
    existing = await db.execute(select(Employee).where(Employee.email == payload.email))
    if existing.scalar_one_or_none():
        raise HTTPException(status_code=409, detail="Email déjà utilisé")

    emp = Employee(
        first_name=payload.first_name,
        last_name=payload.last_name,
        email=payload.email,
        department=payload.department,
        position=payload.position,
        role=payload.role,
        hashed_password=hash_password(payload.password),
    )
    db.add(emp)
    await db.commit()
    await db.refresh(emp)
    return emp


@router.get("/{employee_id}", response_model=EmployeeOut, summary="Détail d'un employé")
async def get_employee(
    employee_id: int,
    db: AsyncSession = Depends(get_db),
    current_user: Employee = Depends(get_current_user),
):
    # Employees can only see themselves; managers/admins see all
    if current_user.role == Role.employee and current_user.id != employee_id:
        raise HTTPException(status_code=403, detail="Accès refusé")

    result = await db.execute(select(Employee).where(Employee.id == employee_id))
    emp = result.scalar_one_or_none()
    if not emp:
        raise HTTPException(status_code=404, detail="Employé non trouvé")
    return emp


@router.patch("/{employee_id}", response_model=EmployeeOut, summary="Modifier un employé")
async def update_employee(
    employee_id: int,
    payload: EmployeeUpdate,
    db: AsyncSession = Depends(get_db),
    current_user: Employee = Depends(get_current_user),
):
    # Employees can update only themselves (except role changes)
    if current_user.role == Role.employee:
        if current_user.id != employee_id:
            raise HTTPException(status_code=403, detail="Accès refusé")
        if payload.role is not None:
            raise HTTPException(status_code=403, detail="Modification de rôle interdite")

    result = await db.execute(select(Employee).where(Employee.id == employee_id))
    emp = result.scalar_one_or_none()
    if not emp:
        raise HTTPException(status_code=404, detail="Employé non trouvé")

    update_data = payload.model_dump(exclude_none=True)
    if "password" in update_data:
        update_data["hashed_password"] = hash_password(update_data.pop("password"))

    for field, value in update_data.items():
        setattr(emp, field, value)

    await db.commit()
    await db.refresh(emp)
    return emp


@router.delete("/{employee_id}", status_code=204, summary="Supprimer un employé")
async def delete_employee(
    employee_id: int,
    db: AsyncSession = Depends(get_db),
    _: Employee = Depends(_admin_only),
):
    result = await db.execute(select(Employee).where(Employee.id == employee_id))
    emp = result.scalar_one_or_none()
    if not emp:
        raise HTTPException(status_code=404, detail="Employé non trouvé")
    await db.delete(emp)
    await db.commit()
