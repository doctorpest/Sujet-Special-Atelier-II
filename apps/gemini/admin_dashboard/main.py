import os
import io
import csv
from datetime import datetime, timedelta
from typing import List, Optional

import matplotlib
matplotlib.use('Agg')  # Configuration du backend non-interactif pour Matplotlib
import matplotlib.pyplot as plt

from fastapi import FastAPI, Depends, HTTPException, status, File, UploadFile, Security
from fastapi.security import OAuth2PasswordBearer, APIKeyHeader
from fastapi.responses import StreamingResponse, FileResponse
from sqlalchemy import create_engine, Column, Integer, String, Float, ForeignKey, func
from sqlalchemy.orm import declarative_base, sessionmaker, Session
from pydantic import BaseModel, EmailStr
from passlib.context import CryptContext
import jwt

# ==========================================
# CONFIGURATION ET CONSTANTES
# ==========================================
SECRET_KEY = "SUPER_SECRET_KEY_A_CHANGER_EN_PRODUCTION"
ALGORITHM = "HS256"
ACCESS_TOKEN_EXPIRE_MINUTES = 60
API_KEY_NAME = "X-API-Key"
EXTERNAL_API_KEY = "PROD_EXTERNAL_SERVICE_SECRET_TOKEN"
UPLOAD_DIR = "./uploaded_docs"

os.makedirs(UPLOAD_DIR, exist_ok=True)

DATABASE_URL = "sqlite:///./fleet_management.db"
engine = create_engine(DATABASE_URL, connect_args={"check_same_thread": False})
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()

pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")
oauth2_scheme = OAuth2PasswordBearer(tokenUrl="auth/login")
api_key_header = APIKeyHeader(name=API_KEY_NAME, auto_error=False)

app = FastAPI(title="Dashboard Administratif - Gestion de Flotte d'Employés")

# ==========================================
# MODÈLES DE BASE DE DONNÉES (SQLAlchemy)
# ==========================================
class DBEmployee(Base):
    __tablename__ = "employees"

    id = Column(Integer, primary_key=True, index=True)
    username = Column(String, unique=True, index=True, nullable=False)
    hashed_password = Column(String, nullable=False)
    full_name = Column(String, nullable=False)
    email = Column(String, unique=True, index=True, nullable=False)
    role = Column(String, nullable=False)  # admin, manager, employee
    department = Column(String, nullable=False)
    salary = Column(Float, nullable=False)

class DBDocument(Base):
    __tablename__ = "documents"

    id = Column(Integer, primary_key=True, index=True)
    filename = Column(String, nullable=False)
    file_path = Column(String, nullable=False)
    employee_id = Column(Integer, ForeignKey("employees.id", ondelete="CASCADE"), nullable=False)

Base.metadata.create_all(bind=engine)

# ==========================================
# SCHÉMAS DE DONNÉES (Pydantic)
# ==========================================
class EmployeeBase(BaseModel):
    username: str
    full_name: str
    email: EmailStr
    role: str  # admin, manager, employee
    department: str
    salary: float

class EmployeeCreate(EmployeeBase):
    password: str

class EmployeeUpdate(BaseModel):
    full_name: Optional[str] = None
    email: Optional[EmailStr] = None
    role: Optional[str] = None
    department: Optional[str] = None
    salary: Optional[float] = None

class EmployeeResponse(EmployeeBase):
    id: int
    class Config:
        from_attributes = True

class Token(BaseModel):
    access_token: str
    token_type: str

# ==========================================
# DÉPENDANCES ET UTILITAIRES
# ==========================================
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

def verify_password(plain_password, hashed_password):
    return pwd_context.verify(plain_password, hashed_password)

def get_password_hash(password):
    return pwd_context.hash(password)

def create_access_token(data: dict):
    to_encode = data.copy()
    expire = datetime.utcnow() + timedelta(minutes=ACCESS_TOKEN_EXPIRE_MINUTES)
    to_encode.update({"exp": expire})
    return jwt.encode(to_encode, SECRET_KEY, algorithm=ALGORITHM)

def get_current_user(token: str = Depends(oauth2_scheme), db: Session = Depends(get_db)) -> DBEmployee:
    credentials_exception = HTTPException(
        status_code=status.HTTP_101_UNAUTHORIZED,
        detail="Token de validation invalide ou expiré.",
        headers={"WWW-Authenticate": "Bearer"},
    )
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        username: str = payload.get("sub")
        if username is None:
            raise credentials_exception
    except jwt.PyJWTError:
        raise credentials_exception
    
    user = db.query(DBEmployee).filter(DBEmployee.username == username).first()
    if user is None:
        raise credentials_exception
    return user

