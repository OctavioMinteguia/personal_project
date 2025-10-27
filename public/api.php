<?php

// Simple routing
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

header('Content-Type: application/json');

// Database connection using PDO
try {
    $pdo = new PDO('sqlite:/var/www/html/var/database/app.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Real email function using SMTP with MailHog
function sendRealEmail($to, $subject, $body) {
    try {
        // Create email content
        $emailContent = "To: $to\r\n";
        $emailContent .= "From: noreply@jobberwocky.com\r\n";
        $emailContent .= "Subject: $subject\r\n";
        $emailContent .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $emailContent .= "\r\n";
        $emailContent .= $body;
        
        // Send to MailHog SMTP server
        $socket = fsockopen('mailhog', 1025, $errno, $errstr, 30);
        
        if (!$socket) {
            throw new Exception("Could not connect to MailHog: $errstr ($errno)");
        }
        
        // SMTP commands
        fputs($socket, "EHLO localhost\r\n");
        fputs($socket, "MAIL FROM: noreply@jobberwocky.com\r\n");
        fputs($socket, "RCPT TO: $to\r\n");
        fputs($socket, "DATA\r\n");
        fputs($socket, $emailContent);
        fputs($socket, "\r\n.\r\n");
        fputs($socket, "QUIT\r\n");
        
        fclose($socket);
        
        // Log the email attempt
        $logEntry = date('Y-m-d H:i:s') . " - Email sent to: $to, Subject: $subject, Result: SUCCESS\n";
        file_put_contents('/var/www/html/var/logs/emails.log', $logEntry, FILE_APPEND);
        
        return true;
        
    } catch (Exception $e) {
        $logEntry = date('Y-m-d H:i:s') . " - Email ERROR to: $to, Subject: $subject, Error: " . $e->getMessage() . "\n";
        file_put_contents('/var/www/html/var/logs/emails.log', $logEntry, FILE_APPEND);
        return false;
    }
}

// Route based on request data
$input = null;
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
}

// Determine action based on input content
if ($method === 'POST' && isset($input['email'])) {
    // Create alert
    if (empty($input['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email is required']);
        exit;
    }
    
    try {
        $id = uniqid('alert_', true);
        $now = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("
            INSERT INTO job_alerts (id, email, search_pattern, filters, active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id,
            $input['email'],
            $input['searchPattern'] ?? null,
            json_encode($input['filters'] ?? []),
            1,
            $now,
            $now
        ]);
        
        // Send confirmation email
        $subject = "Confirmación de Suscripción - Jobberwocky";
        $body = "Hola,\n\nTe has suscrito exitosamente a las alertas de trabajo en Jobberwocky.\n\n";
        $body .= "Patrón de búsqueda: " . ($input['searchPattern'] ?? 'Todos los trabajos') . "\n";
        $body .= "Filtros: " . json_encode($input['filters'] ?? []) . "\n\n";
        $body .= "Recibirás notificaciones cuando se publiquen trabajos que coincidan con tus criterios.\n\n";
        $body .= "¡Gracias por usar Jobberwocky!";
        
        $emailSent = sendRealEmail($input['email'], $subject, $body);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $id,
                'email' => $input['email'],
                'searchPattern' => $input['searchPattern'] ?? null,
                'filters' => $input['filters'] ?? [],
                'active' => true,
                'createdAt' => $now
            ],
            'emailSent' => $emailSent,
            'message' => $emailSent ? 'Suscripción creada y email de confirmación enviado' : 'Suscripción creada, pero hubo un problema enviando el email'
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} elseif ($method === 'POST' && isset($input['title'])) {
    // Create job
    if (empty($input['title']) || empty($input['company']) || empty($input['description'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Title, company, and description are required']);
        exit;
    }
    
    try {
        $id = uniqid('job_', true);
        $now = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("
            INSERT INTO jobs (id, title, company, description, location, salary, type, level, tags, remote, created_at, updated_at, source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id,
            $input['title'],
            $input['company'],
            $input['description'],
            $input['location'] ?? null,
            $input['salary'] ?? null,
            $input['type'] ?? 'full-time',
            $input['level'] ?? 'mid',
            json_encode($input['tags'] ?? []),
            $input['remote'] ?? false,
            $now,
            $now,
            'internal'
        ]);
        
        // Notify subscribers about new job
        $stmt = $pdo->prepare("SELECT * FROM job_alerts WHERE active = 1");
        $stmt->execute();
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $notificationsSent = 0;
        foreach ($alerts as $alert) {
            // Simple matching logic
            $matches = true;
            
            if (!empty($alert['search_pattern'])) {
                $pattern = strtolower($alert['search_pattern']);
                $title = strtolower($input['title']);
                $description = strtolower($input['description']);
                $company = strtolower($input['company']);
                
                if (strpos($title, $pattern) === false && 
                    strpos($description, $pattern) === false && 
                    strpos($company, $pattern) === false) {
                    $matches = false;
                }
            }
            
            if ($matches && !empty($alert['filters'])) {
                $filters = json_decode($alert['filters'], true);
                
                if (isset($filters['type']) && $filters['type'] !== ($input['type'] ?? 'full-time')) {
                    $matches = false;
                }
                
                if (isset($filters['level']) && $filters['level'] !== ($input['level'] ?? 'mid')) {
                    $matches = false;
                }
            }
            
            if ($matches) {
                $subject = "Nueva Oportunidad Laboral - " . $input['title'];
                $body = "Hola,\n\nSe ha publicado una nueva oportunidad laboral que coincide con tus criterios:\n\n";
                $body .= "Título: " . $input['title'] . "\n";
                $body .= "Empresa: " . $input['company'] . "\n";
                $body .= "Descripción: " . $input['description'] . "\n";
                $body .= "Ubicación: " . ($input['location'] ?? 'No especificada') . "\n";
                $body .= "Salario: " . ($input['salary'] ?? 'No especificado') . "\n";
                $body .= "Tipo: " . ($input['type'] ?? 'full-time') . "\n";
                $body .= "Nivel: " . ($input['level'] ?? 'mid') . "\n";
                $body .= "Remoto: " . (($input['remote'] ?? false) ? 'Sí' : 'No') . "\n\n";
                $body .= "¡No pierdas esta oportunidad!\n\n";
                $body .= "Saludos,\nEquipo Jobberwocky";
                
                if (sendRealEmail($alert['email'], $subject, $body)) {
                    $notificationsSent++;
                }
            }
        }
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $id,
                'title' => $input['title'],
                'company' => $input['company'],
                'description' => $input['description'],
                'location' => $input['location'] ?? null,
                'salary' => $input['salary'] ?? null,
                'type' => $input['type'] ?? 'full-time',
                'level' => $input['level'] ?? 'mid',
                'tags' => $input['tags'] ?? [],
                'remote' => $input['remote'] ?? false,
                'createdAt' => $now,
                'source' => 'internal'
            ],
            'notificationsSent' => $notificationsSent,
            'message' => "Trabajo creado y $notificationsSent notificaciones enviadas"
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} elseif ($method === 'GET') {
    // Search jobs
    $query = $_GET['q'] ?? '';
    $location = $_GET['location'] ?? '';
    $type = $_GET['type'] ?? '';
    $level = $_GET['level'] ?? '';
    $remote = $_GET['remote'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    
    try {
        $sql = "SELECT * FROM jobs WHERE 1=1";
        $params = [];
        
        if (!empty($query)) {
            $sql .= " AND (title LIKE ? OR company LIKE ? OR description LIKE ?)";
            $searchTerm = "%{$query}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($location)) {
            $sql .= " AND location LIKE ?";
            $params[] = "%{$location}%";
        }
        
        if (!empty($type)) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        if (!empty($level)) {
            $sql .= " AND level = ?";
            $params[] = $level;
        }
        
        if ($remote !== null) {
            $sql .= " AND remote = ?";
            $params[] = $remote ? 1 : 0;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to proper format
        $formattedJobs = array_map(function($job) {
            return [
                'id' => $job['id'],
                'title' => $job['title'],
                'company' => $job['company'],
                'description' => $job['description'],
                'location' => $job['location'],
                'salary' => $job['salary'],
                'type' => $job['type'],
                'level' => $job['level'],
                'tags' => json_decode($job['tags'], true) ?: [],
                'remote' => (bool)$job['remote'],
                'createdAt' => $job['created_at'],
                'source' => $job['source']
            ];
        }, $jobs);
        
        echo json_encode([
            'success' => true,
            'data' => $formattedJobs,
            'pagination' => [
                'total' => count($formattedJobs),
                'limit' => (int)$limit,
                'offset' => 0
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}