import os
import uuid
from typing import List

from fastapi import APIRouter, Depends, File, HTTPException, UploadFile
from fastapi.responses import FileResponse
from sqlalchemy import select
from sqlalchemy.ext.asyncio import AsyncSession

from core.config import UPLOAD_DIR
from core.database import get_db
from core.security import get_current_user, require_role
from models.document import Document, DocumentOut
from models.employee import Employee, Role

router = APIRouter(prefix="/employees/{employee_id}/documents", tags=["Documents"])

_admin_or_manager = require_role(Role.admin, Role.manager)

MAX_FILE_SIZE = 10 * 1024 * 1024  # 10 MB


async def _check_employee_exists(employee_id: int, db: AsyncSession):
    result = await db.execute(select(Employee).where(Employee.id == employee_id))
    emp = result.scalar_one_or_none()
    if not emp:
        raise HTTPException(status_code=404, detail="Employé non trouvé")
    return emp


@router.get("/", response_model=List[DocumentOut], summary="Lister les documents d'un employé")
async def list_documents(
    employee_id: int,
    db: AsyncSession = Depends(get_db),
    current_user: Employee = Depends(get_current_user),
):
    if current_user.role == Role.employee and current_user.id != employee_id:
        raise HTTPException(status_code=403, detail="Accès refusé")

    await _check_employee_exists(employee_id, db)
    result = await db.execute(
        select(Document).where(Document.employee_id == employee_id).order_by(Document.uploaded_at.desc())
    )
    return result.scalars().all()


@router.post("/", response_model=DocumentOut, status_code=201, summary="Téléverser un document PDF")
async def upload_document(
    employee_id: int,
    file: UploadFile = File(...),
    db: AsyncSession = Depends(get_db),
    current_user: Employee = Depends(_admin_or_manager),
):
    await _check_employee_exists(employee_id, db)

    if file.content_type not in ("application/pdf",):
        raise HTTPException(status_code=415, detail="Seuls les fichiers PDF sont acceptés")

    contents = await file.read()
    if len(contents) > MAX_FILE_SIZE:
        raise HTTPException(status_code=413, detail="Fichier trop volumineux (max 10 MB)")

    safe_name = f"{uuid.uuid4().hex}.pdf"
    dest_dir = os.path.join(UPLOAD_DIR, str(employee_id))
    os.makedirs(dest_dir, exist_ok=True)
    dest_path = os.path.join(dest_dir, safe_name)

    with open(dest_path, "wb") as f:
        f.write(contents)

    doc = Document(
        employee_id=employee_id,
        filename=safe_name,
        original_name=file.filename,
        file_path=dest_path,
        uploaded_by=current_user.id,
    )
    db.add(doc)
    await db.commit()
    await db.refresh(doc)
    return doc


@router.get("/{document_id}/download", summary="Télécharger un document PDF")
async def download_document(
    employee_id: int,
    document_id: int,
    db: AsyncSession = Depends(get_db),
    current_user: Employee = Depends(get_current_user),
):
    if current_user.role == Role.employee and current_user.id != employee_id:
        raise HTTPException(status_code=403, detail="Accès refusé")

    result = await db.execute(
        select(Document).where(Document.id == document_id, Document.employee_id == employee_id)
    )
    doc = result.scalar_one_or_none()
    if not doc:
        raise HTTPException(status_code=404, detail="Document non trouvé")

    if not os.path.exists(doc.file_path):
        raise HTTPException(status_code=410, detail="Fichier introuvable sur le disque")

    return FileResponse(
        path=doc.file_path,
        media_type="application/pdf",
        filename=doc.original_name,
    )


@router.delete("/{document_id}", status_code=204, summary="Supprimer un document")
async def delete_document(
    employee_id: int,
    document_id: int,
    db: AsyncSession = Depends(get_db),
    _: Employee = Depends(_admin_or_manager),
):
    result = await db.execute(
        select(Document).where(Document.id == document_id, Document.employee_id == employee_id)
    )
    doc = result.scalar_one_or_none()
    if not doc:
        raise HTTPException(status_code=404, detail="Document non trouvé")

    if os.path.exists(doc.file_path):
        os.remove(doc.file_path)

    await db.delete(doc)
    await db.commit()
