import os
from flask import Flask, jsonify


def create_app() -> Flask:
    app = Flask(__name__)

    @app.get("/health")
    def health() -> tuple:
        return jsonify({"status": "ok"}), 200

    @app.get("/")
    def root() -> tuple:
        return jsonify({"service": "hr34-api", "framework": "flask"}), 200

    return app


if __name__ == "__main__":
    app = create_app()
    app.run(host=os.getenv("HOST", "0.0.0.0"), port=int(os.getenv("PORT", "8000")))


