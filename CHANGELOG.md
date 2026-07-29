# Changelog

## 2.0.2 – 2026-07-29

- Rededatum wird primär aus `Sitzung vom TT.MM.JJJJ` im Videotitel gelesen und bei bestehenden Beiträgen korrigiert
- sichtbarer Fortschrittshinweis während manueller Synchronisierungen und Veröffentlichungsdatum in der Statustabelle
- fehlende Artikellinks fallen auf den bereinigten Videotitel zurück; veraltete automatisch importierte Artikelbilder werden gelöst
- automatische Updates werden in der Plugin-Liste als unterstützt erkannt
- Plugin- und Autorenwebsite auf `oliveroehme.de` aktualisiert

## 2.0.1 – 2026-07-29

- geschützter Backend-Wipe für synchronisierte Beiträge, Plugin-Medien, Legacy-Locks und Einstellungen
- ungenutzte Kategorie „Bundestagsrede“ wird beim Wipe entfernt
- verwaiste Download-Cronjobs werden mitsamt ihren Locks entfernt; der automatische Abgleich bleibt bis zum nächsten manuellen Start pausiert

## 2.0.0 – 2026-07-29

- Bundestag-Embeds und alle zugehörigen Darstellungsoptionen entfernt
- Artikeltitel als echter Beitragstitel mit bereinigtem Videotitel als Fallback
- lokaler Videoblock als Inhalt jedes synchronisierten Beitrags
- Artikelbild als echtes, manuell änderbares Beitragsbild und Video-Poster
- Beitragsdatum aus dem `date`-Metatag der Bundestag-Videoseite
- automatische Kategorie „Bundestagsrede“ für jeden Videobeitrag

## 1.1.3 – 2026-07-29

- tatsächliches Rededatum wird bei jeder Synchronisierung als Beitragsdatum übernommen
- obsoleter Quellenhinweis unter dem Videoblock entfernt

## 1.1.2 – 2026-07-28

- Titeloptionen direkt am Titelblock und Thumbnail-Option direkt am Videoblock
- automatische Laufzeitmigration bestehender Query-Einstellungen auf die Kindblöcke

## 1.1.1 – 2026-07-28

- Speicherung und Weitergabe der erweiterten Query-Darstellungsoptionen korrigiert
- Artikeltitel und Artikelbild-URL in der Statustabelle ergänzt

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
