=== MDB Bundestagsreden ===
Contributors: ooehme
Tags: bundestag, speeches, video, gutenberg, query loop
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Synchronisiert Bundestagsreden und stellt sie als dynamische Gutenberg-Blöcke und native Query-Loop-Variation bereit.

== Beschreibung ==

MDB Bundestagsreden importiert die Reden eines konfigurierbaren Bundestagsabgeordneten als eigene Beiträge und lädt die Videos nach strenger Host-, MIME- und Größenprüfung lokal in die Mediathek.

Funktionen:

* konfigurierbare Redner-ID und Synchronisationsintervalle
* Artikeltitel als Beitragstitel mit bereinigtem Videotitel als Fallback
* automatischer oder manuell gestarteter lokaler Download
* sicherer, gestreamter MP4-Import
* WP-Cron, Download-Wiederholung und WP-CLI
* dynamische Video-, TOP-, Sitzungs- und Quellenblöcke
* native Bundestagsreden-Variation von core/query
* Artikelbild als manuell änderbares Beitragsbild und Video-Poster
* Beitragsdatum aus dem date-Metatag der Videoseite
* lokales Video im Inhalt jedes importierten Beitrags
* automatische Kategorie Bundestagsrede
* optionale Click-to-load-Ausgabe
* Updates über versionierte GitHub-Release-ZIPs

== Installation ==

1. Release-ZIP unter Plugins > Installieren > Plugin hochladen installieren.
2. Plugin aktivieren.
3. Unter Bundestagsreden > Synchronisierung die Redner-ID und den Modus festlegen.
4. Jetzt synchronisieren ausführen.
5. Im Editor die Query-Variation Bundestagsreden einfügen.

== Häufige Fragen ==

= Werden fehlende Reden gelöscht? =

Nein. Sie werden als not_seen markiert und bleiben redaktionell erhalten.

= Was passiert bei einem Downloadfehler? =

Der Link zur Originalquelle bleibt verfügbar. Fehlgeschlagene Downloads können erneut eingeplant werden.

= Was wird bei der Deinstallation gelöscht? =

Plugin-Einstellungen, Locks und Cronjobs werden entfernt. Synchronisierte Beiträge und Medien bleiben als redaktionelle Inhalte erhalten.

== Changelog ==

= 2.0.0 =
* Entfernt alle Bundestag-Embeds und zugehörigen Optionen.
* Verwendet Artikeltitel, Artikelbild und Video-Metadatum direkt für den Beitrag.
* Fügt den lokalen Videoblock als Beitragsinhalt ein.

= 1.1.3 =
* Verwendet auch bei bestehenden Beiträgen das tatsächliche Rededatum als Beitragsdatum.
* Entfernt den obsoleten Quellenhinweis unter dem Videoblock.

= 1.1.2 =
* Verschiebt Titeloptionen direkt an den Titelblock und die Thumbnail-Option direkt an den Videoblock.
* Übernimmt Einstellungen bestehender Abfrageblöcke automatisch auf die betroffenen Kindblöcke.

= 1.1.1 =
* Behebt die Speicherung und Weitergabe der drei erweiterten Darstellungsoptionen.
* Zeigt Artikeltitel und Artikelbild-URL in der Statustabelle.

= 1.1.0 =

* Erweiterte Query-Darstellung mit Anzahl, Sortierung und Offset.
* Optionale Artikeltitel und Artikelbilder mit Fallback.
* Sicherer, deduplizierter Artikelbild-Import bei Videodownloads.

= 1.0.0 =

* Erster produktionsfähiger Stand.
