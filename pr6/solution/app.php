<?php

use App\CatalogRepository;
use App\JsonFileStorage;
use App\ProductValidator;

require __DIR__ . '/bootstrap.php';

// Сколько товаров показывать на одной странице.
const PER_PAGE = 5;

// Единая «сборка» приложения: хранилище, репозиторий и валидатор.
$storage = new JsonFileStorage(__DIR__ . '/data/catalog.json');
$repository = new CatalogRepository($storage);
$validator = new ProductValidator();
