<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Item.php';
require_once __DIR__ . '/ItemController.php';

class StockController extends BaseController {
    public function index(): void {
        $ctrl = new ItemController();
        $ctrl->stock();
    }
}
