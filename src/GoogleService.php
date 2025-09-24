<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../config/google.php';

class GoogleService {
    private $client;
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $cfg = GoogleConfig::getClientConfig();
        $this->client = new Google_Client();
        $this->client->setClientId($cfg['client_id'] ?? '');
        $this->client->setClientSecret($cfg['client_secret'] ?? '');
        $this->client->setRedirectUri(GoogleConfig::getRedirectUri());
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        $this->client->setScopes([
            'https://www.googleapis.com/auth/tasks',
            'https://www.googleapis.com/auth/calendar.events',
            'https://www.googleapis.com/auth/gmail.readonly',
            'openid',
            'https://www.googleapis.com/auth/userinfo.email'
        ]);

        // Cargar token si existe (modo single-user)
        $this->loadAccessToken();
    }

    public function getAuthUrl(): string {
        return $this->client->createAuthUrl();
    }

    public function handleCallback(string $code): void {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) {
            throw new Exception('Error al obtener token: ' . $token['error_description'] ?? $token['error']);
        }
        $this->saveAccessToken($token);
    }

    public function ensureTokenFresh(): void {
        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $this->client->getRefreshToken();
            if ($refreshToken) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                $this->saveAccessToken($newToken, $refreshToken);
            }
        }
    }

    private function saveAccessToken(array $token, ?string $existingRefreshToken = null): void {
        if (!$existingRefreshToken && isset($token['refresh_token'])) {
            $refresh = $token['refresh_token'];
        } else {
            $refresh = $existingRefreshToken;
        }
        $this->client->setAccessToken($token);

        $googleUserId = 'single-user';
        try {
            // Preferir ID del id_token si está disponible
            $idToken = $token['id_token'] ?? null;
            if ($idToken) {
                $parts = explode('.', $idToken);
                if (count($parts) === 3) {
                    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                    if (!empty($payload['sub'])) {
                        $googleUserId = (string)$payload['sub'];
                    }
                }
            }
            // Fallback: usar endpoint userinfo si hay scope adecuado
            if ($googleUserId === 'single-user') {
                $oauth2Class = 'Google_Service_Oauth2';
                $oauth2 = new $oauth2Class($this->client);
                $userInfo = $oauth2->userinfo->get();
                if ($userInfo && $userInfo->getId()) {
                    $googleUserId = $userInfo->getId();
                }
            }
        } catch (Throwable $e) {
            // Mantener 'single-user' si no podemos obtener el ID
        }

        $expiresIn = $token['expires_in'] ?? null;
        $expiresAt = $expiresIn ? (new DateTime("+{$expiresIn} seconds"))->format('Y-m-d H:i:s') : null;

        // Upsert
        $sql = "INSERT INTO user_tokens (provider, google_user_id, access_token, refresh_token, token_expires_at, scopes)
                VALUES ('google', ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE access_token=VALUES(access_token), refresh_token=VALUES(refresh_token), token_expires_at=VALUES(token_expires_at), scopes=VALUES(scopes)";
        $scopes = implode(' ', $this->client->getScopes());
        $this->db->query($sql, [
            $googleUserId,
            json_encode($token),
            $refresh,
            $expiresAt,
            $scopes
        ]);
    }

    private function loadAccessToken(): void {
        // Cargamos cualquier token guardado (tomamos el más reciente)
        $stmt = $this->db->query("SELECT * FROM user_tokens WHERE provider='google' ORDER BY updated_at DESC LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            $token = json_decode($row['access_token'], true);
            if ($token) {
                if (!empty($row['refresh_token']) && empty($token['refresh_token'])) {
                    $token['refresh_token'] = $row['refresh_token'];
                }
                $this->client->setAccessToken($token);
            }
        }
    }

    public function isConnected(): bool {
        return !$this->client->isAccessTokenExpired() || (bool)$this->client->getRefreshToken();
    }

    // Operaciones de Tasks
    public function createGoogleTask(string $title, string $notes = ''): ?string {
        $this->ensureTokenFresh();
        $tasksServiceClass = 'Google_Service_Tasks';
        $tasksTaskClass = 'Google_Service_Tasks_Task';
        $service = new $tasksServiceClass($this->client);
        $task = new $tasksTaskClass([
            'title' => $title,
            'notes' => $notes
        ]);
        $result = $service->tasks->insert('@default', $task);
        return $result->getId();
    }

    // Operaciones de Calendar
    public function createCalendarEvent(string $summary, ?string $description = null, ?DateTime $start = null, ?DateTime $end = null, array $options = []): ?string {
        $this->ensureTokenFresh();
    $calendarServiceClass = 'Google_Service_Calendar';
    $service = new $calendarServiceClass($this->client);

        $eventData = [
            'summary' => $summary,
            'description' => $description,
        ];

        $allDay = !empty($options['all_day']);
        if ($allDay) {
            $startDate = $options['start_date'] ?? (new DateTime())->format('Y-m-d');
            $endDate = $options['end_date'] ?? (new DateTime('+1 day'))->format('Y-m-d');
            $eventData['start'] = ['date' => $startDate];
            $eventData['end'] = ['date' => $endDate];
        } else {
            $eventData['start'] = [
                'dateTime' => ($start ?: new DateTime())->format(DateTime::ATOM)
            ];
            $eventData['end'] = [
                'dateTime' => ($end ?: (new DateTime('+1 hour')))->format(DateTime::ATOM)
            ];
        }

        if (!empty($options['location'])) {
            $eventData['location'] = (string)$options['location'];
        }

        if (isset($options['reminder_minutes']) && $options['reminder_minutes'] !== null) {
            $minutes = max(0, (int)$options['reminder_minutes']);
            $eventData['reminders'] = [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'popup', 'minutes' => $minutes]
                ]
            ];
        }

        $calendarEventClass = 'Google_Service_Calendar_Event';
        $event = new $calendarEventClass($eventData);
        $created = $service->events->insert('primary', $event);
        return $created->getId();
    }

    // Gmail helpers
    public function getGmailMessageById(string $messageId) {
        $this->ensureTokenFresh();
        $gmailServiceClass = 'Google_Service_Gmail';
        $service = new $gmailServiceClass($this->client);
        return $service->users_messages->get('me', $messageId, ['format' => 'metadata', 'metadataHeaders' => ['Subject', 'From', 'Date']]);
    }

    public function getGmailThreadFirstMessage(string $threadId) {
        $this->ensureTokenFresh();
        $gmailServiceClass = 'Google_Service_Gmail';
        $service = new $gmailServiceClass($this->client);
        $thread = $service->users_threads->get('me', $threadId, ['format' => 'metadata', 'metadataHeaders' => ['Subject', 'From', 'Date']]);
        $messages = $thread->getMessages();
        return $messages && count($messages) > 0 ? $messages[0] : null;
    }

    public static function parseGmailUrl(string $input): array {
        $input = trim($input);
        if (preg_match('#mail.google.com/mail/#', $input)) {
            // URL de Gmail: buscar después de #
            $hash = parse_url($input, PHP_URL_FRAGMENT) ?: '';
            // Posibles formatos: all/<id>, inbox/<id>, starred/<id>
            if (preg_match('#^[^/]+/([a-f0-9]+)$#i', $hash, $m)) {
                return ['thread_id' => $m[1]];
            }
        }
        // Si parece un ID hex largo, asumir thread_id por defecto
        if (preg_match('#^[a-f0-9]{12,}$#i', $input)) {
            return ['thread_id' => $input];
        }
        return [];
    }
}