class RoleChecker:
    def __init__(self, allowed_roles: List[str]):
        self.allowed_roles = allowed_roles

    def __call__(self, current_user: DBEmployee = Depends(get_current_user)):
        if current_user.role not in self.allowed_roles:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Permissions insuffisantes pour effectuer cette action."
            )
        return current_user

def verify_external_api_key(api_key: str = Security(api_key_header)):
    if api_key == EXTERNAL_API_KEY:
        return api_key
    raise HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Clé API externe manquante ou invalide."
    )

# INITIALISATION DES DONNÉES (Création d'un Admin par défaut)
@app.on_event("startup")
def startup_populate():
    db = SessionLocal()
    admin_exists = db.query(DBEmployee).filter(DBEmployee.username == "admin").first()
    if not admin_exists:
        admin_user = DBEmployee(
            username="admin",
            hashed_password=get_password_hash("admin123"),
            full_name="Administrateur Système",
            email="admin@entreprise.com",
            role="admin",
            department="IT",
            salary=65000.0
        )
        db.add(admin_user)
        db.commit()
    db.close()

# ==========================================
# ENDPOINTS API : AUTHENTIFICATION
# ==========================================
@app.post("/auth/login", response_model=Token)
def login(form_data: EmployeeCreate, db: Session = Depends(get_db)):
    # Utilisation simplifiée du schéma pour l'authentification JSON
    user = db.query(DBEmployee).filter(DBEmployee.username == form_data.username).first()
    if not user or not verify_password(form_data.password, user.hashed_password):
        raise HTTPException(status_code=400, detail="Identifiants incorrects.")
    
    access_token = create_access_token(data={"sub": user.username})
    return {"access_token": access_token, "token_type": "bearer"}

# ==========================================
# ENDPOINTS API : CRUD EMPLOYES
# ==========================================
@app.post("/employees", response_model=EmployeeResponse, status_code=201)
def create_employee(
    employee: EmployeeCreate, 
    db: Session = Depends(get_db), 
    _ = Depends(RoleChecker(["admin", "manager"]))
):
    db_user = db.query(DBEmployee).filter(
        (DBEmployee.username == employee.username) | (DBEmployee.email == employee.email)
    ).first()
    if db_user:
        raise HTTPException(status_code=400, detail="L'username ou l'email est déjà enregistré.")
    
    new_employee = DBEmployee(
        username=employee.username,
        hashed_password=get_password_hash(employee.password),
        full_name=employee.full_name,
        email=employee.email,
        role=employee.role,
        department=employee.department,
        salary=employee.salary
    )
    db.add(new_employee)
    db.commit()
    db.refresh(new_employee)
    return new_employee

@app.get("/employees", response_model=List[EmployeeResponse])
def list_employees(
    db: Session = Depends(get_db), 
    current_user: DBEmployee = Depends(RoleChecker(["admin", "manager", "employee"]))
):
    if current_user.role == "employee":
        return [current_user]
    return db.query(DBEmployee).all()

@app.get("/employees/{emp_id}", response_model=EmployeeResponse)
def get_employee(
    emp_id: int, 
    db: Session = Depends(get_db), 
    current_user: DBEmployee = Depends(RoleChecker(["admin", "manager", "employee"]))
):
    if current_user.role == "employee" and current_user.id != emp_id:
        raise HTTPException(status_code=403, detail="Vous ne pouvez visualiser que votre propre profil.")
    
    employee = db.query(DBEmployee).filter(DBEmployee.id == emp_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="Employé introuvable.")
    return employee

@app.put("/employees/{emp_id}", response_model=EmployeeResponse)
def update_employee(
    emp_id: int, 
    data: EmployeeUpdate, 
    db: Session = Depends(get_db), 
    _ = Depends(RoleChecker(["admin", "manager"]))
):
    employee = db.query(DBEmployee).filter(DBEmployee.id == emp_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="Employé introuvable.")
    
    for key, value in data.dict(exclude_unset=True).items():
        setattr(employee, key, value)
        
    db.commit()
    db.refresh(employee)
    return employee

@app.delete("/employees/{emp_id}", status_code=204)
def delete_employee(
    emp_id: int, 
    db: Session = Depends(get_db), 
    _ = Depends(RoleChecker(["admin"]))
):
    employee = db.query(DBEmployee).filter(DBEmployee.id == emp_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="Employé introuvable.")
    db.delete(employee)
    db.commit()
    return None

