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
warnings.filtervoiced = False
warnings.filterwarnings("ignore")
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

# Simpan stdout asli dan alihkan semua print ke stderr sementara
original_stdout = sys.stdout
sys.stdout = sys.stderr

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

def get_path(filename):
    return os.path.join(BASE_DIR, filename)

# ============================================================
# NORMALIZATION DICTIONARY (Typo & Synonyms)
# ============================================================
normalization_dict = {
    "mesjid": "masjid",
    "musholla": "masjid",
    "mushola": "masjid",
    "yg": "yang",
    "klo": "kalau",
    "kalo": "kalau",
    "dimana": "di mana",
    "pendaftaran": "daftar",
    "registrasi": "daftar",
    "bgmn": "bagaimana",
    "gmn": "bagaimana",
    "tks": "terima kasih",
    "trimakasih": "terima kasih",
    "makasih": "terima kasih",
    "bawa": "bawa",
    "pagi": "pagi",
    "siang": "siang",
    "sore": "sore",
    "malam": "malam"
}

def normalize_text(text):
    text = str(text).lower()
    text = re.sub(r'[^a-zA-Z0-9\s]', '', text)
    words = text.split()
    normalized_words = [normalization_dict.get(word, word) for word in words]
    return " ".join(normalized_words)

# ============================================================
# DATA & MODEL LOADING
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
            
            # --- BAGIAN TF-IDF ---
            # Load artefak TF-IDF untuk penanganan typo (Character-level Similarity)
            with open(get_path('tfidf_vectorizer.pickle'), 'rb') as f:
                data["tfidf_vect"] = pickle.load(f)
            with open(get_path('tfidf_matrix.pickle'), 'rb') as f:
                data["tfidf_matrix"] = pickle.load(f)
            with open(get_path('tfidf_intents.pickle'), 'rb') as f:
                data["tfidf_intents"] = pickle.load(f)
            # ---------------------
                
            data["ai_active"] = True
        except Exception:
            data["ai_active"] = False
            
        return data
    except Exception as e:
        return {"error": str(e)}

def get_response(user_input, data):
    text = user_input.lower().strip()
    normalized = normalize_text(user_input)
    
    # ============================================================
    # PHASE 1: GLOBAL KEYWORD MATCH (Highest Priority)
    # ============================================================
    # Search all rows in DataResponse.csv for a specific keyword match 
    # regardless of intent. This ensures "minuman" matches even if 
    # intent is detected differently.
    
    selected_jawaban = None
    best_keyword_len = 0
    
    for _, row in data["responses"].iterrows():
        kw = str(row["keyword"]).lower()
        if kw != "default" and kw in normalized:
            # If multiple keywords match, prioritize the longest one
            if len(kw) > best_keyword_len:
                selected_jawaban = random.choice(row["jawaban"].split("|"))
                best_keyword_len = len(kw)

    if selected_jawaban:
        return selected_jawaban

    # ============================================================
    # PHASE 2: INTENT DETECTION (Broad Keywords + AI)
    # ============================================================
    intent = "unknown"
    
    # Priority 1: Keyword Detection (on normalized text)
    if any(k in normalized for k in ["jadwal", "buka", "jam"]): intent = "cek_jadwal"
    elif any(k in normalized for k in ["daftar", "pendaftaran"]): intent = "cara_pendaftaran"
    elif any(k in normalized for k in ["barang", "bawa", "makanan"]): intent = "barang_boleh"
    elif any(k in normalized for k in ["fasilitas", "lokasi", "tempat", "masjid", "toilet"]): intent = "fasilitas"
    elif any(k in normalized for k in ["halo", "hai", "selamat"]): intent = "sapaan"
    
    # Priority 2: AI Intent Prediction (If keyword unknown)
    if intent == "unknown" and data.get("ai_active"):
        try:
            from tensorflow.keras.preprocessing.sequence import pad_sequences
            from sklearn.metrics.pairwise import cosine_similarity
            
            # 1. Prediksi LSTM
            seq = data["tokenizer"].texts_to_sequences([normalized])
            padded = pad_sequences(seq, maxlen=20, padding='post')
            pred = data["model"].predict(padded, verbose=0)[0]
            idx = np.argmax(pred)
            lstm_conf = pred[idx]
            
            # LOGIKA HYBRID: LSTM + FB-IDF Fallback
            if lstm_conf > 0.7:
                intent = data["le"].inverse_transform([idx])[0]
            else:
                tfidf_input = data["tfidf_vect"].transform([normalized])
                similarities = cosine_similarity(tfidf_input, data["tfidf_matrix"]).flatten()
                best_match_idx = np.argmax(similarities)
                tfidf_conf = similarities[best_match_idx]
                
                if tfidf_conf > 0.4:
                    intent = data["tfidf_intents"][best_match_idx]
                elif lstm_conf > 0.4: 
                    intent = data["le"].inverse_transform([idx])[0]
        except:
            pass

    # ============================================================
    # PHASE 3: SPECIALIZED FALLBACK LOGIC
    # ============================================================
    
    if intent == "cek_jadwal":
        for _, row in data["jadwal"].iterrows():
            if str(row["hari"]).lower() in normalized:
                keyword = "buka" if str(row["status"]).lower() == "buka" else "tutup"
                res_rows_sched = data["responses"][(data["responses"]["intent"] == "cek_jadwal") & (data["responses"]["keyword"] == keyword)]
                if not res_rows_sched.empty:
                    template = random.choice(res_rows_sched.iloc[0]["jawaban"].split("|"))
                    try: return template.format(hari=row["hari"], jam_mulai=row.get("jam_mulai", ""), jam_selesai=row.get("jam_selesai", ""))
                    except: return template.replace("{hari}", str(row["hari"]))
        return "Layanan kunjungan tersedia Senin-Jumat (09.00-14.00) dan Sabtu-Minggu (09.00-15.00)."

    if intent in ["barang_terlarang", "barang_bawaan", "barang_boleh"]:
        for _, row in data["barang"].iterrows():
            nama_brg = str(row["nama_barang"]).lower()
            if nama_brg in normalized or any(k in normalized for k in nama_brg.split()):
                return f"Mohon maaf, {row['nama_barang']} {row['status']} dibawa masuk. {row['keterangan']}."

    if intent == "fasilitas":
        lookup_map = {"musholla": "masjid", "mesjid": "masjid", "toilet": "kamar mandi", "wc": "kamar mandi"}
        search_text = normalized
        for syn, target in lookup_map.items():
            if syn in normalized: search_text = target; break

        for _, row in data["fasilitas"].iterrows():
            fas = str(row["nama_fasilitas"]).lower()
            if fas in search_text or any(k in search_text for k in fas.split()):
                return f"{row['nama_fasilitas']} berada di {row['lokasi']}. {row['keterangan']}."

    # ============================================================
    # PHASE 4: FINAL FALLBACK (Default from DataResponse.csv)
    # ============================================================
    res_rows = data["responses"][data["responses"]["intent"] == intent]
    if res_rows.empty:
        if intent == "unknown": return "Maaf, saya belum memahami pertanyaan Anda. Silakan tanyakan hal lain seputar layanan kunjungan."
        res_rows = data["responses"][data["responses"]["intent"] == "sapaan"]

    default_row = res_rows[res_rows["keyword"].str.lower() == "default"]
    if not default_row.empty:
        return random.choice(default_row.iloc[0]["jawaban"].split("|"))
    elif not res_rows.empty:
        return random.choice(res_rows.iloc[0]["jawaban"].split("|"))
    
    return "Maaf, saya belum memahami pertanyaan Anda."

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
