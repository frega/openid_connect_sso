<?php

namespace Drupal\openid_connect_sso_client\Routing;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\openid_connect_sso_client\Routing
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Don't allow users to edit their accounts, that needs to happen
    // on the server.
    // $items['user/%user/edit']['access callback'] = 'user_access';
    // $items['user/%user/edit']['access arguments'] = array('administer users');
    $route = $collection->get('entity.user.edit_form');
    $route->setRequirement('_permission', 'administer users');
    // $route->setRequirement('_entity_access', 'user.update');
  }

  /**
   * Implements hook_init().
   *
   * Start the OAuth2 login flow for an anonymous user if:
   * - they hit the 'user', 'user/login' or 'user/password' URL, unless ?admin
   *   is present in the URL.
   * - they have the login cookie (indicating that they have logged into another
   *   site in the network).
   */
  function openid_connect_sso_client_init(GetResponseEvent $event) {
    $account = \Drupal::currentUser();

    $anon = $account->isAnonymous();
    $current_route = \Drupal::routeMatch();

    $on_user_page = in_array($current_route->getRouteName(), array('user.page', 'user.login', 'user.pass'));
    $new_login = $anon && $on_user_page && !isset($_GET['admin']);
    $has_cookie = openid_connect_sso_detect_cookie('login');

    // @todo: remove this legacy args()-business.
    $path_info = $event->getRequest()->getPathInfo();
    $args = explode('/', ltrim($path_info, '/'));
    $args0 = !empty($args[0]) ? $args[0]: NULL;
    $args1 = !empty($args[1]) ? $args[1]: NULL;

    // This variable indicates that an OAuth2 flow was already started, and the
    // server has just redirected the user back to the redirect callback.
    $in_flow = $args0 == 'openid-connect' && $args1 && isset($_GET['state']);

    $network_login = $has_cookie && !$in_flow;

    if ($network_login && !$anon) {
      user_logout();
    }

    if ($new_login || $network_login) {
      openid_connect_save_destination();

      $client = _openid_connect_sso_client_get_client();
      $scopes = _openid_connect_sso_client_get_scopes();

      $authorization_redirect_response = $client->authorize($scopes);
      $event->setResponse($authorization_redirect_response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('openid_connect_sso_client_init');
    return $events;
  }

}
