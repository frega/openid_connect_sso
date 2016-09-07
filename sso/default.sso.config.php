<?php

// The collection of SSO script addresses which form the redirection network.
// Don't include the protocol (http://, https://).
// Example url (SSO script on subdomain): "a.firstsite.com"
// Example url (SSO script in the Drupal directory): "firstsite.com/sso.php"
$network = array(
  'a.firstsite.com',
  'a.shop.secondsite.com',
);

// An array of network domain names. The keys are potential origin host names
// which do not appear in the list above, and each value is the cookie domain
// name for that host.
// $domains = array();

// Enable HTTPS for all redirect URLs.
// $https = true;

// Enable adding the domain name to the cookie name.
// $cookie_name_strict = true;

