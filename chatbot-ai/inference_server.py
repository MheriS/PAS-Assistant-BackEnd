"""
Layanan inferensi chatbot: model & CSV dimuat sekali saat startup.

Jalankan dari folder ini (environment Python yang sama dengan predict.py):
  python inference_server.py

Atau:
  uvicorn inference_server:app --host 127.0.0.1 --port 8765 --workers 1

Set di Laravel .env: CHATBOT_INFERENCE_URL=http://127.0.0.1:8765
"""
from __future__ import annotations

import os
import sys
import threading
from contextlib import asynccontextmanager

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

# Pastikan impor predict memakai direktori skrip (CSV/pickle)
os.chdir(os.path.dirname(os.path.abspath(__file__)))
if os.path.dirname(os.path.abspath(__file__)) not in sys.path:
    sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

import predict as pred  # noqa: E402

_predict_lock = threading.Lock()


@asynccontextmanager
async def lifespan(app: FastAPI):
    base = pred.get_inference_static_root()
    if isinstance(base, dict) and base.get("error"):
        print("inference_server: warm load error:", base.get("error"), file=sys.stderr)
    yield


app = FastAPI(title="PAS Chatbot Inference", lifespan=lifespan)


class InferRequest(BaseModel):
    message: str = Field(..., min_length=1, max_length=8000)
    jadwal_base64: str | None = None
    jadwal_spesial_base64: str | None = None


@app.post("/infer")
def infer(body: InferRequest):
    data = pred.inference_merge_schedule(
        body.jadwal_base64,
        body.jadwal_spesial_base64,
    )
    if not isinstance(data, dict) or "error" in data:
        err = data.get("error", "unknown") if isinstance(data, dict) else "invalid"
        raise HTTPException(status_code=503, detail=str(err))

    with _predict_lock:
        text = pred.get_response(body.message, data)

    return {"response": text}


@app.get("/health")
def health():
    base = pred.get_inference_static_root()
    ok = isinstance(base, dict) and "responses" in base and "error" not in base
    return {"ok": ok}


if __name__ == "__main__":
    import uvicorn

    uvicorn.run(
        "inference_server:app",
        host="127.0.0.1",
        port=8765,
        workers=1,
        log_level="info",
    )
