<?php
// Резервный бот для GitHub хостинга
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if ($update && isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $user_id = $message['from']['id'];
    $user_name = $message['from']['first_name'] ?? 'User';
    $username = $message['from']['username'] ?? '';
    
    // Логируем запрос
    error_log("BACKUP: {$user_name} - {$text}");
    
    // Пробуем отправить на основной хостинг
    $main_sent = forwardToMainHost($update);
    
    if (!$main_sent) {
        // Если основной не доступен - сообщаем админу
        $backup_msg = "🆘 РЕЗЕРВНАЯ ЗАЯВКА\n";
        $backup_msg .= "👤 {$user_name}";
        $backup_msg .= $username ? " (@{$username})" : "";
        $backup_msg .= "\n🆔 ID: {$user_id}";
        $backup_msg .= "\n💬 {$text}";
        $backup_msg .= "\n⏰ " . date('H:i:s d.m.Y');
        
        sendToAdmin($backup_msg);
    }
    
    // Отвечаем клиенту что всё ок
    sendTelegramMessage($chat_id, "✅ Ваше сообщение принято! Свяжемся с вами в ближайшее время.");
}

function forwardToMainHost($update) {
    $main_url = "https://globustransfersvo.ru/bot_fixed_v3.php";
    
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($update),
            'timeout' => 5
        ]
    ];
    
    try {
        $context = stream_context_create($options);
        $result = @file_get_contents($main_url, false, $context);
        return $result !== false;
    } catch (Exception $e) {
        return false;
    }
}

function sendToAdmin($message) {
    $bot_token = "7504050885:AAHvJfVcdjDVZqUCIyXK6I0LfL3I8U6kA7o";
    $admin_id = "7109959376";
    
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $data = [
        'chat_id' => $admin_id, 
        'text' => $message
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];
    
    try {
        $context = stream_context_create($options);
        @file_get_contents($url, false, $context);
    } catch (Exception $e) {
        // Игнорируем ошибки
    }
}

function sendTelegramMessage($chat_id, $text) {
    $bot_token = "7504050885:AAHvJfVcdjDVZqUCIyXK6I0LfL3I8U6kA7o";
    
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $data = [
        'chat_id' => $chat_id, 
        'text' => $text
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];
    
    try {
        $context = stream_context_create($options);
        @file_get_contents($url, false, $context);
    } catch (Exception $e) {
        // Игнорируем ошибки
    }
}

http_response_code(200);
echo 'OK';
?>
