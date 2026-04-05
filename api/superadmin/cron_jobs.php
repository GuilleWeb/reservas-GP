<?php
require_once __DIR__ . '/../../helpers.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$user = current_user();
if (!$user || (($user['rol'] ?? null) !== 'superadmin')) {
    json_response(['error' => 'unauthorized'], 403);
}

switch ($action) {
    case 'list':
        json_response(['success' => true, 'data' => cron_jobs_list()]);
        break;
    case 'run':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            json_response(['error' => 'invalid_method'], 405);
        }
        $tasks = $_POST['tasks'] ?? [];
        if (!is_array($tasks)) {
            $tasks = [$tasks];
        }
        $results = cron_jobs_run($tasks, (int) ($user['id'] ?? 0));
        json_response(['success' => true, 'results' => $results]);
        break;
    case 'log':
        $file = project_path('cron_jobs.log');
        $lines = [];
        if (is_file($file)) {
            $content = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $lines = array_slice($content, -200);
        }
        json_response(['success' => true, 'data' => $lines]);
        break;
    default:
        json_response(['error' => 'invalid_action'], 400);
}

