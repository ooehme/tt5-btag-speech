# Changelog

## 1.1.0 – 2026-07-28

- Query-Darstellung mit Anzahl, Sortierung, Offset und optional gekürzten Redentiteln
- optionale Artikeltitel und Artikelbilder aus dem verlinkten Bundestag-Artikel
- begrenzter, MIME-geprüfter und deduplizierter Artikelbild-Import bei Videodownloads

## 1.0.0 – 2026-07-28

- erster produktionsfähiger Stand
- konfigurierbare Redner-, Intervall-, Download-, Qualitäts- und Größenoptionen
- robuster Listen- und Videoparser mit bereinigten Offline-Fixtures
- idempotenter `mdb_speech`-Import ohne Überschreiben redaktioneller Titel
- getrennte Metadaten- und Downloadjobs mit Locks und Fehlerstatus
- sicherer, gestreamter MP4-Import mit HEAD-/Range-Prüfung
- Admin-Oberfläche, WP-Cron und WP-CLI
- dynamische Gutenberg-Blöcke und Core-Query-Variation
- Click-to-load-Frontend und SSR-Fallbacks
- GitHub Actions für PHP 8.0/8.5, JavaScript und Release-ZIPs
- GitHub-Release-Updater
