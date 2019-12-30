<?php

namespace Drupal\social_auth_heroku\Plugin\Network;

use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_heroku\Settings\HerokuAuthSettings;
use Stevenmaguire\OAuth2\Client\Provider\Heroku;

/**
 * Defines a Network Plugin for Social Auth Heroku.
 *
 * @package Drupal\social_auth_heroku\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_heroku",
 *   social_network = "Heroku",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_heroku\Settings\HerokuAuthSettings",
 *       "config_id": "social_auth_heroku.settings"
 *     }
 *   }
 * )
 */
class HerokuAuth extends NetworkBase implements HerokuAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \Stevenmaguire\OAuth2\Client\Provider\Heroku|false
   *   The initialized 3rd party library instance.
   *
   * @throws \Drupal\social_api\SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {

    $class_name = 'Stevenmaguire\OAuth2\Client\Provider\Heroku';
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Heroku Library for the league oAuth not found. Class: %s.', $class_name));
    }

    /** @var \Drupal\social_auth_heroku\Settings\HerokuAuthSettings $settings */
    $settings = $this->settings;

    if ($this->validateConfig($settings)) {

      // All these settings are mandatory.
      $league_settings = [
        'clientId' => $settings->getClientId(),
        'clientSecret' => $settings->getClientSecret(),
        'redirectUri' => Url::fromRoute('social_auth_heroku.callback')->setAbsolute()->toString(),
      ];

      // Proxy configuration data for outward proxy.
      $proxyUrl = $this->siteSettings->get('http_client_config')['proxy']['http'];
      if ($proxyUrl) {
        $league_settings['proxy'] = $proxyUrl;
      }

      return new Heroku($league_settings);
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_heroku\Settings\HerokuAuthSettings $settings
   *   The Heroku auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(HerokuAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();
    if (!$client_id || !$client_secret) {
      $this->loggerFactory
        ->get('social_auth_heroku')
        ->error('Define Client ID and Client Secret on module settings.');

      return FALSE;
    }

    return TRUE;
  }

}
