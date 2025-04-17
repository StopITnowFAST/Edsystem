<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class TgApi
{
    private $channel;
    private $token;

    public function __construct(
        private EntityManagerInterface $em
    ) {        
        $this->channel = $_ENV['TG_CHANNEL'];
        $this->token = $_ENV['TG_TOKEN'];
    }    

    public function sendErrorToTelegram(string $message, ?string $url, ?int $line): void {

        $text = "🚨 Ошибка на сайте:\n\n"
            . "• Сообщение: $message\n"
            . ($url ? "• Страница: $url\n" : "")
            . ($line ? "• Строка: $line\n" : "")
            . "• Время: " . date('Y-m-d H:i:s');
        
        file_get_contents(
            "https://api.telegram.org/bot{$this->token}/sendMessage?" . 
            http_build_query([
                'chat_id' => $this->channel,
                'text' => $text,
                'parse_mode' => 'HTML'
            ])
        );
    }
}