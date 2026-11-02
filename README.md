# Vorstellungsrunde Erhalt der Fachkunde Elektrofachkraft

Eigenständige neue Miniapp. Neues privates GitHub-Repository und eigenen Server-Unterordner verwenden. Die Themenabfrage nicht überschreiben. PHP 8.1+ auf Apache/PHP-Webspace. Keine Datenbankinstallation oder externen Dienste. Alle Pfade relativ. Original-TCS-Logo und identisches kompaktes Design aus Themenabfrage V3.

## Zugang und Upload

Dozentenansicht: admin.php im gewählten Ordner.
Initiales Dozenten-Passwort: `TCS-lAoGPwKRXVQxWk1y`
Vor Veröffentlichung in web/config.php ändern; Repository privat halten.

ZIP entpacken, Inhalt ins neue GitHub-Repository übernehmen. Unter Actions den erfolgreichen Workflow öffnen, unter Summary das Artefakt TCS-EFK-Vorstellungsrunde-Web herunterladen. Äußere Download-ZIP und enthaltene Web-ZIP entpacken, deren Inhalt in den gewünschten Webspace-Ordner laden. Alternativ Inhalt von web/ direkt hochladen. HTTPS verwenden.

## Punktdateien – im neuen Repository manuell anlegen

| Pfad | Gegenüber Themenabfrage V3 |
| --- | --- |
| .github/workflows/web-package.yml | geändert: neuer Workflow- und Artefaktname |
| .gitignore | unverändert |
| web/.htaccess | unverändert |
| web/data/.htaccess | unverändert |

Auch .github ist ein Punktordner. Weil dies ein NEUES Repository ist, alle vier Dateien dort manuell erstellen, falls der Upload sie auslässt. Inhalte stehen in der ZIP. Den alten Workflow der Themenabfrage nicht ändern. GitHub legt Ordner beim Anlegen von .github/workflows/web-package.yml automatisch an.

## Speicherung und Bedienung

Sieben Originalfragen als Pflicht-Freitext mit maximal 3.000 Zeichen je Frage; ohne Aufgabenbezug „Nicht zutreffend“ eingeben. Absenden speichert alle Antworten gemeinsam. Danach bleiben Felder und Button gesperrt, auch nach Neuladen im selben Browser. Kein Zwischenspeichern von Antworttexten im lokalen Speicher. Eine PHP-Sitzung und eine lokale Rundensperre verhindern normale Mehrfachabgaben; andere Browser oder gelöschte Browserdaten ermöglichen eine erneute Teilnahme. PHP-Sitzung enthält technische Sitzungsdaten und die Rundensperre, keine Antworttexte oder Verknüpfung mit dem Antwortbestand.

Datenordner und data/answers.php werden automatisch angelegt. Tool-Ordner muss für PHP beschreibbar sein, wenn data/ fehlt; sonst genügt Schreibzugriff auf data/. Antwortdatei ist durch PHP-Abbruchcode und data/.htaccess geschützt. web/.htaccess schützt Konfiguration und Hilfscode und muss beim Upload erhalten bleiben bzw. manuell angelegt werden.

Antworten werden je Frage separat gesammelt und gemischt. Keine gespeicherten Fragebogenbündel, Namenfelder, E-Mails, IP-Adressen, Teilnehmerkennungen, Zeitstempel oder Antwort-IDs. Gesamtzähler zählt vollständig eingereichte Rückmeldungen. Keine technische Zuordnung zwischen Antworten verschiedener Fragen. Die Reihenfolge ist kein Teilnehmerindex.

Freitext lässt sich nicht pauschal als frei von personenbezogenen Daten garantieren: Teilnehmer könnten selbst identifizierende Details eintragen. Die Oberfläche fordert deshalb ausdrücklich dazu auf, solche Angaben wegzulassen. Wiedererkennung aus dem Inhalt oder durch Live-Beobachtung einzelner Eingänge ist technisch nicht vollständig ausgeschlossen. Webhost-Zugriffsprotokolle werden von dieser Anwendung nicht gesteuert. Keine Tracker, CDNs oder externen Ressourcen.

Dozentenansicht: Rückmeldungszahl und Freitexte nach Frage, Aktualisieren, CSV mit Frage und Antwort. Clear fragt nach, löscht alle aktuellen Antworten und setzt den Zähler zurück. Anschließend können dieselben Browser nach Neuladen wieder teilnehmen. Offene Formulare alter Runden können nicht in die neue Runde absenden. Exportierte CSVs und eigene Backups werden nicht gelöscht.

Bei späteren Updates data/answers.php und eigenes Passwort beibehalten. Fragen in config.php nur zwischen Kursen ändern; anschließend Clear. Ein reines Layoutupdate löscht keine Daten.

## Tests

`python3 tests/integration.py` mit PHP und Python 3, isoliert von produktiven Daten. Freitextspeicherung, Antworttrennung, Eingabeprüfung, Mehrfachabgaben, HTML-Escaping, CSV, Zugriffsschutz und Clear.
