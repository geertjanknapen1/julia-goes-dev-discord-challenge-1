<?php

// Prevent all output except JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once 'GameEngine.php';

// File-based storage for active games (simple temp file approach)
$gamesFile = sys_get_temp_dir() . '/number_guessing_games.json';
$statsFile = sys_get_temp_dir() . '/number_guessing_stats.json';

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
 * Load stats from file
 */
function loadStats(): array
{
    global $statsFile;
    if (!file_exists($statsFile)) {
        return ['played_games' => 0, 'highscore' => null];
    }
    
    $content = file_get_contents($statsFile);
    if (!$content) {
        return ['played_games' => 0, 'highscore' => null];
    }
    
    $stats = json_decode($content, true);
    return is_array($stats) ? $stats : ['played_games' => 0, 'highscore' => null];
}

/**
 * Save stats to file
 */
function saveStats(array $stats): void
{
    global $statsFile;
    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));
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
    
    // Increment played games
    $stats = loadStats();
    $stats['played_games']++;
    saveStats($stats);
    
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
    
    // Update highscore if correct
    if ($result['result'] === 'correct') {
        $stats = loadStats();
        $attempts = $result['attempts'];
        if ($stats['highscore'] === null || $attempts < $stats['highscore']) {
            $stats['highscore'] = $attempts;
            saveStats($stats);
        }
    }
    
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
 * Get global stats
 */
function getStats(): array
{
    return loadStats();
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
            break;

        case 'state':
            if (!isset($_GET['game_id']) || empty($_GET['game_id'])) {
                throw new Exception('Missing game_id parameter');
            }
            $result = getGameState($_GET['game_id']);
            break;

        case 'stats':
            $result = getStats();
            break;

        default:
            throw new Exception('Invalid action: ' . htmlspecialchars($action));
    }

    // Output result as JSON
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'result' => 'error'
    ]);
}
