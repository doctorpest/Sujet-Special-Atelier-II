import os, uuid, shutil
from fastapi import APIRouter, Depends, HTTPException, status, UploadFile, File
from fastapi.responses import FileResponse
from sqlalchemy.orm import Session
from typing import List
from dependencies import get_db, get_current_user
from schemas import DocumentOut
from models import Document, User, Role
from routers.employees import check_employee_access

router = APIRouter()
UPLOAD_DIR = "uploads"

def get_upload_dir(employee_id: int) -> str:
    path = os.path.join(UPLOAD_DIR, str(employee_id))
    os.makedirs(path, exist_ok=True)
    return path

@router.post("/{employee_id}/documents", response_model=DocumentOut, status_code=201)
def upload_document(
    employee_id: int,
    file: UploadFile = File(...),
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    check_employee_access(employee_id, current_user, db)  # vérifie droits
    if not file.filename.lower().endswith(".pdf"):
        raise HTTPException(status_code=400, detail="Only PDF files are allowed")
    unique_name = f"{uuid.uuid4()}_{file.filename}"
    dir_path = get_upload_dir(employee_id)
    file_path = os.path.join(dir_path, unique_name)
    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)
    doc = Document(filename=file.filename, file_path=file_path, employee_id=employee_id)
    db.add(doc)
    db.commit()
    db.refresh(doc)
    return doc

@router.get("/{employee_id}/documents", response_model=List[DocumentOut])
def list_documents(
    employee_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    check_employee_access(employee_id, current_user, db)
    docs = db.query(Document).filter(Document.employee_id == employee_id).all()
    return docs

@router.get("/{employee_id}/documents/{doc_id}")
def download_document(
    employee_id: int,
    doc_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    check_employee_access(employee_id, current_user, db)
    doc = db.query(Document).filter(Document.id == doc_id, Document.employee_id == employee_id).first()
    if not doc:
        raise HTTPException(status_code=404, detail="Document not found")
    return FileResponse(doc.file_path, filename=doc.filename)