<?php

/**
 * OpenRouter API Client
 * 
 * A PHP client for interacting with the OpenRouter AI API.
 * Provides methods to send chat completion requests to various AI models.
 * 
 * Usage:
 * ```php
 * // Single model
 * $openRouter = new OpenRouter('your-api-key', 'openai/gpt-3.5-turbo');
 * 
 * $messages = [
 *     ["role" => "system", "content" => "You are a helpful assistant"],
 *     ["role" => "user", "content" => "Hello, how are you?"]
 * ];
 * 
 * $response = $openRouter->request($messages);
 * 
 * // Multiple models
 * $openRouter = new OpenRouter('your-api-key');
 * $openRouter->setModels([
 *     'openai/gpt-3.5-turbo',
 *     'anthropic/claude-3-haiku',
 *     'google/gemma-7b-it'
 * ]);
 * 
 * $response = $openRouter->request($messages);
 * ```
 * 
 * @author Ivan Milincic
 * @version 1.1
 */

namespace Nautilus;

class OpenRouter {

  private const ENDPOINT = "https://openrouter.ai/api/v1/chat/completions";

  private string $api_key;
  private ?string $model = null;
  private array $models = [];
  private ?string $system_instruction = null;

  /**
   * Constructor
   * 
   * @param string|null $api_key
   * @param string|null $model
   */
  public function __construct(?string $api_key = null, ?string $model = null) {
    if ($api_key) {
      $this->setApiKey($api_key);
    }
    if ($model) {
      $this->setModel($model);
    }
  }

  /**
   * Request - alias for req() method for better readability
   * 
   * @param array $messages
   * @return array
   */
  public function request(array $messages = []): array {
    return $this->req($messages);
  }

  /**
   * Request
   * Basic request to Open Router API
   * 
   * @param array $messages
   * @throws \Exception
   * @return array
   */
  public function req(array $messages = []): array {
    $this->validateRequest($messages);

    $data = $this->prepareRequestData($messages);
    return $this->cURL($data);
  }

  /**
   * Validate request data
   * 
   * @param array $messages
   * @throws \Exception
   */
  private function validateRequest(array $messages): void {
    if (empty($this->api_key)) {
      throw new \Exception("API key is required");
    }

    if (empty($this->models) && empty($this->model)) {
      throw new \Exception("Model or models must be set");
    }

    if (empty($messages)) {
      throw new \Exception("Messages array cannot be empty");
    }
  }

  /**
   * Prepare request data
   * 
   * @param array $messages
   * @return array
   */
  private function prepareRequestData(array $messages): array {
    $data = [];

    // Add model or models
    if (!empty($this->models)) {
      $data["models"] = $this->models;
    } else {
      $data["model"] = $this->model;
    }

    // Prepend system instruction if set
    if ($this->system_instruction) {
      array_unshift($messages, [
        "role" => "system",
        "content" => $this->system_instruction
      ]);
    }

    $data["messages"] = $messages;
    return $data;
  }

  /**
   * cURL
   * Send request to Open Router API
   * 
   * @param array $data
   * @throws \Exception
   * @return array
   */
  public function cURL(array $data): array {
    $jsonData = json_encode($data);
    if ($jsonData === false) {
      throw new \Exception("Failed to encode request data to JSON");
    }

    $ch = curl_init(self::ENDPOINT);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $jsonData,
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer {$this->api_key}"
      ],
      CURLOPT_TIMEOUT => 30,
      CURLOPT_CONNECTTIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
      $error = curl_error($ch);
      curl_close($ch);
      throw new \Exception("cURL Error: " . $error);
    }

    curl_close($ch);

    if ($response === false) {
      throw new \Exception("Failed to get response from API");
    }

    $decodedResponse = json_decode(trim($response), true);
    if ($decodedResponse === null) {
      throw new \Exception("Failed to decode API response");
    }

    if ($httpCode >= 400) {
      $errorMessage = $decodedResponse['error']['message'] ?? 'Unknown API error';
      throw new \Exception("API Error ({$httpCode}): " . $errorMessage);
    }

    return $decodedResponse;
  }

  // Setters and Getters 
  // ========================================================= 

  public function setInstructions(string $instruction): self {
    $this->system_instruction = $instruction;
    return $this;
  }

  public function getInstructions(): ?string {
    return $this->system_instruction;
  }

  public function setApiKey(string $api_key): self {
    if (empty(trim($api_key))) {
      throw new \InvalidArgumentException("API key cannot be empty");
    }
    $this->api_key = trim($api_key);
    return $this;
  }

  public function getApiKey(): ?string {
    return $this->api_key;
  }

  public function setModel(string $model): self {
    if (empty(trim($model))) {
      throw new \InvalidArgumentException("Model cannot be empty");
    }
    $this->model = trim($model);
    $this->models = []; // Clear models array when setting single model
    return $this;
  }

  public function getModel(): ?string {
    return $this->model;
  }

  public function setModels(array $models): self {
    if (empty($models)) {
      throw new \InvalidArgumentException("Models array cannot be empty");
    }
    $this->models = array_filter($models, 'trim');
    $this->model = null; // Clear single model when setting multiple models
    return $this;
  }

  public function getModels(): array {
    return $this->models;
  }
}
