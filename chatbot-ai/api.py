from fastapi import FastAPI, Request
import json
import uvicorn
import pandas as pd
import warnings
import os
import sys

warnings.filterwarnings("ignore")
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

from predict import load_all, get_response

app = FastAPI()

print("Memuat model AI dan data pendukung... mohon tunggu")
CORE_DATA = load_all()
if "error" in CORE_DATA:
    print(f"Error loading models: {CORE_DATA['error']}")
else:
    print("AI Model loaded successfully. Server ready and running on port 8001.")

@app.post("/chat")
async def chat(request: Request):
    try:
        data = await request.json()
        message = data.get("message", "")
        
        # Buat copy dari CORE_DATA sehingga dataset jadwal per request bisa dimodifikasi tanpa mengganggu request lain
        request_data = CORE_DATA.copy()
        
        if "jadwal" in data:
            request_data["jadwal"] = pd.DataFrame(data["jadwal"])
        if "jadwal_spesial" in data:
            request_data["jadwal_spesial"] = pd.DataFrame(data["jadwal_spesial"])
            
        ans = get_response(message, request_data)
        return {"response": ans}
        
    except Exception as e:
        return {"response": "Terjadi kesalahan internal pada layanan AI.", "error": str(e)}

if __name__ == "__main__":
    uvicorn.run(app, host="127.0.0.1", port=8001)
