-- Big Kahuna V2 cleanup.
-- Production-safe variant: intentionally does NOT delete or modify any existing data.
-- The local migration removed an obsolete customer WhatsApp setting. Because production
-- data must remain untouched, that cleanup is not applied here.
SELECT 'V2 cleanup skipped: existing production data preserved.' AS status;
