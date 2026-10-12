# Blood Bank Management System (RedAid)

<p align="center">
	<strong>A role-based blood donation and inventory platform with an AI-assisted matching and forecasting service.</strong>
</p>

<p align="center">
	<img alt="PHP" src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white">
	<img alt="Laravel" src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white">
	<img alt="React" src="https://img.shields.io/badge/React-19-149ECA?logo=react&logoColor=white">
	<img alt="Python" src="https://img.shields.io/badge/Python-AI%20service-3776AB?logo=python&logoColor=white">
	<a href="https://doi.org/10.5281/zenodo.23311005"><img alt="DOI" src="https://zenodo.org/badge/DOI/10.5281/zenodo.23311005.svg"></a>
</p>

This application connects donors, people requesting blood, and administrators in one place. It brings together donor and receiver workflows, blood inventory, donation requests, campaign management, notifications, and a separate Python service for AI-assisted features.

Developed as the Final Year Project (CSC412 Project Work) for the B.Sc. Computer Science and Information Technology programme at the College of Applied Business and Technology, Tribhuvan University.

## Team

**Supervisor:** Santosh Sharma, Coordinator/Lecturer, Department of Computer Science and IT, College of Applied Business and Technology

### Contributions

**Swostik Paneru**
- Designed and built the Laravel REST API, including database models, migrations and role-based routes for Donor, Receiver and Administrator users.
- Implemented JWT authentication, email verification, password recovery and role switching.
- Integrated the Laravel backend with the FastAPI machine-learning service for donor matching, request-priority classification, churn prediction, demand forecasting and campaign targeting.
- Contributed to the React frontend, connecting user interfaces to the backend APIs.

**Sudeep Raj Karki**
- Set up the React frontend, including project structure, routing and the API client.
- Developed REST API endpoints in the Laravel backend.

**Bipin Aryal**
- Developed frontend interfaces for donor, receiver and administrator workflows.
- Contributed to the FastAPI machine-learning service.

All three members co-authored the research paper describing the system and its evaluation.

## Contents

- [Team](#team)
- [What it does](#what-it-does)
- [How it is organized](#how-it-is-organized)
- [Requirements](#requirements)
- [Run locally](#run-locally)
- [Services and URLs](#services-and-urls)
- [API overview](#api-overview)
- [Tests and quality checks](#tests-and-quality-checks)
- [Troubleshooting](#troubleshooting)
- [Paper](#paper)

## What it does

| Area | Capabilities |
| --- | --- |
| Donors | Manage a profile, review donation eligibility, browse campaigns, view matches, and record donations. |
| Receivers | Browse blood inventory, submit blood requests, and view matched donors. |
| Administrators | Manage inventory and campaigns, approve or deny requests, and review dashboard analytics. |
| AI assistance | Match nearby donors, classify request priority, predict donor churn, forecast demand, and suggest campaign targets. |
| Accounts | Register and sign in, verify email, recover passwords, switch roles, and receive notifications. |

## How it is organized

```text
.
|-- Frontend/   React single-page application (Vite)
|-- backend/    Laravel REST API, authentication, and database
`-- ai/         FastAPI service for matching and prediction endpoints
```

The frontend sends API requests to Laravel. Laravel handles application data and authentication, and forwards AI operations to the FastAPI service. The AI service can also be called directly for development and API exploration.

## Requirements

- PHP 8.2 or later with Composer
- Node.js and npm
- Python 3.10 or later with pip
- A database supported by Laravel; SQLite is configured in `backend/.env.example`

Prophet, one of the Python dependencies, may need additional platform-specific setup. See the [Prophet installation guide](https://facebook.github.io/prophet/docs/installation.html) if installing the AI dependencies fails.

## Run locally

Start each service in a separate terminal.

### 1. Laravel API

```powershell
cd backend
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

If `backend/.env` already exists, keep it and verify its database settings instead of replacing it. The example environment uses SQLite. If you choose MySQL or another database, update the corresponding `DB_*` settings and create the database before running migrations. Email uses Laravel's log mailer by default, so verification and reset messages are written to the application log during local development.

### 2. React frontend

```powershell
cd Frontend
npm install
npm run dev
```

Open the local URL printed by Vite, usually `http://localhost:5173`. The API client currently uses `http://localhost:8000/api`; run Laravel at that address for the frontend to connect without code changes.

### 3. AI service

```powershell
cd ai
py -m venv .venv
.venv\Scripts\Activate.ps1
py -m pip install -r requirements.txt
py -m uvicorn main:app --reload --host 127.0.0.1 --port 5000
```

The Laravel backend defaults to `http://127.0.0.1:5000` for AI requests. To use another address, set `AI_SERVICE_URL` in `backend/.env` and clear cached Laravel configuration with `php artisan config:clear`.

## Services and URLs

| Service | Local address | Purpose |
| --- | --- | --- |
| React / Vite | `http://localhost:5173` | Donor, receiver, and administrator interface |
| Laravel | `http://127.0.0.1:8000` | REST API and application data |
| FastAPI | `http://127.0.0.1:5000` | Matching and prediction endpoints; interactive docs at `/docs` |

## API overview

Laravel API routes are prefixed with `/api`. Public routes include registration, login, email verification and password recovery, plus read-only blood inventory. Authenticated routes use JWT authentication; role-specific routes cover donor, receiver, and administrator actions.

The FastAPI service exposes these POST endpoints:

| Endpoint | Function |
| --- | --- |
| `/match-donor` | Return nearby donor matches |
| `/predict-priority` | Predict a blood request's priority |
| `/churn-predict` | Predict donor churn |
| `/demand-forecast` | Forecast upcoming demand |
| `/campaign-targeting` | Group campaign data and recommend target locations |

For Laravel route details, run `php artisan route:list` from `backend/`. For FastAPI request schemas and interactive requests, open `http://127.0.0.1:5000/docs` while the AI service is running.

## Tests and quality checks

Run the Laravel test suite:

```powershell
cd backend
php artisan test
```

Build and lint the frontend:

```powershell
cd Frontend
npm run build
npm run lint
```

## Troubleshooting

- **Frontend requests fail:** Confirm Laravel is listening on port `8000`. The frontend API base URL is currently set in `Frontend/src/api/axios.jsx`.
- **AI requests fail:** Start FastAPI on port `5000`, or set `AI_SERVICE_URL` in `backend/.env` to the running service address. Check Laravel's application logs for upstream request errors.
- **Database or migration errors:** Confirm the `DB_*` settings in `backend/.env`, ensure the selected database exists, then rerun `php artisan migrate`.
- **JWT setup errors:** Run `php artisan jwt:secret` from `backend/` after configuring the Laravel environment.

## Paper

**RedAid: A Web-Based Blood Bank Management System with Machine-Learning Support for Request Prioritization and Donor Matching**

Aryal, B., Karki, S. R., & Paneru, S. (2026). Preprint, Zenodo. Supervisor: Santosh Sharma.

DOI: [10.5281/zenodo.23311005](https://doi.org/10.5281/zenodo.23311005)
