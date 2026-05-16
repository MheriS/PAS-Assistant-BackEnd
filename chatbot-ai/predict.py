import sys
import os
import json
import pickle
import pandas as pd
import numpy as np
import random
import re
import warnings
from rapidfuzz import fuzz

# Silencing all warnings and TF info
warnings.filtervoiced = False
warnings.filterwarnings("ignore")
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

# Simpan stdout asli; pengalihan stdout hanya dipakai saat CLI (__main__) agar modul aman diimpor inference server.
original_stdout = sys.stdout

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

def get_jadwal_summary_text(data):
    parts = []
    buka_days = data["jadwal"][data["jadwal"]["status"].str.lower() == "buka"]
    if not buka_days.empty:
        details = []
        for _, r in buka_days.iterrows():
            details.append(f"{r['hari']} ({r['jam_mulai']} - {r['jam_selesai']})")
        parts.append("Jadwal rutin: " + ", ".join(details))
    
    if "jadwal_spesial" in data and not data["jadwal_spesial"].empty:
        spesial_grouped = {}
        for _, r in data["jadwal_spesial"].iterrows():
            label = r.get("label", r.get("hari", ""))
            if label not in spesial_grouped:
                spesial_grouped[label] = []
            spesial_grouped[label].append(f"{r['jam_mulai']}-{r['jam_selesai']}")
        sp_details = []
        for label, times in spesial_grouped.items():
            sp_details.append(f"{label} ({' & '.join(times)})")
        parts.append("Jadwal spesial: " + ", ".join(sp_details))
    
    return ". ".join(parts) if parts else "tidak ada jadwal layanan"

# ============================================================
# DATA & MODEL LOADING
# ============================================================
def load_all(jadwal_base64=None, jadwal_spesial_base64=None):
    data = {}
    try:
        data["responses"] = pd.read_csv(get_path('DataResponse.csv'))
        data["barang"] = pd.read_csv(get_path('DataBarang.csv'))
        
        import base64 as b64
        
        if jadwal_base64:
            jadwal_json = b64.b64decode(jadwal_base64).decode('utf-8')
            data["jadwal"] = pd.DataFrame(json.loads(jadwal_json))
        else:
            data["jadwal"] = pd.read_csv(get_path('DataJadwal.csv'))
        
        if jadwal_spesial_base64:
            spesial_json = b64.b64decode(jadwal_spesial_base64).decode('utf-8')
            data["jadwal_spesial"] = pd.DataFrame(json.loads(spesial_json))
        else:
            data["jadwal_spesial"] = pd.DataFrame()
            
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
            
            from sklearn.feature_extraction.text import TfidfVectorizer
            
            # --- DYNAMIC FAST EMBEDDINGS (No Dict) ---
            corpus = []
            intents = []
            for _, row in data["responses"].iterrows():
                kw = str(row["keyword"]).lower()
                if kw != "default":
                    for k in kw.split(';'):
                        if k.strip():
                            corpus.append(k.strip())
                            intents.append(row["intent"])

            # Character-level embeddings with tight boundaries
            char_vect = TfidfVectorizer(analyzer='char_wb', ngram_range=(2, 5))
            char_matrix = char_vect.fit_transform(corpus)
            
            data["tfidf_vect"] = char_vect
            data["tfidf_matrix"] = char_matrix
            data["tfidf_intents"] = intents
            # ---------------------
                
            data["ai_active"] = True
        except Exception:
            data["ai_active"] = False
            
        return data
    except Exception as e:
        return {"error": str(e)}

_INFERENCE_STATIC_CACHE = None


def get_inference_static_root():
    """Muat CSV + model sekali untuk layanan inferensi yang tetap hidup."""
    global _INFERENCE_STATIC_CACHE
    if _INFERENCE_STATIC_CACHE is not None:
        return _INFERENCE_STATIC_CACHE
    _INFERENCE_STATIC_CACHE = load_all(None, None)
    return _INFERENCE_STATIC_CACHE


