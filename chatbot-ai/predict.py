import sys
import os
import json
import pickle
import pandas as pd
import numpy as np
import random
import re
import warnings

# Silencing all warnings and TF info
warnings.filterwarnings("ignore")
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

# Simpan stdout asli dan alihkan semua print ke stderr sementara
original_stdout = sys.stdout
sys.stdout = sys.stderr

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

def get_path(filename):
    return os.path.join(BASE_DIR, filename)

# ============================================================
# DATA & MODEL LOADING (Best Effort)
# ============================================================
def load_all():
    data = {}
    try:
        data["responses"] = pd.read_csv(get_path('DataResponse.csv'))
        data["barang"] = pd.read_csv(get_path('DataBarang.csv'))
        data["jadwal"] = pd.read_csv(get_path('DataJadwal.csv'))
        data["fasilitas"] = pd.read_csv(get_path('DataFasilitas.csv'))
        
        try:
            import keras
            try:
                from keras.src.saving import pickle_utils
                sys.modules['keras.saving.pickle_utils'] = pickle_utils
            except ImportError:
                pass
            
            with open(get_path('tokenizer.pickle'), 'rb') as f:
                data["tokenizer"] = pickle.load(f)
            with open(get_path('label_encoder.pickle'), 'rb') as f:
                data["le"] = pickle.load(f)
            with open(get_path('model_chatbot.pickle'), 'rb') as f:
                data["model"] = pickle.load(f)
            data["ai_active"] = True
        except Exception:
            data["ai_active"] = False
            
        return data
    except Exception as e:
        return {"error": str(e)}

def clean_text(text):
    text = str(text).lower()
    text = re.sub(r'[^a-zA-Z0-9\s]', '', text)
    return text

def detect_intent_keyword(text):
    if any(k in text for k in ["jadwal", "buka", "jam"]): return "cek_jadwal"
    if any(k in text for k in ["daftar", "pendaftaran", "registrasi"]): return "cara_pendaftaran"
    if any(k in text for k in ["barang", "bawa", "makanan", "minuman"]): return "barang_boleh"
    if any(k in text for k in ["fasilitas", "lokasi", "tempat"]): return "fasilitas"
    if any(k in text for k in ["halo", "hai", "selamat"]): return "sapaan"
    return "unknown"

def get_response(user_input, data):
    text = user_input.lower().strip()
    cleaned = clean_text(user_input)
    
    intent = detect_intent_keyword(cleaned)
    
    if intent == "unknown" and data.get("ai_active"):
        try:
            from tensorflow.keras.preprocessing.sequence import pad_sequences
            seq = data["tokenizer"].texts_to_sequences([cleaned])
            padded = pad_sequences(seq, maxlen=20, padding='post')
            pred = data["model"].predict(padded, verbose=0)[0]
            idx = np.argmax(pred)
            if pred[idx] > 0.4:
                intent = data["le"].inverse_transform([idx])[0]
        except:
            pass

    # Logic
    if intent in ["barang_terlarang", "barang_bawaan", "barang_boleh"]:
        for _, row in data["barang"].iterrows():
            nama_brg = str(row["nama_barang"]).lower()
            if nama_brg in text or any(k in text for k in nama_brg.split()):
                return f"Mohon maaf, {row['nama_barang']} {row['status']} dibawa masuk. {row['keterangan']}."

    if intent == "cek_jadwal":
        for _, row in data["jadwal"].iterrows():
            if str(row["hari"]).lower() in text:
                keyword = "buka" if str(row["status"]).lower() == "buka" else "tutup"
                res_rows = data["responses"][(data["responses"]["intent"] == "cek_jadwal") & (data["responses"]["keyword"] == keyword)]
                if not res_rows.empty:
                    template = random.choice(res_rows.iloc[0]["jawaban"].split("|"))
                    try: return template.format(hari=row["hari"], jam_mulai=row.get("jam_mulai", ""), jam_selesai=row.get("jam_selesai", ""))
                    except: return template.replace("{hari}", str(row["hari"]))
        return "Layanan kunjungan tersedia Senin-Jumat (09.00-14.00) dan Sabtu-Minggu (09.00-15.00)."

    if intent == "fasilitas":
        for _, row in data["fasilitas"].iterrows():
            fas = str(row["nama_fasilitas"]).lower()
            if fas in text or any(k in text for k in fas.split()):
                return f"{row['nama_fasilitas']} berada di {row['lokasi']}. {row['keterangan']}."

    res_rows = data["responses"][data["responses"]["intent"] == intent]
    if res_rows.empty:
        if intent == "unknown": return "Maaf, saya belum memahami pertanyaan Anda. Silakan tanyakan hal lain seputar layanan kunjungan."
        res_rows = data["responses"][data["responses"]["intent"] == "sapaan"]

    selected_jawaban = None
    for _, row in res_rows.iterrows():
        kw = str(row["keyword"]).lower()
        if kw != "default" and kw in text:
            selected_jawaban = random.choice(row["jawaban"].split("|"))
            break
            
    if not selected_jawaban:
        default_row = res_rows[res_rows["keyword"].str.lower() == "default"]
        selected_jawaban = random.choice((default_row.iloc[0]["jawaban"] if not default_row.empty else res_rows.iloc[0]["jawaban"]).split("|"))
            
    return selected_jawaban

if __name__ == "__main__":
    try:
        input_text = sys.argv[1] if len(sys.argv) > 1 else ""
        data = load_all()
        if "error" in data:
            result = {"response": "Sistem sedang gangguan.", "error": data["error"]}
        else:
            ans = get_response(input_text, data)
            result = {"response": ans}
    except Exception as e:
        result = {"response": "Terjadi kesalahan internal.", "error": str(e)}

    # Kembalikan stdout dan print JSON
    sys.stdout = original_stdout
    print(json.dumps(result))
