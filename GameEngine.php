<?php

/**
 * GameEngine Class
 * 
 * Handles all game logic for the guessing game.
 */
class GameEngine
{
    /**
     * @var int The secret number to guess
     */
    private int $secretNumber;

    /**
     * @var int Minimum number in range
     */
    private int $min;

    /**
     * @var int Maximum number in range
     */
    private int $max;

    /**
     * @var string Unique game ID
     */
    private string $gameId;

    /**
     * @var array Array of guesses made in this game
     */
    private array $guesses = [];

    /**
     * @var bool Whether the game is finished
     */
    private bool $isFinished = false;

    /**
     * GameEngine Constructor
     * 
     * @param int $min Minimum number in range
     * @param int $max Maximum number in range
     * Allows us to create new instances of GameEngine with different ranges for the secret number i.e. 1-100, 1-1000
     * 
     * @return void
     */
    public function __construct(int $min = 1, int $max = 100)
    {
        $this->min = $min;
        $this->max = $max;
        $this->gameId = $this->generateGameId();
        $this->secretNumber = $this->generateSecretNumber();
    }

    /**
     * Generate a random secret number
     * 
     * @return int The randomly generated secret number
     */
    private function generateSecretNumber(): int
    {
        return rand($this->min, $this->max);
    }

    /**
     * Generate a unique game ID
     * 
     * @return string Unique identifier for this game session
     */
    private function generateGameId(): string
    {
        return uniqid('game_', true);
    }

    /**
     * Process a guess and return the result
     * 
     * @param int $guess The guessed number
     * @return array Array containing the result and feedback
     */
    public function makeGuess(int $guess): array
    {
        // Add guess to history
        $this->guesses[] = $guess;

        // Determine result
        if ($guess === $this->secretNumber) {
            $this->isFinished = true;
            $result = 'correct';
            $message = "Volltreffer! Die geheime Zahl war tatsächlich {$this->secretNumber}!";
        } elseif ($guess < $this->secretNumber) {
            $result = 'too_low';
            $message = "Zu niedrig! Versuchen Sie es mit einem höheren Wert.";
        } else {
            $result = 'too_high';
            $message = "Zu hoch! Versuchen Sie es mit einem niedrigeren Wert.";
        }

        return [
            'result' => $result,
            'message' => $message,
            'attempts' => count($this->guesses),
            'secret_number' => $this->isFinished ? $this->secretNumber : null
        ];
    }

    /**
     * Get the current game state
     * 
     * @return array Array containing all game information
     */
    public function getGameState(): array
    {
        return [
            'id' => $this->gameId,
            'secret_number' => $this->secretNumber,
            'min' => $this->min,
            'max' => $this->max,
            'guesses' => $this->guesses,
            'attempts' => count($this->guesses),
            'is_finished' => $this->isFinished
        ];
    }

    /**
     * Get the secret number (for debugging or after game ends)
     * 
     * @return int The secret number
     */
    public function getSecretNumber(): int
    {
        return $this->secretNumber;
    }

    /**
     * Get game ID
     * 
     * @return string The game ID
     */
    public function getGameId(): string
    {
        return $this->gameId;
    }

    /**
     * Get number of attempts made
     * 
     * @return int Number of guesses
     */
    public function getAttempts(): int
    {
        return count($this->guesses);
    }

    /**
     * Check if game is finished
     * 
     * @return bool True if the secret number has been guessed
     */
    public function isFinished(): bool
    {
        return $this->isFinished;
    }

    /**
     * Get all guesses made in this game
     * 
     * @return array Array of all guesses
     */
    public function getGuesses(): array
    {
        return $this->guesses;
    }

    /**
     * Reset the game with a new secret number
     * 
     * @return void
     */
    public function reset(): void
    {
        $this->secretNumber = $this->generateSecretNumber();
        $this->gameId = $this->generateGameId();
        $this->guesses = [];
        $this->isFinished = false;
    }
}
