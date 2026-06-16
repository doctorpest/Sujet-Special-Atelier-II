from pathlib import Path
from uuid import uuid4
from fastapi import APIRouter, Depends, File, UploadFile, HTTPException
from fastapi.responses import FileResponse
from sqlalchemy.orm import Session
from app.dependencies import get_db, require_roles
from app.models import Employee, EmployeeDocument, Role
from app.schemas import DocumentRead
from app.config import settings

router = APIRouter(prefix="/documents", tags=["Documents PDF"])


@router.post(
    "/{employee_id}/upload",
    response_model=DocumentRead,
    dependencies=[Depends(require_roles(Role.MANAGER, Role.ADMIN))],
)
async def upload_employee_pdf(
    employee_id: int,
    file: UploadFile = File(...),
    db: Session = Depends(get_db),
):
    employee = db.query(Employee).filter(Employee.id == employee_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="Employé introuvable")

    if file.content_type != "application/pdf" or not file.filename.lower().endswith(".pdf"):
        raise HTTPException(status_code=400, detail="Seuls les fichiers PDF sont autorisés")

    upload_dir = Path(settings.UPLOAD_DIR)
    upload_dir.mkdir(parents=True, exist_ok=True)

    stored_filename = f"{employee_id}_{uuid4().hex}.pdf"
    file_path = upload_dir / stored_filename

    content = await file.read()
    if not content:
        raise HTTPException(status_code=400, detail="Fichier vide")

    file_path.write_bytes(content)

    document = EmployeeDocument(
        employee_id=employee_id,
        original_filename=file.filename,
        stored_filename=stored_filename,
        file_path=str(file_path),
    )
    db.add(document)
    db.commit()
    db.refresh(document)

    return document


@router.get("/{document_id}/download")
def download_employee_pdf(
    document_id: int,
    db: Session = Depends(get_db),
    _=Depends(require_roles(Role.EMPLOYEE, Role.MANAGER, Role.ADMIN)),
):
    document = db.query(EmployeeDocument).filter(EmployeeDocument.id == document_id).first()
    if not document:
        raise HTTPException(status_code=404, detail="Document introuvable")

    path = Path(document.file_path)
    if not path.exists():
        raise HTTPException(status_code=404, detail="Fichier absent du serveur")

    return FileResponse(
        path=path,
        media_type="application/pdf",
        filename=document.original_filename,
    )


@router.get("/employee/{employee_id}", response_model=list[DocumentRead])
def list_employee_documents(
    employee_id: int,
    db: Session = Depends(get_db),
    _=Depends(require_roles(Role.EMPLOYEE, Role.MANAGER, Role.ADMIN)),
):
    employee = db.query(Employee).filter(Employee.id == employee_id).first()
    if not employee:
        raise HTTPException(status_code=404, detail="Employé introuvable")

    return db.query(EmployeeDocument).filter(EmployeeDocument.employee_id == employee_id).order_by(EmployeeDocument.uploaded_at.desc()).all()
