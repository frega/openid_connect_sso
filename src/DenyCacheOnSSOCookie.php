<?php

namespace Drupal\openid_connect_sso;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Symfony\Component\HttpFoundation\Request;

class DenyCacheOnSSOCookie implements RequestPolicyInterface  {
  /**
   * Deny page/request caching to kick in if we have these SSO login or logout
   * cookies set.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return string
   */
  public function check(Request $request) {
    if ($request->cookies->get('Drupal_visitor_SSOLogout') || $request->cookies->get('Drupal_visitor_SSOLogin')) {
      return self::DENY;
    }
    else {
      return NULL;
    }
  }
}
