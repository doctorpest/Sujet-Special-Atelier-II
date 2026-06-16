from fastapi import FastAPI
from database import engine, Base
from routers import auth, employees, documents, statistics, export, external, api_keys

app = FastAPI(title="Fleet Employee Dashboard", version="1.0.0")

@app.on_event("startup")
def on_startup():
    Base.metadata.create_all(bind=engine)

# Inclusion des routeurs
app.include_router(auth.router, prefix="/auth", tags=["auth"])
app.include_router(employees.router, prefix="/employees", tags=["employees"])
app.include_router(documents.router, prefix="/employees", tags=["documents"])
app.include_router(statistics.router, prefix="/statistics", tags=["statistics"])
app.include_router(export.router, prefix="/export", tags=["export"])
app.include_router(external.router, prefix="/external", tags=["external"])
app.include_router(api_keys.router, prefix="/admin/api-keys", tags=["admin-api-keys"])

@app.get("/")
def root():
    return {"message": "Fleet Management API"}