<?php

require_once __DIR__ . '/BaseController.php';

/**
 * Public customer apps hub (PWA entry) — no login.
 */
class AppsController extends BaseController {
    public function index(): void {
        include __DIR__ . '/../views/public/apps_hub.php';
    }
}