# ==========================================
# GESTION DES DOCUMENTS (PDF)
# ==========================================
@app.post("/employees/{emp_id}/documents", status_code=201)
def upload_document(
    emp_id: int, 
    file: UploadFile = File(...), 
    db: Session = Depends(get_db), 
    _ = Depends(RoleChecker(["admin", "manager"]))
):
    if not file.filename.lower().endswith('.pdf'):
        raise HTTPException(status_code=400, detail="Seuls les fichiers PDF sont autorisés.")
    
    employee = db.query(DBEmployee).filter(DBEmployee.id == emp_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="Employé introuvable.")
    
    file_location = os.path.join(UPLOAD_DIR, f"{emp_id}_{file.filename}")
    with open(file_location, "wb") as f:
        f.write(file.file.read())
        
    db_doc = DBDocument(filename=file.filename, file_path=file_location, employee_id=emp_id)
    db.add(db_doc)
    db.commit()
    return {"message": "Document PDF téléversé avec succès.", "filename": file.filename}

@app.get("/employees/{emp_id}/documents", response_model=List[dict])
def list_documents(
    emp_id: int, 
    db: Session = Depends(get_db), 
    current_user: DBEmployee = Depends(RoleChecker(["admin", "manager", "employee"]))
):
    if current_user.role == "employee" and current_user.id != emp_id:
        raise HTTPException(status_code=403, detail="Accès refusé aux documents d'autres employés.")
        
    docs = db.query(DBDocument).filter(DBDocument.employee_id == emp_id).all()
    return [{"id": d.id, "filename": d.filename} for d in docs]

@app.get("/employees/{emp_id}/documents/{doc_id}")
def download_document(
    emp_id: int, 
    doc_id: int, 
    db: Session = Depends(get_db), 
    current_user: DBEmployee = Depends(RoleChecker(["admin", "manager", "employee"]))
):
    if current_user.role == "employee" and current_user.id != emp_id:
        raise HTTPException(status_code=403, detail="Accès interdit.")
        
    doc = db.query(DBDocument).filter(DBDocument.id == doc_id, DBDocument.employee_id == emp_id).first()
    if not doc:
        raise HTTPException(status_code=404, detail="Document introuvable pour cet employé.")
        
    return FileResponse(path=doc.file_path, filename=doc.filename, media_type='application/pdf')

# ==========================================
# EXPORT DATA (CSV)
# ==========================================
@app.get("/export/csv")
def export_csv_by_department(
    department: str, 
    db: Session = Depends(get_db), 
    _ = Depends(RoleChecker(["admin", "manager"]))
):
    employees = db.query(DBEmployee).filter(DBEmployee.department == department).all()
    
    output = io.StringIO()
    writer = csv.writer(output)
    writer.writerow(["ID", "Nom complet", "Email", "Role", "Département", "Salaire"])
    
    for emp in employees:
        writer.writerow([emp.id, emp.full_name, emp.email, emp.role, emp.department, emp.salary])
        
    output.seek(0)
    response = StreamingResponse(output, media_type="text/csv")
    response.headers["Content-Disposition"] = f"attachment; filename=employees_{department}.csv"
    return response

# ==========================================
# STATISTIQUES GRAPHES (Matplotlib PNG)
# ==========================================
@app.get("/stats/chart")
def get_stats_chart(
    db: Session = Depends(get_db), 
    _ = Depends(RoleChecker(["admin", "manager"]))
):
    # Agrégation des salaires moyens par département
    results = db.query(DBEmployee.department, func.avg(DBEmployee.salary)).group_by(DBEmployee.department).all()
    
    if not results:
        raise HTTPException(status_code=404, detail="Aucune donnée disponible pour générer un graphique.")
        
    departments = [r[0] for r in results]
    avg_salaries = [r[1] for r in results]
    
    plt.figure(figsize=(8, 5))
    plt.bar(departments, avg_salaries, color='skyblue', edgecolor='navy')
    plt.title("Salaire Moyen par Département")
    plt.xlabel("Départements")
    plt.ylabel("Salaire Moyen (€)")
    plt.grid(axis='y', linestyle='--', alpha=0.7)
    
    img_buf = io.BytesIO()
    plt.savefig(img_buf, format='png', bbox_inches='tight')
    img_buf.seek(0)
    plt.close()
    
    return StreamingResponse(img_buf, media_type="image/png")

# ==========================================
# API PARTENAIRE EXTERNE (Clé API)
# ==========================================
@app.get("/external/employees", response_model=List[EmployeeResponse])
def external_api_get_employees(
    db: Session = Depends(get_db), 
    _ = Depends(verify_external_api_key)
):
    """Permet à un service externe d'extraire la liste globale des employés via Token API."""
    return db.query(DBEmployee).all()