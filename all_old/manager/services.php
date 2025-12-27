<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/dashboard_helper.php';

$_SERVER['REQUEST_URI'] = '/services.php';
include __DIR__ . '/../services.php';
