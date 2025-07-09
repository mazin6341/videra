<?php

namespace App\Services;

use App\Clients\IgdbClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class IgdbService {
    public function __construct(protected IgdbClient $igdb_client) {}

    /**
     * Searches for a game by its Steam App ID.
     *
     * @param string $steam_appid The Steam App ID of the game.
     * @return array The game data, or an empty array if not found.
     * @throws GuzzleException
     */
    public function searchBySteamAppId(string $steam_appid): array {
        try {
            $fields = $this->getIgdbGameFields();
            $response = $this->igdb_client->request('POST', 'external_games', [
                RequestOptions::BODY => "fields {$fields}; where category = 1 & uid = \"{$steam_appid}\";"
            ]);

            $results = json_decode($response->getBody()->getContents(), true) ?? [];
            $igdbGame = $results[0]['game'] ?? [];

            if (empty($igdbGame)) {
                return [];
            }

            return $this->transformIgdbToModel($igdbGame);
        } catch (GuzzleException $e) {
            throw $e;
        }
    }

    /**
     * Gets the Game's Time to Beat data from the IGDB API.
     *
     * @param int $igdb_game_id The IGDB ID of the game.
     * @return array The time to beat data, or an empty array if not found.
     * @throws GuzzleException
     */
    public function getTimeToBeat(int $igdb_game_id): array {
        try {
            $response = $this->igdb_client->request('POST', 'game_time_to_beats', [
                RequestOptions::BODY => "fields hastily,normally,completely; where game_id = ({$igdb_game_id});"
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            throw $e;
        }
    }

    /**
     * Gets the IGDB fields required to populate the Game model.
     *
     * @return string
     */
    private function getIgdbGameFields(): string {
        $fields = [
            'name',
            'summary',
            'aggregated_rating',
            'rating',
            'platforms.name',
            'id',
        ];

        return 'game.' . implode(', game.', $fields);
    }

    /**
     * Transforms the raw data from the IGDB API to match our Game model structure.
     *
     * @param array $igdbGame
     * @return array
     */
    private function transformIgdbToModel(array $igdb_game): array {
        $timeToBeat = $this->getTimeToBeat($igdb_game['id']);

        return [
            'title' => $igdb_game['name'] ?? null,
            'description' => $igdb_game['summary'] ?? null,
            'igdb_id' => $igdb_game['id'] ?? null,
            'critic_score' => isset($igdb_game['aggregated_rating']) ? round($igdb_game['aggregated_rating']) : null,
            'user_score' => isset($igdb_game['rating']) ? round($igdb_game['rating']) : null,
            'main_story_completion_time' => $timeToBeat[0]['hastily'] ?? null,
            'completionist_time' => $timeToBeat[0]['completely'] ?? null,
            'platforms' => isset($igdb_game['platforms']) ? array_column($igdb_game['platforms'], 'name') : [],
        ];
    }
}