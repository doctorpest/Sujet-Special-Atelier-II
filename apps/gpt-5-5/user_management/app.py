import os
import sqlite3
from functools import wraps
from datetime import datetime, timezone

from flask import (
    Flask,
    render_template,
    request,
    redirect,
    url_for,
    session,
    flash,
    g,
    abort,
)
from flask_mail import Mail, Message
from itsdangerous import URLSafeTimedSerializer, BadSignature, SignatureExpired
from werkzeug.security import generate_password_hash, check_password_hash


BASE_DIR = os.path.abspath(os.path.dirname(__file__))
DATABASE = os.path.join(BASE_DIR, "database.db")

app = Flask(__name__)

# En production, définis SECRET_KEY dans les variables d'environnement.
app.config["SECRET_KEY"] = os.environ.get("SECRET_KEY", "dev-secret-key-change-me")

# Configuration email.
# Par défaut, les emails sont affichés dans la console en développement.
app.config["MAIL_SERVER"] = os.environ.get("MAIL_SERVER", "localhost")
app.config["MAIL_PORT"] = int(os.environ.get("MAIL_PORT", "25"))
app.config["MAIL_USE_TLS"] = os.environ.get("MAIL_USE_TLS", "false").lower() == "true"
app.config["MAIL_USERNAME"] = os.environ.get("MAIL_USERNAME")
app.config["MAIL_PASSWORD"] = os.environ.get("MAIL_PASSWORD")
app.config["MAIL_DEFAULT_SENDER"] = os.environ.get(
    "MAIL_DEFAULT_SENDER", "no-reply@example.com"
)
app.config["MAIL_SUPPRESS_SEND"] = os.environ.get(
    "MAIL_SUPPRESS_SEND", "true"
).lower() == "true"

mail = Mail(app)
serializer = URLSafeTimedSerializer(app.config["SECRET_KEY"])


def get_db():
    if "db" not in g:
        g.db = sqlite3.connect(DATABASE)
        g.db.row_factory = sqlite3.Row
    return g.db


@app.teardown_appcontext
def close_db(exception=None):
    db = g.pop("db", None)
    if db is not None:
        db.close()


def init_db():
    db = get_db()
    with open(os.path.join(BASE_DIR, "schema.sql"), "r", encoding="utf-8") as f:
        db.executescript(f.read())
    db.commit()


def create_default_admin_if_needed():
    db = get_db()
    admin = db.execute("SELECT id FROM users WHERE role = 'admin' LIMIT 1").fetchone()

    if admin is None:
        email = os.environ.get("ADMIN_EMAIL", "admin@example.com")
        password = os.environ.get("ADMIN_PASSWORD", "admin123")
        full_name = os.environ.get("ADMIN_FULL_NAME", "Administrateur")

        db.execute(
            """
            INSERT INTO users (email, password_hash, full_name, role)
            VALUES (?, ?, ?, ?)
            """,
            (email, generate_password_hash(password), full_name, "admin"),
        )
        db.commit()
        print("=" * 70)
        print("Compte administrateur créé pour le développement :")
        print(f"Email    : {email}")
        print(f"Password : {password}")
        print("Change ces identifiants en production.")
        print("=" * 70)


def ensure_database():
    if not os.path.exists(DATABASE):
        with app.app_context():
            init_db()
            create_default_admin_if_needed()


def current_user():
    user_id = session.get("user_id")
    if not user_id:
        return None

    return get_db().execute(
        "SELECT id, email, full_name, role, created_at FROM users WHERE id = ?",
        (user_id,),
    ).fetchone()


@app.context_processor
def inject_user():
    return {"current_user": current_user()}


def login_required(view):
    @wraps(view)
    def wrapped_view(*args, **kwargs):
        if current_user() is None:
            flash("Tu dois être connecté pour accéder à cette page.", "warning")
            return redirect(url_for("login"))
        return view(*args, **kwargs)

    return wrapped_view


def admin_required(view):
    @wraps(view)
    def wrapped_view(*args, **kwargs):
        user = current_user()
        if user is None:
            flash("Tu dois être connecté.", "warning")
            return redirect(url_for("login"))

        if user["role"] != "admin":
            abort(403)

        return view(*args, **kwargs)

    return wrapped_view


def send_reset_email(user):
    token = serializer.dumps(user["email"], salt="password-reset")
    reset_url = url_for("reset_password", token=token, _external=True)

    msg = Message(
        subject="Réinitialisation de ton mot de passe",
        recipients=[user["email"]],
        body=f"""Bonjour {user["full_name"]},

Tu as demandé une réinitialisation de mot de passe.

Clique sur ce lien pour choisir un nouveau mot de passe :
{reset_url}

Ce lien expire dans 1 heure.

Si tu n'es pas à l'origine de cette demande, ignore cet email.
""",
    )

    if app.config["MAIL_SUPPRESS_SEND"]:
        print("=" * 70)
        print("EMAIL DE RÉINITIALISATION — mode développement")
        print(f"Destinataire : {user['email']}")
        print(f"Lien         : {reset_url}")
        print("=" * 70)
    else:
        mail.send(msg)


@app.route("/")
def index():
    return render_template("index.html")


