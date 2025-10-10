Selenium Docker test scaffold for Abrechnung app
===============================================

How to use locally:
1. Ensure Docker and Docker Compose are installed.

2. From your project root, place docker-compose.yml here and run:

   docker-compose up -d --build

3. Wait for services to come up (MySQL init and Apache). Then run:

   docker exec abrechnung_phpunit phpunit tests/php/AbrechnungTest.php

4. To run Selenium UI tests:
   docker exec abrechnung_selenium_tests pytest -q tests/ui/test_selenium.py

Notes:
- The selenium container exposes WebDriver at port 4444.
- Selenium tests connect to the PHP app using the docker Compose service name 'php' (http://php:80/...).
- Adjust timeouts if your environment is slower.
