# HR34 Flask API

Quick start

1. Create and activate a virtual environment
2. Install requirements
3. Run the server

Windows (PowerShell)

```
python -m venv .venv
.venv\Scripts\Activate.ps1
pip install -r requirements.txt
python app.py
```

Environment variables

- HOST (default 0.0.0.0)
- PORT (default 8000)
- JWT_SECRET_KEY
- MYSQL_HOST, MYSQL_PORT, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB

Health check: GET /health

