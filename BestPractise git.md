# Nütziche Befehle für GitHub
Befehle, für die verwendung in der Console

## 1. Clone den aktuellen Master
1. Navigiere zum Pfad, an dem du den neuen Ordner ablegen willst.
2. git clone https://github.com/azanov/test-flow-moodle-plugin.git [Ordnername]

## 2. Erstelle einen neuen Branch
3. Der neue Branch sollte den Namen deines Kürzels tragen. Bei mir DoWi
3. $ git branch [Kürzel]
4. $ git checkout [Kürzel]
5. git push origin [Kürzel]  
Mit diesem Befehl lädst du den Branch hoch.

## 3. Dateien hochladen
7. git add [FileName]
8. git commit -m "Kommentar"
9. git push origin [BranchName]

## 4. Pull Request setzen
Kein eigenständiges hochladen von Updates zu "Master". Nur Pull Requests!
1. On GitHub, navigate to the main page of the repository.
2. In the "Branch" menu, choose the branch that contains your commits. 
3. To the right of the Branch menu, click New pull request. 
4. Use the base branch dropdown menu to select the branch you'd like to merge your changes into, then use the compare branch drop-down menu to choose the topic branch you made your changes in. 
5. Type a title and description for your pull request. 
6. Click Create pull request. 