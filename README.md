# cusc-edtech-installation-guide

CUSC EdTech Installation & Setup Guide
This repository provides step-by-step technical documentation for installing, configuring, and maintaining the CUSC EdTech system.
It is intended for developers, system administrators, and future maintainers who take over the project

## Data Storage

This project does **not use a traditional database**.
Data and content are managed using:

- Static HTML files (for content structure)
- JSON files (for dynamic data such as documentation structure and comments)
  There is **no database server** and **no SQL schema** involved.
  All data is created and maintained directly through HTML and JSON files.

## Getting Started (Local Development)

### Requirements

- Install: PHP >= 8.0
- Web browser: chrome, edge, safari
- OS: macOS, win, ubuntu

### Run

1. Clone the repository.
2. Navigate to the web root:

```bash
cd HTDOCS
```

3. Start the PHP built-in server:

```bash
   php -S localhost:8000
```

4. Open your browser:

```bash
   http://localhost:8000
```
