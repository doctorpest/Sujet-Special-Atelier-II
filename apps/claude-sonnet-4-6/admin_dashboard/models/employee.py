import enum
from datetime import datetime
from typing import Optional

from sqlalchemy import String, Boolean, DateTime, Enum as SAEnum, Integer
from sqlalchemy.orm import Mapped, mapped_column

from core.database import Base


class Role(str, enum.Enum):
    employee = "employee"
    manager = "manager"
    admin = "admin"


class Employee(Base):
    __tablename__ = "employees"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, index=True)
    first_name: Mapped[str] = mapped_column(String(100))
    last_name: Mapped[str] = mapped_column(String(100))
    email: Mapped[str] = mapped_column(String(200), unique=True, index=True)
    department: Mapped[str] = mapped_column(String(100))
    position: Mapped[Optional[str]] = mapped_column(String(150), nullable=True)
    role: Mapped[Role] = mapped_column(SAEnum(Role), default=Role.employee)
    hashed_password: Mapped[str] = mapped_column(String(256))
    is_active: Mapped[bool] = mapped_column(Boolean, default=True)
    created_at: Mapped[datetime] = mapped_column(DateTime, default=datetime.utcnow)
    updated_at: Mapped[datetime] = mapped_column(
        DateTime, default=datetime.utcnow, onupdate=datetime.utcnow
    )


# ── Pydantic schemas ──────────────────────────────────────────────────────────
from pydantic import BaseModel, EmailStr, field_validator


class EmployeeCreate(BaseModel):
    first_name: str
    last_name: str
    email: EmailStr
    department: str
    position: Optional[str] = None
    role: Role = Role.employee
    password: str

    @field_validator("password")
    @classmethod
    def password_min_length(cls, v):
        if len(v) < 6:
            raise ValueError("Le mot de passe doit comporter au moins 6 caractères")
        return v


class EmployeeUpdate(BaseModel):
    first_name: Optional[str] = None
    last_name: Optional[str] = None
    email: Optional[EmailStr] = None
    department: Optional[str] = None
    position: Optional[str] = None
    role: Optional[Role] = None
    is_active: Optional[bool] = None
    password: Optional[str] = None


class EmployeeOut(BaseModel):
    id: int
    first_name: str
    last_name: str
    email: str
    department: str
    position: Optional[str]
    role: Role
    is_active: bool
    created_at: datetime

    model_config = {"from_attributes": True}


class TokenOut(BaseModel):
    access_token: str
    token_type: str = "bearer"
    role: Role
