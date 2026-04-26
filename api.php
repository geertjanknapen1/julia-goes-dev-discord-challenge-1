<?php

// Buffer output to prevent headers already sent errors, set content type and clean buffer.
ob_start();
header('Content-Type: application/json; charset=utf-8');
ob_end_clean();

require_once 'GameEngine.php';

// File-based storage for active games (simple temp file approach)
$gamesFile = sys_get_temp_dir() . '/number_guessing_games.json';

/**
 * Load all active games from file
 */
function loadGames(): array
{
    global $gamesFile;
    if (!file_exists($gamesFile)) {
        return [];
    }
    
    $content = file_get_contents($gamesFile);
    if (!$content) {
        return [];
    }
    
    $games = json_decode($content, true);
    return is_array($games) ? $games : [];
}

/**
 * Save games to file
 */
function saveGames(array $games): void
{
    global $gamesFile;
    file_put_contents($gamesFile, json_encode($games, JSON_PRETTY_PRINT));
}

/**
 * Initialize a new game
 */
function initGame(): array
{
    $engine = new GameEngine(1, 100);
    $gameId = $engine->getGameId();
    
    // Store game as serialized string
    $games = loadGames();
    $games[$gameId] = serialize($engine);
    saveGames($games);
    
    return $engine->getGameState();
}

/**
 * Process a guess in the current game
 */
function processGuess(string $gameId, int $guess): array
{
    $games = loadGames();
    
    if (!isset($games[$gameId])) {
        return [
            'error' => 'Game not found',
            'result' => 'error'
        ];
    }
    
    // Deserialize the game engine and process the guess, update stored game.
    $engine = unserialize($games[$gameId]);
    $result = $engine->makeGuess($guess);
    $games[$gameId] = serialize($engine);
    saveGames($games);
    
    return $result;
}

/**
 * Get current game state
 */
function getGameState(string $gameId): array
{
    $games = loadGames();
    
    if (!isset($games[$gameId])) {
        return [
            'error' => 'Game not found',
            'result' => 'error'
        ];
    }
    
    $engine = unserialize($games[$gameId]);
    return $engine->getGameState();
}

/**
 * Main handler - routes requests based on action and returns JSON responses
 */
try {
    $action = $_GET['action'] ?? '';

    if (empty($action)) {
        throw new Exception('No action specified');
    }

    switch ($action) {
        case 'init':
            $result = initGame();
            if ($result === null) {
                throw new Exception('Failed to initialize game');
            }
            echo json_encode($result);
            break;

        case 'guess':
            // Get POST data
            $input = file_get_contents('php://input');
            if (empty($input)) {
                throw new Exception('No POST data received');
            }
            
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON: ' . json_last_error_msg());
            }
            
            if (!isset($data['game_id']) || empty($data['game_id'])) {
                throw new Exception('Missing game_id parameter');
            }
            
            if (!isset($data['guess']) || !is_numeric($data['guess'])) {
                throw new Exception('Invalid guess parameter');
            }

            $result = processGuess($data['game_id'], (int)$data['guess']);
            echo json_encode($result);
            break;

        case 'state':
            if (!isset($_GET['game_id']) || empty($_GET['game_id'])) {
                throw new Exception('Missing game_id parameter');
            }
            $result = getGameState($_GET['game_id']);
            echo json_encode($result);
            break;

        default:
            throw new Exception('Invalid action: ' . htmlspecialchars($action));
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'result' => 'error'
    ]);
}
