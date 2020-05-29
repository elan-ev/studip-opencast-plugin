Stud.IP-Opencast-Plugin
=======================

*This plugin serves as a connection between [Opencast](http://opencast.org) and
the LMS [Stud.IP](http://studip.de/).*

*This is the development page for the Opencast plugin. To get a working version for your Stud.IP, head over to the [Stud.IP marketplace](https://develop.studip.de/studip/plugins.php/pluginmarket/presenting/details/dfd73b3d67c627be493536c1ae0e27c9). To build your own version, make sure to read how to [Build a working plugin zip](https://github.com/elan-ev/studip-opencast-plugin/wiki/Build-a-working-plugin-zip).*

Plugin-Version: 1.x  
Kompatibel zu Stud.IP Versionen: 4.0 - 4.5  
Kompatibel zu Opencast Versionen:
- Bis einschließlich Plugin-Version 1.2.113: 5.x, 6.x, 7.x, 8.x
- Nach Plugin-Version 1.2.113: 8.3+ (für einen Direktupload nach LTI-Anmeldung)

**Beachten Sie die [INSTALL.md](INSTALL.md), diese Pluginversion benötigt Konfigurationseinstellungen in Opencast!**

Mit dem Opencast-Plugin kann eine Verbindung zwischen einer
Opencast-Installation und dem Lernmanagementsystem Stud.IP hergestellt werden.
Dies ermöglicht die Erstellung, Verwaltung und Veröffentlichung von
Vorlesungsaufzeichnungen direkt in Stud.IP.

Der Fokus der Entwicklung liegt bei einer möglichst intuitiven und einfachen
Bedienung. Somit ist für dis Verwendung des Plugins kaum Vorwissen nötig.
Außerdem wurde sichergestellt, dass das Plugin folgende
Anforderungen erfüllt:

*Transparenz der Aufzeichnungstechnik:* Die DozentInnen können in ihren
Veranstaltung direkt erkennen, ob der gebuchte Veranstaltungsraum mit
entsprechender Aufzeichnungstechnik ausgerüstet ist. Dies wird im Plugin durch
die Verknüpfung von Stud.IP-Ressourcen mit Aufzeichnungsgeräten in Opencast
sichergestellt. DozentInnen benötigen dafür kein technisches Vorwissen über
die verwendete Aufzeichnungstechnik.

*Einfache Aufzeichnungsplanung:* Vorlesungsaufzeichnungen können direkt in dem
Stud.IP-Kurs von den DozentInnen geplant werden. Dabei werden im Kurs
verfügbare Metadaten bei der Planung berücksichtig. Möglich ist dies mit einer
eigenen Planungsansicht. Hiermit entfällt die mehrfache Eingabe von Metadaten.

*Kontrolle der Sichtbarkeit:* Die DozentInnen können die Sichtbarkeit jeder
Aufzeichnung in Stud.IP individuell festlegen.
