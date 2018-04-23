# Stud.IP-Opencast-Plugin

This plugin serves as a connection between [Opencast](http://opencast.org) and
the LMS [Stud.IP](http://studip.de/).

Das Opencast-Plugin stellt eine Verbindung zwischen einer Opencast-Installation
und dem Lernmanagementsystem Stud.IP her. Das Plugin bildet hierbei die
Prozesse für das Management von Audio- und Videoinhalten, vor allem für die
Aufzeichnung und Distribution von Lehrveranstaltungen im Lernmanagementsystem
ab. Bei der Entwicklung des Plugins wurden folgende typische Anforderungen
beachtet:

*Transparenz der Aufzeichnungstechnik*: Die DozentIn kann in ihrer
Veranstaltung direkt erkennen, ob der gebuchte Veranstaltungsraum mit
entsprechender Aufzeichnungstechnik ausgerüstet ist. Dies wird im Plugin durch
die Verknüpfung von Stud.IP-Ressourcen mit korrespondierenden Capture Agents
aus Opencast sichergestellt. DozentInnen benötigen hierbei kein technisches
Vorwissen über die verwendete Aufzeichnungstechnik.

*Einfache Aufzeichnungsplanung*: Vorlesungsaufzeichnugen sollen direkt aus dem
Kurs im LMS von der DozentIn geplant werden können. Im Kurs verfügbare
Metadaten sollen bei der Planung berücksichtigt werden. Realisiert wurde dies
mit einer eigenen Planungsansicht im Plugin, basierend auf der
Ablaufplanverwaltung. Hiermit entfällt die mehrfache Eingabe von kursbezogenen
Metadaten.

*Kontrolle der Sichtbarkeit*: Die DozentIn soll die Sichtbarkeit jeder
Aufzeichnung individuell festlegen können. Dies wird durch eine eigenes
Einstellungsmenü in der Kursansicht realisiert. Hier kann die DozentIn pro
verfügbarer Aufzeichung über die Sichtbarkeit entscheiden.
