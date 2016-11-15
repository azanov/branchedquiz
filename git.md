# Git - Source Control
![git](http://git-scm.com/images/logo@2x.png)


## 1. Installing Git

### 1.1. Installing the command line version of Git.
If you are using MacOS X, run thr following command: `$ sudo port install git-core +doc +bash_completion +gitweb`.

If you are using Windows, download and install [msysgit.github.io](http://msysgit.github.io/).

### 1.2. Optionally, a Git client with GUI (not required)

* [GitHub Windows](https://windows.github.com/) - Git client with GUI for Windows 7+ from GitHub.

* [GitHub Mac](https://mac.github.com/) - Git client with GUI for Mac OS X 10.9+ from GitHub.

* [Git for Windows](http://msysgit.github.io/) - 3 в 1: Git, bash-cmd and a simple GUI client.

* [SourceTree](http://www.sourcetreeapp.com) - a free Git client. Allows you to work with Git without having to deal with the command line. Works on Windows 7+ and Mac OS X 10.7+. Supports multiple languages.
Before you start using SourceTree, be sure to get familiar with the Git basics and the git command line.

## 2. Git Basics

### 2.1. Quick tutorial
For beginners, it's recommended to take the [Git How To](http://githowto.com/) course.

### 2.2. Useful Links

#### 2.2.1. Understanding Git
* [Git How To](http://githowto.com/) — an interactive git course.
* [Команды git](http://git-scm.com/book/commands) - a complete command reference
* [git - the simple guide](http://rogerdudler.github.io/git-guide/)

#### 2.2.2. Video Tutorials
* [Git & GitHub Tutorials](https://www.youtube.com/playlist?list=PLEACDDE80A79CE8E7)

#### 2.2.3. Books
* [Pro Git](http://git-scm.com/book/en/v2) - official Git documentation ([German version](http://git-scm.com/book/de/v2))

#### 2.2.4. Cheatsheet
* [GitHub Cheatsheet](https://raw.githubusercontent.com/github/training-kit/master/downloads/github-git-cheat-sheet.pdf)


## 3. Working with Git

### 3.1. Creating a project:

* `git init` - initializing Git.
* `git add -A` - index all files.
* `git commit -m 'chore(project): init project'` - making the initial commit.
* `git remote add origin <url>` - adding a remote repository.
* `git push origin master` - pushing the project to the remote repository (`master` branch).
* If you failed to push, the remote repository might already contain some files. Do a pull with `rebase`:
```
git pull --rebase origin master
```
* Should any conflicts exist, see 3.4.
* Push again.

### 3.2. Working with an existing project

* `git clone <url>` - clone the project locally.

### 3.3. Daily work with the project

* `git pull --rebase origin master` - refresh trhe local `master` branch with `--rebase`. This will avoid the creation of merge commits.
* If there are no conflicts, skip this step, otherwise, go to 3.4.
* Make some changes to your code.
* `git add -A` - index all changes.
* `git commit -m 'feat(DHLV01 EDI): add dhl v01 edi'` - commiting the changes
* `git push origin master` - pushing the changes to the remore repository (`master` branch).

### 3.4. Solving conflicts

* Edit the files with conflicts by hand (to see a list of files with conflicts, run `git status`):
```
<<<<<<< HEAD
// First section: The code in the current branch
=======
// Second section: Your changes
>>>>>>> master
```

To resolve the conflict, only the second section should be kept / merged with first:
```
// Second section: Your changes
```

* Index the changes:
```
git add -A
```

* Continue the `rebase`:
```
git rebase --continue
```

* If there are no more conflics in the code, `rebase` will finish. Otherwise continue to resolve the conflicts.

### 3.5. Creating a new branch for a feature

#### 3.5.1. Creating a branch

* `git pull --rebase origin master` - update `master` master branch with `--rebase` in order to avoid merge commits.
* `git checkout -b feature/<name>` - create a feature branch `feature/<name>`, `<name>` is the name of the feature.
* Make changes in your code
* `git add -A` - index the changes.
* `git commit -m 'feat(main): add main page'` - commit according to the commit naming convention.
* `git push origin feature/<name>` - push our branch to the remote repository.


#### 3.5.2. Make a `rebase` of our feature into the `master` branch

* Being in your branch, run:
```
git rebase master
```
* If there are any conflicts, solve them first, otherwise continue with the next step.
* `git push origin master` - Push the changes to the remote repository.

### 3.6. Useful commands

* `git commit --amend` - allows to change the previous commit. You can rename it or add files.
* `git reset --hard HEAD^` - remove last commit completely.


### 5. Commit Naming Convention

Conventional changelog is being used.

#### 5.1. Format

Each commit message consists of **type(scope): subject**.

You can also add a more detailed description (body) at the end.

- `type` - type of the changes made in the current commit;
- `scope` - a place in the code where the changes were made;
- `subject` - commit message/description;
- `body` (not required) - a detailed description of the changes;

```
<type>(<scope>): <subject>
<BLANK LINE>
<body>
```

Examples:

```
feat(ruler): add inches as well as centimeters
fix(protractor): fix 90 degrees counting as 91 degrees
refactor(pencil): use graphite instead of lead

Closes #640.

Graphite is a much more available resource than lead, so we use it to lower the price.
fix(pen): use blue ink instead of red ink

BREAKING CHANGE: Pen now uses blue ink instead of red.

To migrate, change your code from the following:

`pen.draw('blue')`

To:

`pen.draw('red')`
```

Each line of the commit message should not exceed 100 chars.

#### 5.2. Types

- `feat` - a new feature;
- `fix` - bug fixing;
- `docs` - change in documentation;
- `style` - formatting of code, other changes that do not change any functionality изменения, не влияющие на работу кода;
- `refactor` - refactoring of existing code;
- `test` - added or updated a test case;
- `chore` - changes in build process

#### 5.3. Scope

Can be a specific place in code where changes were made. **Only one scope is allowed**

#### 5.4. Commit Messages

- English only;
- Use present tense, e.g `change`, but not `changed` или `changes`;
- No capital letter at the front;
- No dot at the end;
