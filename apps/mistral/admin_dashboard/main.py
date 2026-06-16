# FastAPI Admin Dashboard for Employee Management
# main.py

from fastapi import FastAPI, Depends, HTTPException, status, UploadFile, File, Form, Query
from fastapi.security import OAuth2PasswordBearer, OAuth2PasswordRequestForm
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, EmailStr
from typing import List, Optional
from datetime import datetime, timedelta
from jose import JWTError, jwt
from passlib.context import CryptContext
import sqlite3
import os
import uuid
import csv
import io
import matplotlib.pyplot as plt
import base64
from fastapi.responses import FileResponse, StreamingResponse

# --- Config ---
SECRET_KEY = "your-secret-key-here"
ALGORITHM = "HS256"
ACCESS_TOKEN_EXPIRE_MINUTES = 30

# --- Models ---
class EmployeeBase(BaseModel):
    name: str
    email: EmailStr
    department: str
    role: str

class EmployeeCreate(EmployeeBase):
    password: str

class Employee(EmployeeBase):
    id: int
    is_active: bool
    created_at: datetime

class EmployeeInDB(Employee):
    hashed_password: str

class Token(BaseModel):
    access_token: str
    token_type: str

class TokenData(BaseModel):
    email: Optional[EmailStr] = None
    role: Optional[str] = None

class Document(BaseModel):
    id: int
    employee_id: int
    filename: str
    upload_date: datetime

