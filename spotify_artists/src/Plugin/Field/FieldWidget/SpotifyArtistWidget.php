<?php

namespace Drupal\spotify_artists\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\spotify_artists\ArtistsService;
use Drupal\spotify_artists\SpotifyApiService;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'field_example_text' widget.
 *
 * * @FieldWidget(
 *   id = "field_spotify_artist_widget",
 *   module = "spotify_artists",
 *   label = @Translation("Artists selection"),
 *   field_types = {
 *     "field_spotify_artist"
 *   }
 * )
 */
class SpotifyArtistWidget extends WidgetBase implements ContainerFactoryPluginInterface {


  /**
   * Artists service.
   *
   * @var \Drupal\spotify_artists\ArtistsService
   */

  public ArtistsService $artistsService;

  /**
   * API service.
   *
   * @var \Drupal\spotify_artists\SpotifyApiService
   */
  public SpotifyApiService $spotify;

  /**
   * Plugin config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $config;

  /**
   * Account proxy service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $accountProxy;

  /**
   * Route provider.
   *
   * @var \Drupal\Core\Routing\RouteProvider
   */
  private RouteProvider $route;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    SpotifyApiService $spotify,
    ArtistsService $artistsService,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $account_proxy,
    RouteProvider $routeProvider,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->spotify = $spotify;
    $this->artistsService = $artistsService;
    $this->config = $config_factory;
    $this->accountProxy = $account_proxy;
    $this->route = $routeProvider;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
  $plugin_id,
  $plugin_definition
  ) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('spotify.api'),
      $container->get('spotify.artists'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $value = $items[$delta]->value ?? '';
    $options = [];
    // Prepare message based on user access and path.
    $user_access = $this->accountProxy->getAccount()->hasPermission('administer site configuration');
    $path = $this->route->getRouteByName('spotify_artists.form_artists')->getPath();
    $no_artists_msg = $user_access && $path ? "check <a href='$path'>configuration</a>" : "contact administrator";

    // Get artists data from service.
    $artists = $this->artistsService->getArtists();
    if ($artists->status === 200) {
      $artists = $artists->artists;
      foreach ($artists as $artist) {
        $options[$artist->id] = $artist->name;
      }
      $element +=
          [
            '#type' => 'select',
            '#title' => $this->t('Now choose from these results'),
            '#limit_validation_errors' => [],
            '#options' => $options,
            '#default_value' => $value,
          ];
    }
    else {
      $element +=
          [
            '#type' => 'item',
            '#markup' => $this->t("No artists found, please") . $no_artists_msg,
          ];
    }

    return ['value' => $element];
  }

}
