# Architektur

Das Plugin trennt Quelle, Persistenz, Synchronisierung, Download und Ausgabe. Es baut keine eigene Loop-Engine, sondern ergänzt `core/query`.

## Laufzeitkomponenten

- `Plugin` verdrahtet die Komponenten und enthält Aktivierung/Deaktivierung.
- `Settings` validiert Redner-ID, Intervall, Modus, Qualität und Größenlimit.
- `Speaker_Catalog` lädt die offizielle Bundestag-Rednerliste, cached sie zwölf Stunden und fällt bei Störungen auf die mitgelieferte Kopie zurück.
- `URL_Resolver` erzeugt URLs nur aus validierten IDs und verwaltet die Host-Allowlist.
- `Source_Client` kapselt begrenzte WordPress-HTTP-Anfragen.
- `List_Parser`, `Video_Parser` und `Article_Parser` lesen DOM-basiert mit mehreren kurzen Selektor-Fallbacks.
- `Speech_Post_Type` registriert CPT und REST-sichtbare Metadaten.
- `Speech_Repository` führt idempotente Upserts und Statusabfragen aus.
- `Sync_Status` hält die Statusübergänge unabhängig vom WordPress-Speicher.
- `Synchronizer` koordiniert Liste, Detailseiten und Upserts.
- `Sync_Lock` verhindert parallele Metadatenläufe.
- `MP4_Validator` prüft Host, HTTP-Status, MIME-Type und Größe.
- `Download_Service` prüft hochwertige MP4-Fallbacks und streamt mit WordPress-Funktionen in die Mediathek.
- `Subtitle_Service` lädt SRT-Untertitel, konvertiert sie in WebVTT und legt sie als verknüpfte Medien ab.
- `Legacy_Article_Image_Cleanup` entfernt einmalig alte Medien mit der eindeutigen Artikelbild-Importmarkierung.
- `Download_Lock` verhindert doppelte Attachments bei parallelen Jobs.
- `Wipe_Service` entfernt ausschließlich Plugin-Beiträge, zugehörige Medien und Legacy-Zustände.
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
5. Ein optionaler Artikel-Link wird aufgelöst; Artikeltitel und Open-Graph-Bild werden gecacht geparst.
6. `_mdb_video_id` dient als idempotenter Schlüssel.
7. Der Artikeltitel wird als `post_title` gesetzt; ersatzweise dient der bereinigte Videotitel.
8. Der Videoblock wird bei leeren Beiträgen als Inhalt gesetzt; sein Poster nutzt vorrangig ein redaktionelles Beitragsbild und sonst die externe Artikelbild-URL.
9. Im Modus `automatic` werden einzelne lokale Download-Cronjobs geplant; bestehende Videos ohne geprüften Untertitel werden einmalig nachgezogen.
10. Fehler einer Rede stoppen nicht die übrige Synchronisierung.
11. Fehlende Reden werden nie gelöscht.

## Statusmodell

- `download_available`: Metadaten sind verfügbar; der lokale Download kann gestartet werden.
- `download_pending`: lokaler Download ist eingeplant.
- `downloaded`: ein verknüpftes Attachment existiert.
- `download_failed`: Downloadfehler; Metadaten und Originalquelle bleiben nutzbar.
- `sync_error`: Detailseite konnte nicht synchronisiert werden.
- `not_seen`: Eintrag fehlte in der letzten erfolgreich geparsten Liste.

Metadatenfehler und Downloadfehler bleiben getrennt. Eine erfolgreiche Metadatenaktualisierung löscht einen bestehenden Downloadfehler nicht.

## Sicherheitsgrenzen

- IDs sind ausschließlich numerisch.
- Quell- und Download-URLs müssen HTTPS, Port 443 und einen exakt erlaubten Host verwenden.
- HTML-Antworten sind auf 5 MB begrenzt.
- MP4-Validierung folgt keinen Redirects und akzeptiert ausschließlich `video/mp4`.
- MP4-Fallbacks bleiben auf die konfigurierten hochwertigen 1080p-Profile beschränkt.
- Untertitelantworten sind auf 2 MB begrenzt und müssen gültige SRT-Zeitmarken enthalten.
- Der GET-Fallback überträgt mit `Range: bytes=0-0` höchstens ein Byte.
- Nach dem Stream-Download prüft WordPress den tatsächlichen Dateityp erneut.
- Artikelbilder bleiben extern und werden nicht in die Mediathek kopiert.
- Admin-Aktionen erfordern `manage_options` und eine aktionsspezifische Nonce.
- Der Wipe ist zusätzlich durch eine Bestätigungsabfrage geschützt und löscht die Kategorie nur unbenutzt.
- Interne Fehler-, Download- und Statusmetadaten werden nicht öffentlich über REST exponiert.
- Der Videoblock gibt ausschließlich lokal importierte MP4-Dateien und WebVTT-Untertitel aus.

## Parserdrift

Die Parser verwenden mehrere kurze Fallbacks (`m-videos__headline h1`, `main h1`, `h1`, Open-Graph-Titel) statt eines langen DOM-Pfads. Wenn keine Rede oder kein Titel erkennbar ist, entsteht ein verständlicher `Parser_Exception`; eine leere Synchronisierung wird dadurch nicht als legitimer Zustand behandelt. Optionale Artikelmetadaten fallen bei fehlenden Links oder Bildern auf die Videodaten zurück.

## Updates

`Update URI` verweist auf GitHub. `Release_Updater` verarbeitet nur Releases aus `ooehme/tt5-btag-speech` und nur das zum stabilen Tag passende Asset `mdb-bundestag-speeches-X.Y.Z.zip`. `.github/workflows/release.yml` erzeugt dieses Archiv aus einem Tag. Dadurch startet der Updatepfad erst, sobald das Repository ein korrektes Release enthält.
