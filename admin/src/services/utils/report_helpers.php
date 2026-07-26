<?php
// Shared helpers for report services

if (!function_exists('require_post_json')) {
    function require_post_json(): array {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        return is_array($input) ? $input : [];
    }
}

if (!function_exists('normalize_date_range')) {
    function normalize_date_range(array $input): array {
        $start = $input['start'] ?? date('Y-m-01');
        $end = $input['end'] ?? date('Y-m-t');
        // Basic validation and clamping
        $startTime = strtotime($start) ?: strtotime(date('Y-m-01'));
        $endTime = strtotime($end) ?: strtotime(date('Y-m-t'));
        if ($endTime < $startTime) {
            $tmp = $startTime;
            $startTime = $endTime;
            $endTime = $tmp;
        }
        return [
            'start' => date('Y-m-d', $startTime),
            'end' => date('Y-m-d', $endTime)
        ];
    }
}

if (!function_exists('soft_delete_clause')) {
    function soft_delete_clause(string $alias = ''): string {
        $col = $alias ? rtrim($alias, '.') . '.deleted_at' : 'deleted_at';
        return "$col IS NULL";
    }
}

if (!function_exists('json_ok')) {
    function json_ok($data): void {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

?>


