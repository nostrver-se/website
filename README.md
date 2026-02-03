# nostrver.se

Drupal website for https://nostrver.se.

## Docker

See `docker/docker-compose.yml`.

## CI/CD

See `.gitlab-ci.yml`

## Nostr contrib modules

In this Drupal instance I'm developing several modules using Nostr.

* [**Nostr AppleSauce**](https://www.drupal.org/project/nostr_applesauce)
  Drupal module to implement the [**AppleSauce SDK**](https://hzrd149.github.io/applesauce).
* [**Nostr Wallet Connect**](https://drupal.org/project/nostr_wallet_connect)
  Drupal module for connecting your wallet using NWC.
* [**Nostr Profile**](https://drupal.org/project/nostr_profile)
  Drupal module for managing Nostr profile entities for user accounts.
* [**Nostr Event**](https://drupal.org/project/nostr_event)
  Drupal module for handling Nostr events as nodes.
* [**Nostr internet identifier NIP-05**](https://www.drupal.org/project/nostr_id_nip05)
  Drupal module to setup Nostr internet identifier addresses with Drupal
* [**Nostr Simple Publish**](https://www.drupal.org/project/nostr_simple_publish)
  Drupal module to cross-post notes from Drupal to Nostr
* [**Nostr long-form content NIP-23**](https://www.drupal.org/project/nostr_content_nip23)
  Drupal module to cross-post Markdown formatted content from Drupal to Nostr
* [**Nostr Dev Kit**](https://www.drupal.org/project/nostr_dev_kit)
  Drupal module to implement the NDK js library.

You can read my opinion on how we could use Nostr with Drupal: https://nostrver.se/blog/nostr-empowered-drupal-initiative

## Security checks

@TODO - https://github.com/FriendsOfPHP/security-advisories and https://github.com/fabpot/local-php-security-checker

## Code checks

@TODO - https://www.drupal.org/project/coder
