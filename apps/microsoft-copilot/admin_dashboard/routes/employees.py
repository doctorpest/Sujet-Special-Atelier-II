# routes/employees.py
import os
import io
import csv
from fastapi import APIRouter, Depends, HTTPException, UploadFile, File, Query
from fastapi.responses import FileResponse, StreamingResponse, Response
from sqlalchemy.orm import Session
from models import User, Document
from schemas import UserCreate, UserOut, DocumentOut
from deps import get_db, require_role, get_password_hash, get_current_user
from typing import List, Optional
import matplotlib.pyplot as plt

router = APIRouter()

# Create employee (admin or manager)
@router.post("/", response_model=UserOut)
def create_employee(payload: UserCreate, db: Session = Depends(get_db), current=Depends(require_role("administrateur", "manager"))):
    user = User(
        email=payload.email,
        full_name=payload.full_name,
        hashed_password=get_password_hash(payload.password),
        role=payload.role,
        department=payload.department
    )
    db.add(user)
    try:
        db.commit()
        db.refresh(user)
    except Exception:
        db.rollback()
        raise HTTPException(status_code=400, detail="Could not create user")
    return user

# List employees (all roles can list; employees see only themselves)
@router.get("/", response_model=List[UserOut])
def list_employees(db: Session = Depends(get_db), current=Depends(get_current_user)):
    if current.role == "employe":
        users = db.query(User).filter(User.id == current.id).all()
    elif current.role == "manager":
        users = db.query(User).filter(User.department == current.department).all()
    else:
        users = db.query(User).all()
    return users

# Get employee
@router.get("/{user_id}", response_model=UserOut)
def get_employee(user_id: int, db: Session = Depends(get_db), current=Depends(get_current_user)):
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(404, "User not found")
    if current.role == "employe" and current.id != user.id:
        raise HTTPException(403, "Insufficient permissions")
    if current.role == "manager" and user.department != current.department and current.id != user.id:
        raise HTTPException(403, "Insufficient permissions")
    return user

# Update employee
@router.put("/{user_id}", response_model=UserOut)
def update_employee(user_id: int, payload: UserCreate, db: Session = Depends(get_db), current=Depends(require_role("administrateur", "manager"))):
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(404, "User not found")
    user.email = payload.email
    user.full_name = payload.full_name
    user.hashed_password = get_password_hash(payload.password)
    user.role = payload.role
    user.department = payload.department
    db.commit()
    db.refresh(user)
    return user

# Delete employee (admin only)
@router.delete("/{user_id}")
def delete_employee(user_id: int, db: Session = Depends(get_db), current=Depends(require_role("administrateur"))):
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(404, "User not found")
    # delete documents files
    for doc in user.documents:
        try:
            os.remove(doc.path)
        except Exception:
            pass
    db.delete(user)
    db.commit()
    return {"detail": "deleted"}

# Upload PDF for employee (employee can upload for self; manager/admin for others)
@router.post("/{user_id}/documents", response_model=DocumentOut)
def upload_document(user_id: int, file: UploadFile = File(...), db: Session = Depends(get_db), current=Depends(get_current_user)):
    if file.content_type != "application/pdf":
        raise HTTPException(400, "Only PDF allowed")
    user = db.query(User).filter(User.id == user_id).first()
    if not user:
        raise HTTPException(404, "User not found")
    if current.role == "employe" and current.id != user.id:
        raise HTTPException(403, "Insufficient permissions")
    if current.role == "manager" and user.department != current.department and current.id != user.id:
        raise HTTPException(403, "Insufficient permissions")
    # save file
    filename = f"{user_id}_{int(__import__('time').time())}_{file.filename}"
    path = os.path.join("uploads", filename)
    with open(path, "wb") as f:
        f.write(file.file.read())
    doc = Document(user_id=user.id, filename=file.filename, path=path)
    db.add(doc)
    db.commit()
    db.refresh(doc)
    return doc

# Download document
@router.get("/{user_id}/documents/{doc_id}")
def download_document(user_id: int, doc_id: int, db: Session = Depends(get_db), current=Depends(get_current_user)):
    doc = db.query(Document).filter(Document.id == doc_id, Document.user_id == user_id).first()
    if not doc:
        raise HTTPException(404, "Document not found")
    user = db.query(User).filter(User.id == user_id).first()
    if current.role == "employe" and current.id != user.id:
        raise HTTPException(403, "Insufficient permissions")
    if current.role == "manager" and user.department != current.department and current.id != user.id:
        raise HTTPException(403, "Insufficient permissions")
    return FileResponse(path=doc.path, filename=doc.filename, media_type="application/pdf")

# Export CSV filtered by department
@router.get("/export")
def export_csv(department: Optional[str] = Query(None), db: Session = Depends(get_db), current=Depends(require_role("administrateur", "manager"))):
    query = db.query(User)
    if department:
        query = query.filter(User.department == department)
    users = query.all()
    def iter_csv():
        buffer = io.StringIO()
        writer = csv.writer(buffer)
        writer.writerow(["id", "email", "full_name", "role", "department", "created_at"])
        for u in users:
            writer.writerow([u.id, u.email, u.full_name, u.role, u.department or "", u.created_at.isoformat()])
            yield buffer.getvalue()
            buffer.seek(0)
            buffer.truncate(0)
    headers = {
        "Content-Disposition": f"attachment; filename=employees_{department or 'all'}.csv"
    }
    return StreamingResponse(iter_csv(), media_type="text/csv", headers=headers)

# Stats endpoint returning PNG (matplotlib)
@router.get("/stats")
def stats_png(department: Optional[str] = Query(None), db: Session = Depends(get_db), current=Depends(require_role("administrateur", "manager"))):
    query = db.query(User)
    if department:
        query = query.filter(User.department == department)
    users = query.all()
    # simple stat: count per role
    counts = {}
    for u in users:
        counts[u.role] = counts.get(u.role, 0) + 1
    roles = list(counts.keys())
    values = [counts[r] for r in roles]
    plt.figure(figsize=(6,4))
    plt.bar(roles, values, color=["#2b8cbe","#7bccc4","#edf8b1"])
    plt.title(f"Répartition par rôle {'- ' + department if department else ''}")
    plt.xlabel("Rôle")
    plt.ylabel("Nombre")
    buf = io.BytesIO()
    plt.tight_layout()
    plt.savefig(buf, format="png")
    plt.close()
    buf.seek(0)
    return Response(content=buf.getvalue(), media_type="image/png")
