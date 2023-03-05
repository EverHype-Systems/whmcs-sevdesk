
# EverHype Systems GmbH - sevdesk x WHMCS

## Der Grund, warum das Modul nun kostenfrei ist:
Unser Ziel als Unternehmen ist es, anderen Unternehmen dabei zu helfen mithilfe von Automatisierung mehr Zeit für die eigentliche Kernkompetenz zu erlangen. Da viele Anbieter, vor allem aus dem Hosting Bereich teilweise noch sehr klein sind, entschlossen wir uns dieses Modul nun der Öffentlichkeit _ohne monatliche Gebühr_ bereitzustellen.

## Updates für die Module
Das Modul sollte ohne große Probleme auch in der Zukunft funktionieren. Nichtsdestotrotz können wir hierfür **keine** Garantie geben. Das Modul dient der Öffentlichkeit und jeder, der möchte, kann sich an diesem Projekt beteiligen und Updates per Pull Request einsenden.

Wir werden die Module bei großen Changes - innerhalb von WHMCS - , sofern es die Zeit erlaubt, mit Updates versorgen und bereitstellen.

Solltet ihr dieses Modul nutzen und könnt programmieren, würden wir uns über eine PR von euch freuen.

## Richtigkeit und Haftung
Wir möchten **ausdrücklich** betonen, dass wir für keine Fehler in der Buchhaltung haften oder die Richtigkeit **nicht** garantieren können. Diverse Fehler, falsche Konfigurationen innerhalb von WHMCS und andere Auslöser können einen Fehler hervorrufen.

Im Ordner /modules/addons/sevdesk/invoices ist eine .htaccess-Datei hinterlegt, die es verhindern soll, dass - während eines Synchronisationslauf - Rechnungen für die Öffentlichkeit sichtbar sein sollen. Bitte prüft die .htaccess bitte nochmal auf eigenen Server und ob diese sich richtig verhält. Gegebenenfalls solltet ihr den /invoices/ - Ordner nur für interne Abfragen [IP-Binding bspw.] erlauben.

Um eine gute Buchführung zu ermöglichen, empfehlen wir stets einen Steuerberater. Wir sind lediglich Software-Entwickler.

## Installation & Konfiguration
Zieht den Ordner in euren WHMCS-Root-Ordner. Aktiviert das Modul im Admin-Bereich und hinterlegt alle notwendigen Bedingungen.

Um dieses Modul nutzen zu können, benötigt ihr einen Sevdesk Account mit entsprechenden API-Rechten.

**Der Secret-Key wird benötigt, um auf die PDFInvoice.php zuzugreifen. Der Key sollte daher grundsätzlich sicher und eindeutig sein. Nutzt hierfür am besten eine uuid4.**

## Support & Hilfestellungen
Falls Hilfe benötigt benötigt, könnt ihr euch gerne bei uns melden. Bitte beachtet, dass wir für ein kostenloses Modul keinen kostenlosen direkt Support anbieten können. Für kostenlosen Support öffnet bitte ein Github Issue, es kann hier aber passieren, dass es zu Wartezeiten kommen kann.

https://everhype.de/open-source-support


