Important Notes on this D8-version:

0. This is not yet on parity w/ the D7 version of openid_connect_sso.
1. You need to patch openid_connect.module with this patch: https://www.drupal.org/files/issues/2796697-return-response-object.patch for any of this to work.
2. This is not working with caching. This should be fixable quickly, but for the time being add this snippet to the settings.php

if (isset($_COOKIE['Drupal_visitor_SSOLogout']) || isset($_COOKIE['Drupal_visitor_SSOLogin'])) {
  $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
  $settings['cache']['bins']['render'] = 'cache.backend.null';
}

And adjust your services.yml with this chunk:

services:
  cache.backend.null:
    class: Drupal\Core\Cache\NullBackendFactory

3. sso.php script:
    - I have not tested the a.-Subdomain-based approach
    - I have split config from the sso.php script (s. sso/sso-config.php)
    - There's basic syslog logging.
    - I have adjusted the workings a little bit to allow for subpaths (e.g. test-a.dev/d8_openid_connect_sso_client/web/sso.php,test-b.dev/d8_openid_connect_sso_client/web/sso.php).
    - Therefore I have adjusted the workings to provide the full destination URL instead of the path on the origin_host - Yes, THERE IS AN OPEN REDIRECT ISSUE (see also in the D7 issue queue)!

4. Currently we're redirecting to <front> on all pages on the openid_connect_sso-client side (should be a relatively easy fix).
5. The settings form for the client_id (chosing which openid_connect-clients to use for authorization) is not working ATM.

Provides a single sign-on solution based on OpenID Connect.

The OpenID Connect server (central place of login) is a Drupal site running oauth2_server.
The clients are Drupal sites running openid_connect.

After the user's login on the server or logout on any of the network sites,
the module starts a redirect chain that visits the SSO script of each site in the network.
The SSO script then sets a cookie notifying the parent site of the pending login / logout.
When the user visits the actual site, the cookie is read, and the user logged in / out automatically.

This is the same approach used by Google Accounts.
The point of the redirects is to give each site a chance to set a cookie valid for its domain,
thus going around the same-origin policy that forbids a site from setting a cookie for another domain.
The redirects are fast and unnoticeable, since the SSO script is standalone (no Drupal bootstrap) and only sets the cookie.

See the documentation for more examples and setup instructions:
https://drupal.org/node/2274367
