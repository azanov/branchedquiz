# Allgemeines Treffen

## 1. Aufgabenstellung korrigiert
Bisher nahmen wir an, dass unsere Auswertung nur auf war-falsch basiert. 
Uns ist dabei die spezifische Auswertung von Bsp. Stack o.a. egal.

Neu ist, dass wir mit den Teilpunkten arbeiten sollen. Speziell hast dass, 
für jede Teilpunkteanzahl kann es eigene Pfade geben.

| ID | Punkte | Neue Aufgaben |
|----------|:-------------:|:------:|
|A1|1|A2|
|A1|2|A2, A3, A12|
|A1|3|A2, A5|
|...
|A2|5|A3, A15|
|

Bsp: Nurzer hat in Aufgabe 1 2Pk. Er bekommt dann Aufgabe 2. Hier hat er 5Pk und erhält. 
Es wird A3 angezeigt. Hier kommen bei verschiedenen Punkten keine weiteren Aufgaben dazu. 
Nun ist Aufgabe A15 dran, worauf A3 übersprungen wird (nicht doppelt) und dann erst A12
gestellt wird. 

Kurz gesagt, neue Aufgaben kommen auf den Stack. Hier werden sie dann abgearbeitet, 
wobei keine Aufgabe doppelt vorkommen soll.

## 2. User-Interface
Es wäre gut, wenn es 2 Ansichten gibt. 
1. Graphischer Baum (+Pfade zuwischen verschiedenen Ästen ermöglichen)
1. Tabelle (wie oben)

Beim Baum bitte Teilbäume erstellen lassen. Auch ist es wichtig, dass Aufgaben sich auf
verschiedenen Pfaden wiederverwenden lassen. D.h. bei Änderung an einer Aufgabe wird die
Änderung für jedes vorkommen der Aufgabe aktualisiert.

## 3. Feedback
### Verschiedene Feedback Arten
Es soll möglich sein, Feedback direkt nach einer Aufgabe/einem Aufgabenteil zu geben.
Am Ende der Aufgaben soll es ein kommuliertes Feedback geben.

__Ansatz:__ Ein graphischer Knoten, den man an gewünschten Stellen positionieren kann.

### Erweiteres Feedback
Der Nutzer bekommt zuerst ein Allgemeines Feedback. Über Knöpfe `+Mehr` kann er 
weitere Details angezeigt werden. __Vorschlag:__ Mehrere Bereiche mit `#####` 
kennzeichnen. Denn Text  `+Mehr` soll man wie folgt anpassen können: `#####+Meinname####`

### Feedback eingeben
Nach jeder Teilaufgabe soll das Feedback je nach Punktezahl individuell vergeben werden.
Anruf erfolgt dann später.

Beim Feedback am Ende soll nach Möglichkeit auch ein Statistisches Feedback gegeben werden.
Mit ausgaben wie: Häufige Fehler...

## 4. Protokollierung
Es wird ein Ausführliche Protokoll benötigt. Bestehend Auswertung
* Zeit je Aufgabe
* Pfade
* ...

## 5. Organisatorisches
Nächste Treffen:
* Di, 10.01.2017 9:00 Uhr
* Mo, 30.01.2017 12:00 Uhr

__Auftraggeber erhalten Zugriff auf Trello.__
