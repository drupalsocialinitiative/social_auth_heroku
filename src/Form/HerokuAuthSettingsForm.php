<?php

namespace Drupal\social_auth_heroku\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Settings form for Social Auth Heroku.
 */
class HerokuAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_heroku_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_heroku.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_heroku.settings');

    $form['heroku_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Heroku Client settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Heroku API Client at <a href="@heroku-dev">@heroku-dev</a>',
          ['@heroku-dev' => 'https://dashboard.heroku.com/account/applications']),
    ];

    $form['heroku_settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the ID here.'),
    ];

    $form['heroku_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Client Secret here.'),
    ];

    $form['heroku_settings']['authorized_redirect_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('OAuth Callback URL'),
      '#description' => $this->t('Copy this value to <em>OAuth Callback URL</em> field of your Heroku App settings.'),
      '#default_value' => Url::fromRoute('social_auth_heroku.callback')->setAbsolute()->toString(),
    ];

    $form['heroku_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['heroku_settings']['advanced']['scopes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Scopes for API call'),
      '#default_value' => $config->get('scopes'),
      '#description' => $this->t('Define any additional scopes to be requested, separated by a comma (e.g.: read,write).<br>
                                  The scope  \'identity\' is added by default.<br>
                                  You can see the full list of valid scopes and their description <a href="@scopes">here</a>.',
                                    ['@scopes' => 'https://devcenter.heroku.com/articles/oauth#scopes']),
    ];

    $form['heroku_settings']['advanced']['endpoints'] = [
      '#type' => 'textarea',
      '#title' => $this->t('API calls to be made to collect data'),
      '#default_value' => $config->get('endpoints'),
      '#description' => $this->t('Define the endpoints to be requested when user authenticates with Heroku for the first time<br>
                                  Enter each endpoint in different lines in the format <em>endpoint</em>|<em>name_of_endpoint</em>.<br>
                                  <b>For instance:</b><br>
                                  /apps|apps_list<br>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_heroku.settings')
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->set('scopes', $values['scopes'])
      ->set('endpoints', $values['endpoints'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
