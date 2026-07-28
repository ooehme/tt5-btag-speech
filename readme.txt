=== MDB Bundestagsreden ===
Contributors: ooehme
Tags: bundestag, speeches, video, gutenberg, query loop
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Synchronisiert Bundestagsreden und stellt sie als dynamische Gutenberg-Blöcke und native Query-Loop-Variation bereit.

== Beschreibung ==

MDB Bundestagsreden importiert die Reden eines konfigurierbaren Bundestagsabgeordneten als eigene Beiträge. Videos können datenschutzfreundlich eingebettet oder nach strenger Host-, MIME- und Größenprüfung lokal in die Mediathek geladen werden.

Funktionen:

* konfigurierbare Redner-ID und Synchronisationsintervalle
* idempotente Beiträge ohne Überschreiben redaktioneller Titel
* Embed-only-, automatischer und manueller lokaler Modus
* sicherer, gestreamter MP4-Import
* WP-Cron, Download-Wiederholung und WP-CLI
* dynamische Video-, TOP-, Sitzungs- und Quellenblöcke
* native Bundestagsreden-Variation von core/query
* optionale Artikeltitel und Artikelbilder mit sicherem Fallback
* begrenzter, deduplizierter Artikelbild-Import bei lokalen Videodownloads
* Click-to-load ohne Player-Anfrage vor der Interaktion
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

Der Embed und der Link zur Originalquelle bleiben verfügbar. Fehlgeschlagene Downloads können erneut eingeplant werden.

= Was wird bei der Deinstallation gelöscht? =

Plugin-Einstellungen, Locks und Cronjobs werden entfernt. Synchronisierte Beiträge und Medien bleiben als redaktionelle Inhalte erhalten.

== Changelog ==

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
