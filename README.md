
Docker-based test scaffold for Abrechnung app
============================================

How to use (locally)
--------------------

1. Make sure Docker and Docker Compose are installed.

2. From your project root (where docker-compose.yml will be placed), run:

   docker-compose up -d --build

   This starts:
   - PHP Apache server on port 8080 (serving your repo)
   - MySQL 8 service (3306)
   - phpunit container for running unit tests
   - playwright container (idle, used to run UI tests)

3. Run PHPUnit tests inside the phpunit container:

   docker exec -it abrechnung_phpunit phpunit --version
   docker exec -it abrechnung_phpunit phpunit tests/php/AbrechnungTest.php

4. Run Playwright UI tests from the playwright container:

   docker exec -it abrechnung_playwright bash -lc "cd /workspace && npm ci && npx playwright install --with-deps && npx playwright test"

Notes
-----
- The Playwright tests access the app via the host machine address. In Docker for Mac/Windows use host.docker.internal.
- Adjust ports or service names as needed for your environment.