def inference_merge_schedule(jadwal_base64, jadwal_spesial_base64):
    """Gabungkan jadwal live (dari Laravel) dengan cache statis model/CSV."""
    base = get_inference_static_root()
    if not isinstance(base, dict) or "error" in base:
        return base
    import base64 as b64

    data = {
        k: v
        for k, v in base.items()
        if k not in ("jadwal", "jadwal_spesial")
    }
    if jadwal_base64:
        data["jadwal"] = pd.DataFrame(
            json.loads(b64.b64decode(jadwal_base64).decode("utf-8"))
        )
    else:
        data["jadwal"] = pd.read_csv(get_path("DataJadwal.csv"))
    if jadwal_spesial_base64:
        data["jadwal_spesial"] = pd.DataFrame(
            json.loads(b64.b64decode(jadwal_spesial_base64).decode("utf-8"))
        )
    else:
        data["jadwal_spesial"] = pd.DataFrame()
    return data


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
        raw_kw = str(row["keyword"]).lower()
        jawaban = str(row["jawaban"])
        
        # Skip keywords that are "default" or templates that contain placeholders (indicated by "{")
        # because those templates require context handled in Phase 3.
        if raw_kw == "default" or "{" in jawaban:
            continue
            
        # Handle multiple keywords separated by ';'
        keywords = [k.strip() for k in raw_kw.split(';')]
        
        for kw in keywords:
            if not kw:
                continue
                
            norm_kw = normalize_text(kw)
            if not norm_kw:
                continue
                
            # Use word boundaries to avoid partial matches (e.g., "buka" matching "dibuka")
            if re.search(rf'\b{re.escape(norm_kw)}\b', normalized):
                # If multiple keywords match, prioritize the longest one
                if len(kw) > best_keyword_len:
                    selected_jawaban = random.choice(jawaban.split("|"))
                    best_keyword_len = len(kw)

    if selected_jawaban:
        return selected_jawaban

    # ============================================================
    # PHASE 2: INTENT DETECTION (Broad Keywords + AI)
    # ============================================================
    intent = "unknown"
    
    # Priority 1: Keyword Detection (on normalized text)
    if any(k in normalized for k in ["jadwal", "buka", "jam"]): intent = "cek_jadwal"
    elif any(k in normalized for k in ["syarat", "ketentuan", "identitas", "identitas diri"]): intent = "syarat_kunjungan"
    elif any(k in normalized for k in ["barang", "bawa", "makanan"]): intent = "barang_boleh"
    elif any(k in normalized for k in ["fasilitas", "lokasi", "tempat", "masjid", "toilet"]): intent = "fasilitas"
    elif any(k in normalized for k in ["durasi", "menit", "lama"]): intent = "durasi_kunjungan"
    elif any(k in normalized for k in ["anak", "balita", "lansia"]): intent = "ketentuan_pengunjung"
    elif any(k in normalized for k in ["loket", "pintu", "meja informasi"]): intent = "lokasi_layanan"
    elif any(k in normalized for k in ["nomor", "telepon", "admin", "hubungi", "wa"]): intent = "kontak"
    elif any(k in normalized for k in ["pakaian", "baju", "sandal", "kaos", "celana"]): intent = "ketentuan_pakaian"
    elif any(k in normalized for k in ["daftar", "pendaftaran"]): intent = "cara_pendaftaran"
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

    # Priority 3: Fallback Item Detection (Search DataBarang names if still unknown)
    if intent == "unknown":
        for _, row in data["barang"].iterrows():
            nama = str(row["nama_barang"]).lower()
            if len(nama) >= 2 and nama in normalized:
                intent = "barang_boleh"
                break

    # ============================================================
    # PHASE 3: SPECIALIZED FALLBACK LOGIC
    # ============================================================
    
    if intent == "cek_jadwal":
        # Check if asking about a specific day
        for _, row in data["jadwal"].iterrows():
            if str(row["hari"]).lower() in normalized:
                keyword = "buka" if str(row["status"]).lower() == "buka" else "tutup"
                res_rows_sched = data["responses"][(data["responses"]["intent"] == "cek_jadwal") & (data["responses"]["keyword"] == keyword)]
                if not res_rows_sched.empty:
                    template = random.choice(res_rows_sched.iloc[0]["jawaban"].split("|"))
                    try: return template.format(hari=row["hari"], jam_mulai=row.get("jam_mulai", ""), jam_selesai=row.get("jam_selesai", ""))
                    except: return template.replace("{hari}", str(row["hari"]))
        
        # Build comprehensive summary with routine + special
        buka_days = data["jadwal"][data["jadwal"]["status"].str.lower() == "buka"]
        parts = []
        if not buka_days.empty:
            details = []
            for _, r in buka_days.iterrows():
                details.append(f"{r['hari']} ({r['jam_mulai']} - {r['jam_selesai']})")
            parts.append("Berikut adalah jadwal pendaftaran kunjungan rutin: " + ", ".join(details) + ". Hari lainnya layanan tutup.")
        
        if "jadwal_spesial" in data and not data["jadwal_spesial"].empty:
            spesial_grouped = {}
            for _, r in data["jadwal_spesial"].iterrows():
                label = r.get("label", r.get("hari", ""))
                if label not in spesial_grouped:
                    spesial_grouped[label] = []
                spesial_grouped[label].append(f"{r['jam_mulai']}-{r['jam_selesai']}")
            sp_details = []
            for label, times in spesial_grouped.items():
                sp_details.append(f"{label} ({' & '.join(times)})")
            parts.append("Jadwal kunjungan spesial: " + ", ".join(sp_details) + ".")
        
        if parts:
            return "\n\n".join(parts)
        
        return "Mohon maaf, saat ini sedang tidak ada jadwal layanan kunjungan yang tersedia."

    if intent in ["barang_terlarang", "barang_bawaan", "barang_boleh"]:

        # Bersihkan kata umum
        query_words = [
            w for w in normalized.split()
            if w not in ["barang", "bawa", "makanan", "apa", "saja", "boleh", "dilarang", "saat", "kunjungan"]
        ]

        best_match = None
        best_score = 0

        for _, row in data["barang"].iterrows():
            nama_brg = str(row["nama_barang"]).lower()

            score = fuzz.partial_ratio(normalized, nama_brg)

            if nama_brg in normalized:
                score += 20

            if score > best_score and score >= 75:
                best_match = row
                best_score = score

        # ======================
        # MATCH FOUND
        # ======================
        if best_match is not None:

            status_map = {
                "makanan": "makanan",
                "minuman": "minuman",
                "barang": "barang",
                "tembakau": "produk tembakau",
                "adiktif": "produk tembakau"
            }

            status_text = status_map.get(
                str(best_match["status"]).lower(),
                best_match["status"]
            )

            return (
                f"Mohon maaf, {best_match['nama_barang']} "
                f"termasuk {status_text} yang {best_match['keterangan']} "
                f"dibawa saat kunjungan."
            )

        # ======================
        # FALLBACK
        # ======================
        dilarang_items = data["barang"][data["barang"]["status"].str.lower() == "dilarang"]

        if not dilarang_items.empty:
            sampel = dilarang_items["nama_barang"].head(5).tolist()

            return (
                "Secara umum, barang yang dilarang dibawa saat kunjungan antara lain: "
                f"{', '.join(sampel)}, dan barang terlarang lainnya. "
                "Untuk daftar lengkap, silakan lihat menu informasi barang."
            )

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
        ans = random.choice(default_row.iloc[0]["jawaban"].split("|"))
    elif not res_rows.empty:
        ans = random.choice(res_rows.iloc[0]["jawaban"].split("|"))
    else:
        ans = "Maaf, saya belum memahami pertanyaan Anda."

    if "{jadwal}" in ans:
        ans = ans.replace("{jadwal}", get_jadwal_summary_text(data))
    
    return ans

if __name__ == "__main__":
    sys.stdout = sys.stderr
    try:
        input_text = ""
        jadwal_base64 = None
        jadwal_spesial_base64 = None

        args = sys.argv[1:]
        if args:
            input_text = args[0]
            i = 1
            while i < len(args):
                if args[i] == "--jadwal-base64" and i + 1 < len(args):
                    jadwal_base64 = args[i + 1]
                    i += 2
                elif args[i] == "--jadwal-spesial-base64" and i + 1 < len(args):
                    jadwal_spesial_base64 = args[i + 1]
                    i += 2
                else:
                    i += 1

        data = load_all(jadwal_base64, jadwal_spesial_base64)
        if "error" in data:
            result = {"response": "Sistem sedang gangguan.", "error": data["error"]}
        else:
            ans = get_response(input_text, data)
            result = {"response": ans}
    except Exception as e:
        result = {"response": "Terjadi kesalahan internal.", "error": str(e)}

    sys.stdout = original_stdout
    print(json.dumps(result))
