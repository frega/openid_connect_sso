<?php

namespace Drupal\openid_connect_sso\Routing;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\openid_connect_sso\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {
  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
  }

  /**
   * Implements hook_init().
   *
   * Log out the user if he has the logout cookie (indicating that he has
   * already logged out of one the sites in the network).
   */
  public function handleNetworkLogout() {
    $account = \Drupal::currentUser();

    $needs_logout = !$account->isAnonymous() && openid_connect_sso_detect_cookie('logout');
    if ($needs_logout) {
      user_logout();
    }
    else {
      // Ensure that the logout cookie is removed.
      openid_connect_sso_remove_cookie('logout');
    }
  }

  /**
   * Implements hook_exit().
   *
   * Redirect to the SSO script, so that we set cookies on each network site.
   */
  function triggerNetworkRedirects(FilterResponseEvent $event) {
    $enabled = \Drupal::config('openid_connect_sso.settings')->get('enabled');
    if (!$enabled) {
      return;
    }
    $redirect = openid_connect_sso_get_redirect();
    if (!$redirect) {
      return;
    }

    $url_options = array(
      'absolute' => TRUE,
      'query' => array(
        'op' => $redirect,
        'origin_host' => $_SERVER['HTTP_HOST'],
      ),
    );

    /* if ($redirect == 'login') {
      // The $destination parameter is only set if this is invoked via
      // drupal_goto().
      if ($destination === NULL) {
        $options = array('absolute' => TRUE);
        if (isset($_GET['destination'])) {
          $path = $_GET['destination'];
        }
        else {
          $path = $_GET['q'];
          if ($query = \Drupal::request()->query->all()) {
            $options['query'] = $query;
          }
        }
        $destination_url = Url::fromURI($path, $options);
        // $destination = url($path, $options);
      }
      // Ensure that the destination set by drupal_goto() is an absolute URL.
      elseif (strpos($destination, 'https://') !== 0 && strpos($destination, 'http://') !== 0) {
        $parsed = UrlHelper::parse($destination);
        $destination = url($parsed['path'], $parsed + array('absolute' => TRUE));
      }

      // A core bug sets $destination to user/login when user/$uid is
      // requested instead. Even though that works, we set the proper url
      // here for clarity sake.
      $login_url = url('user/login', array('absolute' => TRUE));
      if (empty($destination) || $destination == $login_url) {
        $destination = url('user', array('absolute' => TRUE));
      }

      $request = $event->getRequest();

      $url = Url::fromUri('internal:/' . $request->getPathInfo(), [
        'absolute' => TRUE,
        'query' => $request->query->all()
      ]);

      // Set the destination to which the SSO script will return the user.
      $url_options['query']['destination'] = $url->toString();
    }
    else {
      // Redirect to frontpage
      $url = Url::fromUri('internal:/', ['absolute' => TRUE]);
      // Set the destination to which the SSO script will return the user.
      $url_options['query']['destination'] = $url->toString();
    }

    // drupal_goto() performs a drupal_exit() which calls hook_exit().
    // Thus, the redirect is performed manually, to avoid infinite loops.
    */

    // @todo: this is not correct in *some* circumstances but let's simplify.
    $destination_url = Url::fromUri('internal:/', [
      'absolute' => TRUE,
    ]);

    $url_options['query']['destination'] = $destination_url->toString();
    $sso_script_url = openid_connect_sso_get_script_url();
    $url = Url::fromUri($sso_script_url, $url_options);

    $response = new TrustedRedirectResponse($url->toString());
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('handleNetworkLogout');
    $events[KernelEvents::RESPONSE][] = array('triggerNetworkRedirects', 100);
    return $events;
  }

}
