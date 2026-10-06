-- Jalankan pada SQL Editor project Supabase yang dipakai untuk arsip metadata.
-- Tabel ini menyimpan metadata arsip saja, bukan user/session/audit MySQL.
CREATE TABLE IF NOT EXISTS public.archive_surat_metadata (
    search_id text PRIMARY KEY,
    agenda_number integer NOT NULL,
    name text,
    title text,
    nature text NOT NULL,
    letter_number text,
    letter_date date,
    received_date date NOT NULL,
    sender text,
    proposer text,
    description text,
    notes text,
    drive_url text,
    dispositions jsonb NOT NULL DEFAULT '[]'::jsonb,
    archived_at timestamptz NOT NULL DEFAULT now()
);

ALTER TABLE public.archive_surat_metadata ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON TABLE public.archive_surat_metadata FROM PUBLIC, anon, authenticated, service_role;
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE public.archive_surat_metadata TO service_role;
