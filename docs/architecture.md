# Architektur

Das Plugin trennt Quelle, Persistenz, Synchronisierung, Download und Ausgabe. Es baut keine eigene Loop-Engine, sondern ergänzt `core/query`.

## Laufzeitkomponenten

- `Plugin` verdrahtet die Komponenten und enthält Aktivierung/Deaktivierung.
- `Settings` validiert Redner-ID, Intervall, Modus, Qualität und Größenlimit.
- `URL_Resolver` erzeugt URLs nur aus validierten IDs und verwaltet die Host-Allowlist.
- `Source_Client` kapselt begrenzte WordPress-HTTP-Anfragen.
- `List_Parser` und `Video_Parser` lesen DOM-basiert mit mehreren kurzen Selektor-Fallbacks.
- `Speech_Post_Type` registriert CPT und REST-sichtbare Metadaten.
- `Speech_Repository` führt idempotente Upserts und Statusabfragen aus.
- `Sync_Status` hält die Statusübergänge unabhängig vom WordPress-Speicher.
- `Synchronizer` koordiniert Liste, Detailseiten und Upserts.
- `Sync_Lock` verhindert parallele Metadatenläufe.
- `MP4_Validator` prüft Host, HTTP-Status, MIME-Type und Größe.
- `Download_Service` streamt mit WordPress-Funktionen in die Mediathek.
- `Download_Lock` verhindert doppelte Attachments bei parallelen Jobs.
- `Cron` plant Metadaten und einzelne Downloads getrennt.
- `Admin` verarbeitet ausschließlich berechtigte, Nonce-geschützte Aktionen; die View ist separat.
- `CLI` stellt die drei WP-CLI-Kommandos bereit.
- `Blocks` registriert Block-Metadaten und Assets.
- `Speech_Video_Renderer` rendert Player und Click-to-load.
- `Block_Renderer` rendert die drei dynamischen Felder.
- Editor-Blocktypen und Query-Variation liegen getrennt unter `assets/editor/`.
- `Release_Updater` liest ausschließlich das neueste GitHub-Release und dessen benanntes ZIP-Asset.

## Synchronisationsablauf

1. Ein atomarer Options-Lock wird gesetzt.
2. Die Redenliste wird begrenzt abgerufen und geparst.
3. Erst nach erfolgreichem Listen-Parsing werden bestehende Reden als `not_seen` markiert.
4. Jede Videoseite wird separat abgerufen und geparst.
5. `_mdb_video_id` dient als idempotenter Schlüssel.
6. Quelldaten werden aktualisiert; `post_title` wird nur beim ersten Import gesetzt.
7. Im Modus `automatic` werden einzelne Download-Cronjobs geplant.
8. Fehler einer Rede stoppen nicht die übrige Synchronisierung.
9. Fehlende Reden werden nie gelöscht.

## Statusmodell

- `embed_available`: Metadaten und Embed sind verfügbar.
- `download_pending`: lokaler Download ist eingeplant.
- `downloaded`: ein verknüpftes Attachment existiert.
- `download_failed`: Downloadfehler; Metadaten und Embed bleiben nutzbar.
- `sync_error`: Detailseite konnte nicht synchronisiert werden.
- `not_seen`: Eintrag fehlte in der letzten erfolgreich geparsten Liste.

Metadatenfehler und Downloadfehler bleiben getrennt. Eine erfolgreiche Metadatenaktualisierung löscht einen bestehenden Downloadfehler nicht.

## Sicherheitsgrenzen

- IDs sind ausschließlich numerisch.
- Quell- und Download-URLs müssen HTTPS, Port 443 und einen exakt erlaubten Host verwenden.
- HTML-Antworten sind auf 5 MB begrenzt.
- MP4-Validierung folgt keinen Redirects und akzeptiert ausschließlich `video/mp4`.
- Der GET-Fallback überträgt mit `Range: bytes=0-0` höchstens ein Byte.
- Nach dem Stream-Download prüft WordPress den tatsächlichen Dateityp erneut.
- Admin-Aktionen erfordern `manage_options` und eine aktionsspezifische Nonce.
- Interne Fehler-, Download- und Statusmetadaten werden nicht öffentlich über REST exponiert.
- Iframes erhalten eine Sandbox und werden im Click-to-load-Modus erst nach Interaktion erstellt.

## Parserdrift

Die Parser verwenden mehrere kurze Fallbacks (`m-videos__headline h1`, `main h1`, `h1`, Open-Graph-Titel) statt eines langen DOM-Pfads. Wenn keine Rede oder kein Titel erkennbar ist, entsteht ein verständlicher `Parser_Exception`; eine leere Synchronisierung wird dadurch nicht als legitimer Zustand behandelt.

## Updates

`Update URI` verweist auf GitHub. `Release_Updater` verarbeitet nur Releases aus `ooehme/tt5-btag-speech` und nur das zum stabilen Tag passende Asset `mdb-bundestag-speeches-X.Y.Z.zip`. `.github/workflows/release.yml` erzeugt dieses Archiv aus einem Tag. Dadurch startet der Updatepfad erst, sobald das Repository ein korrektes Release enthält.
