# MDB Bundestagsreden

Produktionsfähiges WordPress-Plugin zum Synchronisieren und Darstellen von Reden aus der Mediathek des Deutschen Bundestages.

## Funktionen

- konfigurierbare Bundestag-Redner-ID und interne Filter-IDs
- idempotenter Import als öffentlicher Custom Post Type `mdb_speech`
- redaktioneller Beitragstitel bleibt bei späteren Synchronisierungen unverändert
- Embed-only-, automatischer und manueller lokaler Downloadmodus
- gestreamter MP4-Import mit Host-, Status-, MIME- und Größenprüfung
- `HEAD`-Prüfung mit begrenztem `Range: bytes=0-0`-Fallback
- WP-Cron, Synchronisations- und Download-Locks sowie WP-CLI
- dynamische Gutenberg-Blöcke und eine native `core/query`-Variation
- optionale Artikeltitel und Artikelbilder mit sicherem Fallback auf die Videodaten
- entfernte Artikelbilder im Embed-Modus, deduplizierter Bildimport bei lokalen Downloads
- datenschutzfreundliche Click-to-load-Ausgabe
- GitHub-Release-Updates, sobald Releases im Repository verfügbar sind

## Anforderungen

- WordPress 6.7 oder neuer
- PHP 8.0 oder neuer mit DOM-Erweiterung
- Schreibzugriff auf das WordPress-Upload-Verzeichnis für lokale Downloads

## Installation

1. Das versionierte Release-ZIP `mdb-bundestag-speeches-X.Y.Z.zip` herunterladen.
2. Unter **Plugins → Installieren → Plugin hochladen** installieren und aktivieren.
3. Unter **Bundestagsreden → Synchronisierung** die Redner-ID und den gewünschten Modus festlegen.
4. **Jetzt synchronisieren** ausführen.
5. Im Block-Editor die Query-Variation **Bundestagsreden** oder einzelne MDB-Blöcke einfügen.

Weitere Hinweise stehen in [docs/installation.md](docs/installation.md).

## Gutenberg

Verfügbar sind:

- `mdb/speech-video` mit `auto`, `local` und `embed`
- `mdb/speech-topic`
- `mdb/speech-session`
- `mdb/speech-source-link`
- Query-Variation `mdb/speeches` für `core/query`

Der Videoblock liest im Core Query Loop den aktuellen `postId`-Kontext. Das `core/post-template` bleibt frei gestaltbar; Pagination übernehmen die Core-Blöcke.

Die Query-Variation bietet Anzahl, Sortierung und Offset. Die Optionen für gekürzte Redentitel und Artikeltitel befinden sich direkt am Titelblock; das Artikelbild wird direkt am Videoblock aktiviert. Fehlende Artikelmetadaten ändern die normale Ausgabe nicht.

## WP-CLI

```bash
wp mdb-speeches sync
wp mdb-speeches download
wp mdb-speeches retry
```

## Entwicklung und Tests

Die Standardsuite benötigt keine Live-Anfragen:

```bash
php tests/run.php
node tests/editor.test.cjs
find . -type f -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
find assets tests -type f \( -name '*.js' -o -name '*.cjs' \) -print0 | xargs -0 -n1 node --check
```

Ein optionaler Live-Test kann bewusst zugeschaltet werden:

```bash
MDB_RUN_LIVE_TESTS=1 php tests/run.php
```

Die bereinigten HTML-Fixtures unter `tests/fixtures/` stammen von den verifizierten Beispielendpunkten. Details stehen in [docs/architecture.md](docs/architecture.md).

## Updates

Der Updater akzeptiert ausschließlich das zum Tag passende Asset `mdb-bundestag-speeches-X.Y.Z.zip` aus dem neuesten GitHub-Release von `ooehme/tt5-btag-speech`. Ohne passendes Release-Asset bleibt der Updater bewusst inaktiv.

## Lizenz

GPL-2.0-or-later. Siehe [LICENSE](LICENSE).
