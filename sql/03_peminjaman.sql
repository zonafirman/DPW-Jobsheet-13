-- Jobsheet 12: tabel peminjaman, menghubungkan buku dan anggota
-- Jalankan: psql -d simpus_mini -f sql/03_peminjaman.sql

CREATE TABLE IF NOT EXISTS peminjaman (
    id SERIAL PRIMARY KEY,
    buku_id INTEGER NOT NULL REFERENCES buku(id),
    anggota_id INTEGER NOT NULL REFERENCES anggota(id),
    tanggal_pinjam DATE NOT NULL DEFAULT CURRENT_DATE,
    tanggal_kembali DATE,
    status VARCHAR(20) NOT NULL DEFAULT 'dipinjam'
);

-- Database lama (Jobsheet 11) sempat membuat tabel ini dengan kolom
-- id_buku / id_anggota. Kalau masih ada, namanya disamakan dengan Jobsheet 12.
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_name = 'peminjaman' AND column_name = 'id_buku') THEN
        ALTER TABLE peminjaman RENAME COLUMN id_buku TO buku_id;
    END IF;
    IF EXISTS (SELECT 1 FROM information_schema.columns
               WHERE table_name = 'peminjaman' AND column_name = 'id_anggota') THEN
        ALTER TABLE peminjaman RENAME COLUMN id_anggota TO anggota_id;
    END IF;
END $$;
