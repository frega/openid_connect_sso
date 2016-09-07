<?php

namespace Drupal\openid_connect_sso\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigManager;

/**
 * Class SettingsForm.
 *
 * @package Drupal\openid_connect_sso\Form
 */
class SettingsForm extends ConfigFormBase  {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openid_connect_sso.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openid_connect_sso_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openid_connect_sso.settings');
    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable SSO'),
      '#default_value' => (bool) $config->get('enabled'),
    );

    $form['script_url'] = array(
      '#type' => 'textfield',
      '#title' => t('SSO script URL'),
      '#description' => t('The first URL to visit in the SSO redirection chain.'),
      '#default_value' => $config->get('script_url'),
    );

    $form['cookie_domain'] = array(
      '#type' => 'textfield',
      '#title' => t('Cookie domain'),
      '#description' => t('The domain name to use when clearing SSO cookies. Leave this blank to use the current host name.'),
      '#default_value' => $config->get('cookie_domain'),
      // @todo: add validation
      // '#element_validate' => array($this, 'openid_connect_sso_validate_cookie_domain'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Validate the cookie domain.
   */
  function openid_connect_sso_validate_cookie_domain(&$element, &$form_state) {
    if (strlen($element['#value'])) {
      $domain = ltrim($element['#value'], '.');
      if (parse_url('http://' . $domain, PHP_URL_HOST) != $domain) {
        form_error($element, t('Invalid cookie domain'));
      }
    }
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->configFactory()->getEditable('openid_connect_sso.settings');

    foreach(['enabled', 'script_url', 'cookie_domain'] as $key) {
      $value = $form_state->getValue($key);
      $config->set($key, $value)->save();
    }

    $config_reloaded = $this->config('openid_connect_sso.settings');

  }

}
