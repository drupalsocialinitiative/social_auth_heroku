<?php

namespace Drupal\social_auth_heroku\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_heroku\HerokuAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Returns responses for Simple Heroku Connect module routes.
 */
class HerokuAuthController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The user manager.
   *
   * @var \Drupal\social_auth\SocialAuthUserManager
   */
  private $userManager;

  /**
   * The heroku authentication manager.
   *
   * @var \Drupal\social_auth_heroku\HerokuAuthManager
   */
  private $herokuManager;

  /**
   * Used to access GET parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_auth\SocialAuthDataHandler
   */
  private $dataHandler;


  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * HerokuAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_heroku network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_heroku\HerokuAuthManager $heroku_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $social_auth_data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(NetworkManager $network_manager, SocialAuthUserManager $user_manager, HerokuAuthManager $heroku_manager, RequestStack $request, SocialAuthDataHandler $social_auth_data_handler, LoggerChannelFactoryInterface $logger_factory) {

    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->herokuManager = $heroku_manager;
    $this->request = $request;
    $this->dataHandler = $social_auth_data_handler;
    $this->loggerFactory = $logger_factory;

    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_heroku');

    // Sets the session keys to nullify if user could not logged in.
    $this->userManager->setSessionKeysToNullify(['access_token', 'oauth2state']);
    $this->setting = $this->config('social_auth_heroku.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth_heroku.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.social_auth_data_handler'),
      $container->get('logger.factory')
    );
  }

  /**
   * Response for path 'user/login/heroku'.
   *
   * Redirects the user to Heroku for authentication.
   */
  public function redirectToHeroku() {
    /* @var \League\OAuth2\Client\Provider\Heroku false $heroku */
    $heroku = $this->networkManager->createInstance('social_auth_heroku')->getSdk();

    // If heroku client could not be obtained.
    if (!$heroku) {
      drupal_set_message($this->t('Social Auth Heroku not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Heroku service was returned, inject it to $herokuManager.
    $this->herokuManager->setClient($heroku);

    // Generates the URL where the user will be redirected for Heroku login.
    // If the user did not have email permission granted on previous attempt,
    // we use the re-request URL requesting only the email address.
    $heroku_login_url = $this->herokuManager->getHerokuLoginUrl();

    $state = $this->herokuManager->getState();

    $this->dataHandler->set('oauth2state', $state);

    return new TrustedRedirectResponse($heroku_login_url);
  }

  /**
   * Response for path 'user/login/heroku/callback'.
   *
   * Heroku returns the user here after user has authenticated in Heroku.
   */
  public function callback() {
    // Checks if user cancel login via Heroku.
    $error = $this->request->getCurrentRequest()->get('error');
    if ($error == 'access_denied') {
      drupal_set_message($this->t('You could not be authenticated.'), 'error');
      return $this->redirect('user.login');
    }

    /* @var \League\OAuth2\Client\Provider\Heroku false $heroku */
    $heroku = $this->networkManager->createInstance('social_auth_heroku')->getSdk();

    // If Heroku client could not be obtained.
    if (!$heroku) {
      drupal_set_message($this->t('Social Auth Heroku not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    $state = $this->dataHandler->get('oauth2state');

    // Retreives $_GET['state'].
    $retrievedState = $this->request->getCurrentRequest()->query->get('state');
    if (empty($retrievedState) || ($retrievedState !== $state)) {
      $this->userManager->nullifySessionKeys();
      drupal_set_message($this->t('Heroku login failed. Unvalid oAuth2 State.'), 'error');
      return $this->redirect('user.login');
    }

    // Saves access token to session.
    $this->dataHandler->set('access_token', $this->herokuManager->getAccessToken());

    $this->herokuManager->setClient($heroku)->authenticate();

    // Gets user's info from Heroku API.
    if (!$heroku_profile = $this->herokuManager->getUserInfo()) {
      drupal_set_message($this->t('Heroku login failed, could not load Heroku profile. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // If user information could be retrieved.
    return $this->userManager->authenticateUser($heroku_profile->getName(), $heroku_profile->getEmail(), $heroku_profile->getId(), $this->herokuManager->getAccessToken(), '', '');

  }

}
