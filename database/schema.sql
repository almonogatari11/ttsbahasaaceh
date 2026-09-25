-- =====================================================
-- Database: tts_aceh (SQLite)
-- Website TTS (Teka-Teki Silang) Bahasa Aceh
-- =====================================================
-- File ini dijalankan OTOMATIS oleh database.php saat pertama kali
-- website diakses (kalau file database/tts_aceh.sqlite belum ada).
-- Tidak perlu dijalankan manual, tidak perlu shell/SSH.

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS paket (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nomor_paket INTEGER NOT NULL UNIQUE,
    grid_baris INTEGER NOT NULL,
    grid_kolom INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS soal (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    paket_id INTEGER NOT NULL,
    nomor_soal INTEGER NOT NULL,
    arah TEXT NOT NULL CHECK (arah IN ('mendatar','menurun')),
    baris_mulai INTEGER NOT NULL,
    kolom_mulai INTEGER NOT NULL,
    panjang INTEGER NOT NULL,
    clue_aceh TEXT NOT NULL,
    clue_indonesia TEXT NOT NULL,
    jawaban_aceh TEXT NOT NULL,
    FOREIGN KEY (paket_id) REFERENCES paket(id) ON DELETE CASCADE
);

-- Progres tiap akun per paket
CREATE TABLE IF NOT EXISTS progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    paket_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'belum' CHECK (status IN ('belum','sedang','selesai')),
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (user_id, paket_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (paket_id) REFERENCES paket(id) ON DELETE CASCADE
);

-- Jawaban terakhir yang diinput pengguna, per soal
CREATE TABLE IF NOT EXISTS jawaban_user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    soal_id INTEGER NOT NULL,
    jawaban_input TEXT NOT NULL DEFAULT '',
    benar INTEGER NOT NULL DEFAULT 0,
    updated_at TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (user_id, soal_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (soal_id) REFERENCES soal(id) ON DELETE CASCADE
);

-- SQLite tidak punya "ON UPDATE CURRENT_TIMESTAMP" seperti MySQL,
-- jadi updated_at diperbarui lewat trigger berikut:
CREATE TRIGGER IF NOT EXISTS trg_progress_updated_at
AFTER UPDATE ON progress
FOR EACH ROW
BEGIN
    UPDATE progress SET updated_at = datetime('now') WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS trg_jawaban_user_updated_at
AFTER UPDATE ON jawaban_user
FOR EACH ROW
BEGIN
    UPDATE jawaban_user SET updated_at = datetime('now') WHERE id = NEW.id;
END;

-- Catatan: tabel `sessions` (MySQL) dihapus — tidak diperlukan lagi karena
-- hosting gratis (shared hosting) tidak me-restart filesystem seperti Railway,
-- jadi session PHP bawaan (file-based) sudah cukup dan lebih sederhana.
