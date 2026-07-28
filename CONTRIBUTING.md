# Beitragen

Änderungen sollen klein, nach Verantwortlichkeit getrennt und rückwärtskompatibel zu PHP 8.0 sein.

Vor einem Commit:

1. PHP-Lint ausführen.
2. `php tests/run.php` ausführen.
3. JavaScript mit `node --check` prüfen.
4. `node tests/editor.test.cjs` ausführen.
5. Keine unbearbeiteten Live-Antworten, Medien oder Zugangsdaten committen.

Live-HTML darf nur bereinigt und auf parserrelevante Ausschnitte reduziert unter `tests/fixtures/` gespeichert werden.