# --- Database Setup ---
def get_db():
    conn = sqlite3.connect("employees.db")
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    conn = get_db()
    cursor = conn.cursor()
    
    # Employees table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS employees (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            department TEXT NOT NULL,
            role TEXT NOT NULL,
            hashed_password TEXT NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    """)
    
    # Documents table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            employee_id INTEGER NOT NULL,
            filename TEXT NOT NULL,
            file_path TEXT NOT NULL,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id)
        )
    """)
    
    # API Keys table
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS api_keys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT UNIQUE NOT NULL,
            service_name TEXT NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    """)
    
    conn.commit()
    conn.close()

# --- Auth ---
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")
oauth2_scheme = OAuth2PasswordBearer(tokenUrl="token")

def verify_password(plain_password: str, hashed_password: str):
    return pwd_context.verify(plain_password, hashed_password)

def get_password_hash(password: str):
    return pwd_context.hash(password)

def get_user(db, email: str):
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM employees WHERE email = ?", (email,))
    user = cursor.fetchone()
    conn.close()
    if user:
        return EmployeeInDB(**dict(user))
    return None

def authenticate_user(db, email: str, password: str):
    user = get_user(db, email)
    if not user:
        return False
    if not verify_password(password, user.hashed_password):
        return False
    return user

def create_access_token(data: dict, expires_delta: Optional[timedelta] = None):
    to_encode = data.copy()
    if expires_delta:
        expire = datetime.utcnow() + expires_delta
    else:
        expire = datetime.utcnow() + timedelta(minutes=15)
    to_encode.update({"exp": expire})
    encoded_jwt = jwt.encode(to_encode, SECRET_KEY, algorithm=ALGORITHM)
    return encoded_jwt

async def get_current_user(token: str = Depends(oauth2_scheme)):
    credentials_exception = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Could not validate credentials",
        headers={"WWW-Authenticate": "Bearer"},
    )
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        email: str = payload.get("sub")
        role: str = payload.get("role")
        if email is None:
            raise credentials_exception
        token_data = TokenData(email=email, role=role)
    except JWTError:
        raise credentials_exception
    
    user = get_user(get_db(), email=token_data.email)
    if user is None:
        raise credentials_exception
    return user

async def get_current_active_user(current_user: EmployeeInDB = Depends(get_current_user)):
    if not current_user.is_active:
        raise HTTPException(status_code=400, detail="Inactive user")
    return current_user

# --- Permissions ---
def check_admin(current_user: EmployeeInDB):
    if current_user.role != "administrateur":
        raise HTTPException(status_code=403, detail="Admin access required")

def check_manager_or_admin(current_user: EmployeeInDB):
    if current_user.role not in ["manager", "administrateur"]:
        raise HTTPException(status_code=403, detail="Manager or Admin access required")

# --- FastAPI App ---
app = FastAPI(title="Employee Admin Dashboard")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# --- Routes ---

# Auth
@app.post("/token", response_model=Token)
async def login_for_access_token(
    form_data: OAuth2PasswordRequestForm = Depends()
):
    user = authenticate_user(get_db(), form_data.username, form_data.password)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Incorrect email or password",
            headers={"WWW-Authenticate": "Bearer"},
        )
    access_token_expires = timedelta(minutes=ACCESS_TOKEN_EXPIRE_MINUTES)
    access_token = create_access_token(
        data={"sub": user.email, "role": user.role},
        expires_delta=access_token_expires
    )
    return {"access_token": access_token, "token_type": "bearer"}

# Employees
@app.post("/employees/", response_model=Employee)
async def create_employee(
    employee: EmployeeCreate,
    current_user: EmployeeInDB = Depends(get_current_active_user)
):
    check_admin(current_user)
    
    conn = get_db()
    cursor = conn.cursor()
    hashed_password = get_password_hash(employee.password)
    cursor.execute(
        """INSERT INTO employees (name, email, department, role, hashed_password) 
           VALUES (?, ?, ?, ?, ?)""",
        (employee.name, employee.email, employee.department, employee.role, hashed_password)
    )
    conn.commit()
    employee_id = cursor.lastrowid
    conn.close()
    
    return Employee(
        id=employee_id,
        name=employee.name,
        email=employee.email,
        department=employee.department,
        role=employee.role,
        is_active=True,
        created_at=datetime.utcnow()
    )

@app.get("/employees/", response_model=List[Employee])
async def read_employees(
    department: Optional[str] = Query(None),
    current_user: EmployeeInDB = Depends(get_current_active_user)
):
    check_manager_or_admin(current_user)
    
    conn = get_db()
    cursor = conn.cursor()
    if department:
        cursor.execute("SELECT * FROM employees WHERE department = ?", (department,))
    else:
        cursor.execute("SELECT * FROM employees")
    employees = cursor.fetchall()
    conn.close()
    
    return [Employee(**dict(emp)) for emp in employees]

@app.get("/employees/{employee_id}", response_model=Employee)
async def read_employee(
    employee_id: int,
    current_user: EmployeeInDB = Depends(get_current_active_user)
):
    check_manager_or_admin(current_user)
    
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM employees WHERE id = ?", (employee_id,))
    employee = cursor.fetchone()
    conn.close()
    
    if employee is None:
        raise HTTPException(status_code=404, detail="Employee not found")
    return Employee(**dict(employee))

@app.put("/employees/{employee_id}", response_model=Employee)
async def update_employee(
    employee_id: int,
    employee: EmployeeBase,
    current_user: EmployeeInDB = Depends(get_current_active_user)
):
    check_manager_or_admin(current_user)
    
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute(
        """UPDATE employees SET name = ?, email = ?, department = ?, role = ? WHERE id = ?""",
        (employee.name, employee.email, employee.department, employee.role, employee_id)
    )
    conn.commit()
    cursor.execute("SELECT * FROM employees WHERE id = ?", (employee_id,))
    updated_employee = cursor.fetchone()
    conn.close()
    
    if updated_employee is None:
        raise HTTPException(status_code=404, detail="Employee not found")
    return Employee(**dict(updated_employee))

@app.delete("/employees/{employee_id}")
async def delete_employee(
    employee_id: int,
    current_user: EmployeeInDB = Depends(get_current_active_user)
):
    check_admin(current_user)
    
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("DELETE FROM employees WHERE id = ?", (employee_id,))
    conn.commit()
    conn.close()
    
    return {"message": "Employee deleted successfully"}

# Documents
@app.post("/employees/{employee_id}/documents/")
async def upload_document(
    employee_id: int,
    file: UploadFile = File(...),
    current_user: EmployeeInDB = Depends(get_current_active_user)
):
    if current_user.id != employee_id and current_user.role not in ["manager", "administrateur"]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    # Create uploads directory if it doesn't exist
    os.makedirs("uploads", exist_ok=True)
    
    # Generate a unique filename
    file_extension = os.path.splitext(file.filename)[1]
    unique_filename = f"{uuid.uuid4()}{file_extension}"
    file_path = f"uploads/{unique_filename}"
    
    # Save the file
    with open(file_path, "wb") as buffer:
        buffer.write(await file.read())
    
    # Save to database
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute(
        """INSERT INTO documents (employee_id, filename, file_path) 
           VALUES (?, ?, ?)""",
        (employee_id, file.filename, file_path)
    )
    conn.commit()
    conn.close()
    
    return {"message": "File uploaded successfully", "filename": file.filename}

@app.get("/employees/{employee_id}/documents/{document_id}")
async def download_document(
    employee_id: int,
    document_id: int,
    current_user: EmployeeInDB = Depends(get_current_active_user)
):
    if current_user.id != employee_id and current_user.role not in ["manager", "administrateur"]:
        raise HTTPException(status_code=403, detail="Not authorized")
    
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM documents WHERE id = ? AND employee_id = ?", (document_id, employee_id))
    document = cursor.fetchone()
    conn.close()
    
    if document is None:
        raise HTTPException(status_code=404, detail="Document not found")
    
    return FileResponse(document["file_path"], filename=document["filename"])

# Export CSV
@app.get("/employees/export/csv")
async def export_employees_csv(
    department: Optional[str] = Query(None),
    current_user: EmployeeInDB = Depends(get_current_active_user)
):
    check_manager_or_admin(current_user)
    
    conn = get_db()
    cursor = conn.cursor()
    if department:
        cursor.execute("SELECT * FROM employees WHERE department = ?", (department,))
    else:
        cursor.execute("SELECT * FROM employees")
    employees = cursor.fetchall()
    conn.close()
    
    # Create CSV in memory
    output = io.StringIO()
    writer = csv.writer(output)
    writer.writerow(["ID", "Name", "Email", "Department", "Role", "Is Active", "Created At"])
    for emp in employees:
        writer.writerow([
            emp["id"], emp["name"], emp["email"], emp["department"], 
            emp["role"], emp["is_active"], emp["created_at"]
        ])
    
    # Return as streaming response
    output.seek(0)
    return StreamingResponse(
        iter([output.getvalue()]),
        media_type="text/csv",
        headers={"Content-Disposition": "attachment; filename=employees.csv"}
    )

# Statistics
@app.get("/statistics/department")
async def get_department_statistics(
    current_user: EmployeeInDB = Depends(get_current_active_user)
):
    check_manager_or_admin(current_user)
    
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT department, COUNT(*) as count FROM employees GROUP BY department")
    stats = cursor.fetchall()
    conn.close()
    
    # Create a bar chart
    departments = [stat["department"] for stat in stats]
    counts = [stat["count"] for stat in stats]
    
    plt.figure(figsize=(10, 6))
    plt.bar(departments, counts)
    plt.title("Employees by Department")
    plt.xlabel("Department")
    plt.ylabel("Number of Employees")
    
    # Save to bytes
    buf = io.BytesIO()
    plt.savefig(buf, format="png")
    buf.seek(0)
    plt.close()
    
    return StreamingResponse(buf, media_type="image/png")

# API Key Endpoint
@app.get("/api/employees")
async def get_employees_via_api(
    api_key: str = Query(...)
):
    conn = get_db()
    cursor = conn.cursor()
    cursor.execute("SELECT * FROM api_keys WHERE key = ? AND is_active = TRUE", (api_key,))
    key_data = cursor.fetchone()
    
    if not key_data:
        raise HTTPException(status_code=403, detail="Invalid or inactive API key")
    
    cursor.execute("SELECT * FROM employees")
    employees = cursor.fetchall()
    conn.close()
    
    return [Employee(**dict(emp)) for emp in employees]

# --- Main ---
if __name__ == "__main__":
    init_db()
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)