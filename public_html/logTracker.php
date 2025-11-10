<?php

$access_log_file = __DIR__ . '/pagVisited.log'; 
$events_log_file = __DIR__ . '/eventTracker.log';
function log_page_access() {
    global $access_log_file;

    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    $logged_user_id = $_SESSION['user_id'] ?? 'GUEST';

    $timestamp = date('d/M/Y:H:i:s O');
    $request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
    $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
    $status_code = '200'; 
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'No Browser Info';

    
    $log_line = sprintf(
        '[%s] [%s] - - [%s] "%s %s %s" %s - "%s" "%s"',
        $ip_address,
        $logged_user_id,
        $timestamp,
        $request_method,
        $request_uri,
        $protocol,
        $status_code,
        $_SERVER['HTTP_REFERER'] ?? '-', 
        $user_agent
    );

    file_put_contents($access_log_file, $log_line . "\n", FILE_APPEND);
}

function log_event($eventType, $message,$user) {
    global $events_log_file;

    $timestamp = date('d/M/Y:H:i:s O');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
    
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'No Browser Info';

    
    $log_line = sprintf(
        "[%s] [%s] [%s] [%s] %s",
        $timestamp,
        $eventType,
        $ip_address,
        $user,
        $message
    );

    file_put_contents($events_log_file, $log_line . "\n", FILE_APPEND);
}

log_page_access();

?>