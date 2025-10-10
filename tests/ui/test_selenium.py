import os
import time
import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.remote.webdriver import WebDriver
from selenium.webdriver.common.desired_capabilities import DesiredCapabilities
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

SELENIUM_URL = os.environ.get("SELENIUM_URL", "http://selenium:4444/wd/hub")
APP_URL = os.environ.get("APP_URL", "http://php:80/abrechnung_anzeigen.php?date=today")

@pytest.fixture(scope='module')
def driver() -> WebDriver:
    caps = DesiredCapabilities.CHROME.copy()
    driver = webdriver.Remote(command_executor=SELENIUM_URL, desired_capabilities=caps)
    yield driver
    driver.quit()

def test_page_loads_and_table_visible(driver):
    driver.get(APP_URL)
    WebDriverWait(driver, 10).until(EC.visibility_of_element_located((By.CSS_SELECTOR, "table.styled-table")))
    table = driver.find_element(By.CSS_SELECTOR, "table.styled-table")
    assert table is not None

def test_click_product_cell_shows_popup(driver):
    driver.get(APP_URL)
    WebDriverWait(driver, 10).until(EC.visibility_of_element_located((By.CSS_SELECTOR, "table.styled-table")))
    try:
        cell = driver.find_element(By.CSS_SELECTOR, "td.product-cell")
    except:
        cell = driver.find_element(By.CSS_SELECTOR, "td[data-produkt]")
    assert cell is not None
    cell.click()
    WebDriverWait(driver, 5).until(EC.visibility_of_element_located((By.CSS_SELECTOR, ".qty-inline-popup")))
    popup = driver.find_element(By.CSS_SELECTOR, ".qty-inline-popup")
    assert popup is not None
