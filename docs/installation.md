# Installation und Betrieb

## Erstinstallation

1. Das Release-ZIP in WordPress hochladen und aktivieren.
2. **Bundestagsreden → Synchronisierung** öffnen.
3. Redner-ID und Redenlisten-Filter prüfen; `12404` sowie `21244 OR 12404` sind nur die Entwicklungsstandards für Steffen Janich.
4. Intervall und Downloadmodus wählen.
5. Bei lokalen Downloads Qualität und maximale Dateigröße passend zum Hosting festlegen.
6. Eine manuelle Synchronisierung starten und die Statustabelle prüfen.

## Downloadmodi

- `embed_only`: kein lokaler Download; Ausgabe über Bundestag-Embed.
- `automatic`: neue Reden werden nach dem Metadatenlauf per separatem Cronjob lokal importiert.
- `local`: lokale Speicherung ist vorgesehen, Downloads werden aber bewusst über Backend oder CLI gestartet.

Bei jedem Downloadfehler bleibt der Embed verfügbar. Fehlgeschlagene Downloads können gesammelt neu eingeplant werden.

## Cron

WP-Cron muss auf der Website funktionieren. Für wenig besuchte Installationen empfiehlt sich ein echter System-Cron, der regelmäßig `wp-cron.php` aufruft.

## Dateigrößen

Das konfigurierte Limit wird vor und nach dem Download geprüft. PHP-, Webserver- und WordPress-Uploadlimits können zusätzlich greifen. Der Download wird zunächst in eine temporäre Datei gestreamt und anschließend mit `media_handle_sideload()` importiert.

## Updates

Produktive Updates werden über getaggte GitHub-Releases verteilt:

1. Versionsnummer in Plugin-Header, Block-Metadaten und Changelog erhöhen.
2. Qualitätsprüfungen ausführen.
3. Tag `vX.Y.Z` veröffentlichen.
4. Der Release-Workflow erzeugt `mdb-bundestag-speeches-X.Y.Z.zip`.
5. WordPress erkennt das neue Release über den externen Updater.

Ohne zum Tag passendes, exakt benanntes ZIP-Asset wird kein Update angeboten.
