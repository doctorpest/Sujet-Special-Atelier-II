from pydantic import BaseModel, EmailStr
from typing import Optional, List
from datetime import datetime
from models import Role

# Auth
class Token(BaseModel):
    access_token: str
    token_type: str

class Login(BaseModel):
    username: str
    password: str

# User / Employee
class UserBase(BaseModel):
    username: str
    email: EmailStr
    full_name: str
    department: str
    role: Role = Role.employee

class UserCreate(UserBase):
    password: str

class UserUpdate(BaseModel):
    email: Optional[EmailStr] = None
    full_name: Optional[str] = None
    department: Optional[str] = None
    role: Optional[Role] = None

class UserOut(UserBase):
    id: int
    class Config:
        from_attributes = True

# Document
class DocumentOut(BaseModel):
    id: int
    filename: str
    employee_id: int
    upload_date: datetime
    class Config:
        from_attributes = True

# API Key
class APIKeyCreate(BaseModel):
    description: str

class APIKeyOut(BaseModel):
    id: int
    key: str
    description: str
    is_active: bool
    created_at: datetime
    class Config:
        from_attributes = True

# External employee list (public)
class EmployeePublic(BaseModel):
    id: int
    username: str
    email: str
    full_name: str
    department: str
    role: Role
    class Config:
        from_attributes = True