from sqlalchemy.ext.asyncio import create_async_engine, async_sessionmaker, AsyncSession
from sqlalchemy.orm import DeclarativeBase

from core.config import DATABASE_URL

engine = create_async_engine(DATABASE_URL, echo=False)
AsyncSessionLocal = async_sessionmaker(engine, expire_on_commit=False)


class Base(DeclarativeBase):
    pass


async def get_db() -> AsyncSession:
    async with AsyncSessionLocal() as session:
        yield session


async def init_db():
    """Create all tables and seed a default admin."""
    from models.employee import Employee  # noqa: F401 — register model
    from models.document import Document  # noqa: F401

    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)

    # Seed admin if none exists
    from sqlalchemy import select
    from models.employee import Role
    from core.security import hash_password

    async with AsyncSessionLocal() as session:
        result = await session.execute(
            select(Employee).where(Employee.role == Role.admin)
        )
        if result.scalar_one_or_none() is None:
            admin = Employee(
                first_name="Admin",
                last_name="Système",
                email="admin@company.com",
                department="IT",
                role=Role.admin,
                hashed_password=hash_password("admin1234"),
                is_active=True,
            )
            session.add(admin)
            await session.commit()
            print("✅ Admin créé : admin@company.com / admin1234")