@app.route("/register", methods=("GET", "POST"))
def register():
    if request.method == "POST":
        email = request.form.get("email", "").strip().lower()
        password = request.form.get("password", "")
        full_name = request.form.get("full_name", "").strip()
        role = request.form.get("role", "user")

        if not email or not password or not full_name:
            flash("Tous les champs sont obligatoires.", "danger")
            return render_template("register.html")

        if role not in ("user", "admin"):
            flash("Rôle invalide.", "danger")
            return render_template("register.html")

        if len(password) < 8:
            flash("Le mot de passe doit contenir au moins 8 caractères.", "danger")
            return render_template("register.html")

        db = get_db()
        existing_user = db.execute(
            "SELECT id FROM users WHERE email = ?", (email,)
        ).fetchone()

        if existing_user:
            flash("Un compte existe déjà avec cet email.", "danger")
            return render_template("register.html")

        db.execute(
            """
            INSERT INTO users (email, password_hash, full_name, role)
            VALUES (?, ?, ?, ?)
            """,
            (email, generate_password_hash(password), full_name, role),
        )
        db.commit()

        flash("Inscription réussie. Tu peux maintenant te connecter.", "success")
        return redirect(url_for("login"))

    return render_template("register.html")


@app.route("/login", methods=("GET", "POST"))
def login():
    if request.method == "POST":
        email = request.form.get("email", "").strip().lower()
        password = request.form.get("password", "")

        user = get_db().execute(
            "SELECT * FROM users WHERE email = ?", (email,)
        ).fetchone()

        if user is None or not check_password_hash(user["password_hash"], password):
            flash("Email ou mot de passe incorrect.", "danger")
            return render_template("login.html")

        session.clear()
        session["user_id"] = user["id"]

        flash("Connexion réussie.", "success")
        return redirect(url_for("profile"))

    return render_template("login.html")


@app.route("/logout")
@login_required
def logout():
    session.clear()
    flash("Tu es déconnecté.", "info")
    return redirect(url_for("index"))


@app.route("/profile")
@login_required
def profile():
    return render_template("profile.html", user=current_user())


@app.route("/admin/users")
@admin_required
def admin_users():
    users = get_db().execute(
        """
        SELECT id, email, full_name, role, created_at
        FROM users
        ORDER BY created_at DESC
        """
    ).fetchall()

    return render_template("admin_users.html", users=users)


@app.route("/admin/users/<int:user_id>/role", methods=("POST",))
@admin_required
def update_user_role(user_id):
    new_role = request.form.get("role")

    if new_role not in ("user", "admin"):
        flash("Rôle invalide.", "danger")
        return redirect(url_for("admin_users"))

    db = get_db()
    user = db.execute("SELECT id FROM users WHERE id = ?", (user_id,)).fetchone()

    if user is None:
        flash("Utilisateur introuvable.", "danger")
        return redirect(url_for("admin_users"))

    db.execute("UPDATE users SET role = ? WHERE id = ?", (new_role, user_id))
    db.commit()

    flash("Rôle mis à jour.", "success")
    return redirect(url_for("admin_users"))


@app.route("/admin/users/<int:user_id>/delete", methods=("POST",))
@admin_required
def delete_user(user_id):
    logged_user = current_user()

    if logged_user["id"] == user_id:
        flash("Tu ne peux pas supprimer ton propre compte administrateur.", "danger")
        return redirect(url_for("admin_users"))

    db = get_db()
    user = db.execute("SELECT id FROM users WHERE id = ?", (user_id,)).fetchone()

    if user is None:
        flash("Utilisateur introuvable.", "danger")
        return redirect(url_for("admin_users"))

    db.execute("DELETE FROM users WHERE id = ?", (user_id,))
    db.commit()

    flash("Utilisateur supprimé.", "success")
    return redirect(url_for("admin_users"))


@app.route("/forgot-password", methods=("GET", "POST"))
def forgot_password():
    if request.method == "POST":
        email = request.form.get("email", "").strip().lower()

        user = get_db().execute(
            """
            SELECT id, email, full_name
            FROM users
            WHERE email = ?
            """,
            (email,),
        ).fetchone()

        # Réponse volontairement générique pour ne pas révéler si l'email existe.
        if user:
            send_reset_email(user)

        flash(
            "Si un compte existe avec cet email, un lien de réinitialisation a été envoyé.",
            "info",
        )
        return redirect(url_for("login"))

    return render_template("forgot_password.html")


@app.route("/reset-password/<token>", methods=("GET", "POST"))
def reset_password(token):
    try:
        email = serializer.loads(
            token,
            salt="password-reset",
            max_age=3600,
        )
    except SignatureExpired:
        flash("Le lien de réinitialisation a expiré.", "danger")
        return redirect(url_for("forgot_password"))
    except BadSignature:
        flash("Lien de réinitialisation invalide.", "danger")
        return redirect(url_for("forgot_password"))

    db = get_db()
    user = db.execute("SELECT id FROM users WHERE email = ?", (email,)).fetchone()

    if user is None:
        flash("Compte introuvable.", "danger")
        return redirect(url_for("forgot_password"))

    if request.method == "POST":
        password = request.form.get("password", "")
        confirm_password = request.form.get("confirm_password", "")

        if len(password) < 8:
            flash("Le mot de passe doit contenir au moins 8 caractères.", "danger")
            return render_template("reset_password.html", token=token)

        if password != confirm_password:
            flash("Les mots de passe ne correspondent pas.", "danger")
            return render_template("reset_password.html", token=token)

        db.execute(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            (generate_password_hash(password), user["id"]),
        )
        db.commit()

        flash("Mot de passe réinitialisé. Tu peux te connecter.", "success")
        return redirect(url_for("login"))

    return render_template("reset_password.html", token=token)


@app.errorhandler(403)
def forbidden(error):
    return render_template("base.html", error_message="Accès interdit."), 403


@app.errorhandler(404)
def not_found(error):
    return render_template("base.html", error_message="Page introuvable."), 404


if __name__ == "__main__":
    ensure_database()
    app.run(debug=True)