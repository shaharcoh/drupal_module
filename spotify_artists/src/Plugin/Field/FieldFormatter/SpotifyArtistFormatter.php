<?php

namespace Drupal\spotify_artists\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\spotify_artists\Service\ArtistsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'field_spotify_artist' formatter.
 *
 * @FieldFormatter(
 *   id = "field_spotify_artist_formatter",
 *   label = @Translation("Display Spotify artist info"),
 *   field_types = {
 *     "field_spotify_artist"
 *   }
 * )
 */
class SpotifyArtistFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    protected ArtistsService $artistsService
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('spotify.artists'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $selectedId = $items[0] ? $items[0]->value : '';
    $artistData = [];
    if ($selectedId) {
      $artistsData = $this->artistsService->getArtists($selectedId);

      $ids = [];
      // Create an array of ids in order to find the index of selected artist.
      foreach ($artistsData['artists'] as $artist) {
        $ids[] = $artist->id;
      }
      if ($artistsData['status'] === 200) {
        $artistData = $artistsData['artists'][array_search($selectedId, $ids)];
      }

    }
    return [
      '#theme' => 'spotify_artists_field',
      '#artist' => $artistData ?: '',
    ];
  }

}
