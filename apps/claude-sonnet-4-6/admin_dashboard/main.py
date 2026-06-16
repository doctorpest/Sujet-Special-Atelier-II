from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from core.database import init_db
from routers import auth, employees, documents, export, external_api


@asynccontextmanager
async def lifespan(app: FastAPI):
    await init_db()
    yield


app = FastAPI(
    title="HR Dashboard API",
    description=(
        "API de gestion de flotte d'employés. "
        "Authentification JWT · Rôles (employee / manager / admin) · "
        "Upload PDF · Export CSV · Stats PNG · API externe par clé."
    ),
    version="1.0.0",
    lifespan=lifespan,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],        # Restreindre en production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(auth.router)
app.include_router(employees.router)
app.include_router(documents.router)
app.include_router(export.router)
app.include_router(external_api.router)


@app.get("/", tags=["Root"])
async def root():
    return {
        "app": "HR Dashboard API",
        "version": "1.0.0",
        "docs": "/docs",
        "redoc": "/redoc",
    }
