from datetime import datetime
from typing import Optional

from sqlalchemy import String, Integer, DateTime, ForeignKey
from sqlalchemy.orm import Mapped, mapped_column

from core.database import Base


class Document(Base):
    __tablename__ = "documents"

    id: Mapped[int] = mapped_column(Integer, primary_key=True, index=True)
    employee_id: Mapped[int] = mapped_column(Integer, ForeignKey("employees.id"), index=True)
    filename: Mapped[str] = mapped_column(String(255))
    original_name: Mapped[str] = mapped_column(String(255))
    file_path: Mapped[str] = mapped_column(String(512))
    uploaded_at: Mapped[datetime] = mapped_column(DateTime, default=datetime.utcnow)
    uploaded_by: Mapped[Optional[int]] = mapped_column(Integer, nullable=True)


# ── Pydantic ──────────────────────────────────────────────────────────────────
from pydantic import BaseModel


class DocumentOut(BaseModel):
    id: int
    employee_id: int
    filename: str
    original_name: str
    uploaded_at: datetime

    model_config = {"from_attributes": True}
