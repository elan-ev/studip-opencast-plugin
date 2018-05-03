# Stud.IP-Opencast-Plugin

This plugin serves as a connection between [Opencast](http://opencast.org) and
the LMS [Stud.IP](http://studip.de/).

Mit dem Opencast-Plugin kann eine Verbindung zwischen einer Opencast-Installation
und dem Lernmanagementsystem Stud.IP hergestellt werden. Dafür werden die Prozesse 
für das Management von Audio- und Videoinhalten, vor allem für die
Aufzeichnung und Distribution von Lehrveranstaltungen, im Lernmanagementsystem
abgebildet. Bei der Entwicklung des Plugins wurden folgende Anforderungen
beachtet:

*Transparenz der Aufzeichnungstechnik*: Die DozentIn kann in ihrer
Veranstaltung direkt erkennen, ob der gebuchte Veranstaltungsraum mit
entsprechender Aufzeichnungstechnik ausgerüstet ist. Dies wird im Plugin durch
die Verknüpfung von Stud.IP-Ressourcen mit den entsprechenden Capture-Agents
aus Opencast sichergestellt. DozentInnen benötigen hierbei kein technisches
Vorwissen über die verwendete Aufzeichnungstechnik.

*Einfache Aufzeichnungsplanung*: Vorlesungsaufzeichnugen sollen direkt aus dem
Kurs im LMS von der DozentIn geplant werden können. Im Kurs verfügbare
Metadaten sollen bei der Planung berücksichtigt werden. Umgesetzt wird dies
mit einer eigenen Planungsansicht im Plugin, basierend auf der
Ablaufplanverwaltung. Hiermit entfällt die mehrfache Eingabe von kursbezogenen
Metadaten.

*Kontrolle der Sichtbarkeit*: Die DozentIn soll die Sichtbarkeit jeder
Aufzeichnung individuell festlegen können. Dies ist durch ein eigenes
Einstellungsmenü in der Kursansicht möglich. Hier kann die DozentIn pro
verfügbarer Aufzeichung über die Sichtbarkeit entscheiden.
