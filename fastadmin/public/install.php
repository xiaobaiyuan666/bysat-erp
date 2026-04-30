<?php

declare(strict_types=1);

use app\admin\command\Install;
use think\Response;

define('APP_PATH', __DIR__ . '/../application/');

require __DIR__ . '/../thinkphp/base.php';
require_once APP_PATH . 'common.php';
require_once APP_PATH . 'admin/command/Install.php';

$installer = new Install();
$response = $installer->index();

if ($response instanceof Response) {
    $response->send();
    exit;
}

echo (string)$response;
