from fastapi import APIRouter, Depends, HTTPException, status, Query
from sqlalchemy.orm import Session
from typing import List, Optional
from dependencies import get_db, get_current_user, role_required
from schemas import UserCreate, UserUpdate, UserOut
from models import User, Role
from routers.auth import get_password_hash

router = APIRouter()

def check_employee_access(employee_id: int, current_user: User, db: Session):
    """Vérifie qu'un utilisateur a le droit d'accéder/modifier un employé donné."""
    employee = db.query(User).filter(User.id == employee_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="Employee not found")
    if current_user.role == Role.admin:
        return employee
    if current_user.role == Role.manager:
        if employee.department != current_user.department:
            raise HTTPException(status_code=403, detail="Access restricted to your department")
        # Un manager ne peut pas modifier les admins
        if employee.role == Role.admin:
            raise HTTPException(status_code=403, detail="Cannot manage admin users")
        return employee
    if current_user.role == Role.employee:
        if employee.id != current_user.id:
            raise HTTPException(status_code=403, detail="Access only to your own profile")
        return employee
    raise HTTPException(status_code=403, detail="Forbidden")

# Liste des employés (filtrée par département)
@router.get("/", response_model=List[UserOut])
def list_employees(
    department: Optional[str] = Query(None),
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    if current_user.role == Role.admin:
        query = db.query(User)
        if department:
            query = query.filter(User.department == department)
        return query.all()
    elif current_user.role == Role.manager:
        # Manager ne voit que son département
        query = db.query(User).filter(User.department == current_user.department)
        if department and department != current_user.department:
            raise HTTPException(status_code=403, detail="Can only filter your own department")
        return query.all()
    else:  # employee
        # Un employé ne voit que son propre profil
        return [current_user]

# Créer un employé
@router.post("/", response_model=UserOut, status_code=201)
def create_employee(
    employee: UserCreate,
    current_user: User = Depends(role_required([Role.admin, Role.manager])),
    db: Session = Depends(get_db)
):
    # Vérification manager : ne peut créer que dans son département
    if current_user.role == Role.manager:
        if employee.department != current_user.department:
            raise HTTPException(status_code=403, detail="Can only create employees in your department")
        if employee.role in [Role.admin, Role.manager]:
            raise HTTPException(status_code=403, detail="Cannot create admin or manager")
    # Vérifier unicité username
    if db.query(User).filter(User.username == employee.username).first():
        raise HTTPException(status_code=400, detail="Username already exists")
    hashed_pwd = get_password_hash(employee.password)
    db_user = User(
        username=employee.username,
        hashed_password=hashed_pwd,
        email=employee.email,
        full_name=employee.full_name,
        department=employee.department,
        role=employee.role
    )
    db.add(db_user)
    db.commit()
    db.refresh(db_user)
    return db_user

# Détails employé
@router.get("/{employee_id}", response_model=UserOut)
def get_employee(
    employee_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    employee = check_employee_access(employee_id, current_user, db)
    return employee

# Mise à jour employé
@router.put("/{employee_id}", response_model=UserOut)
def update_employee(
    employee_id: int,
    updates: UserUpdate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    employee = check_employee_access(employee_id, current_user, db)
    # Restrictions de rôle
    if current_user.role == Role.manager:
        # Un manager ne peut pas changer le rôle d'un employé
        if updates.role is not None and updates.role in [Role.admin, Role.manager]:
            raise HTTPException(status_code=403, detail="Cannot assign admin/manager role")
    if current_user.role == Role.employee:
        # Un employé ne peut pas changer son rôle ni son département
        if updates.role is not None or updates.department is not None:
            raise HTTPException(status_code=403, detail="Cannot modify role or department")
    for field, value in updates.dict(exclude_unset=True).items():
        setattr(employee, field, value)
    db.commit()
    db.refresh(employee)
    return employee

# Suppression employé (admin seulement)
@router.delete("/{employee_id}", status_code=204)
def delete_employee(
    employee_id: int,
    current_user: User = Depends(role_required([Role.admin])),
    db: Session = Depends(get_db)
):
    employee = db.query(User).filter(User.id == employee_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="Employee not found")
    db.delete(employee)
    db.commit()
    return