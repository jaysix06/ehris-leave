# Git commands (brief reference)

## Get the latest code from GitHub

**Update your current branch with what’s on GitHub (e.g. main):**
```bash
git fetch origin
git pull origin main
```

---

## You’re on branch **raegan** – get updates from **main**

```bash
git checkout raegan
git pull origin main
```

---

## You’re on branch **raegan** – get updates from **kian**

```bash
git checkout raegan
git fetch origin
git merge origin/kian
```

Or: `git pull origin kian`

---

## You’re on branch **kian** – save your work to **kian**

```bash
git add .
git commit -m "Your message here"
git push origin kian
```

First time pushing this branch: `git push -u origin kian`

---

## Commit and push to **main**

```bash
git checkout main
git add .
git commit -m "Your message"
git push origin main
```

---

## Handy checks

- See current branch: `git branch`
- See status: `git status`
- Switch branch: `git checkout branch-name`
