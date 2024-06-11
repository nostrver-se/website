# nostrver.se

Drupal website for https://nostrver.se.

## Docker

See `docker/docker-compose.yml`.

## CI/CD

See `.gitlab-ci.yml`

## Start development

1. `cd docker`
2. `docker compose up -d`
3. `docker compose exec drpl_drupal bash`
4. `composer install`
5. `drush si standard` for a clean site install
6. Delete existing entities
   * `drush entity-delete shortcut` delete shortcut entities
7. Enable devel module `drush en devel`
8. Generate a new UUID for the site `drush uuid`
9. Save this site UUID value site in `system.site.yml`
10. `drush cset system.site uuid <the_uuid>` for setting the UUID in the database
11. `drush cim` for importing current config files
12. `drush cr`
13. Navigate to http://localhost in your browser

## Update Drupal core + contribs

1. `composer outdated`
2. `composer update -W`
3. `drush cr`
4. `drush updb`
5. `drush cex`
6. `composer clearcache`

## What modules are included?

* AdvAgg
* Config Split
* Config Ignore
* Drush
* Raven
* Backup Migrate
* Symfony Mailer
* Reroute Email
* Paragraphs
* Admin Toolbar
* Admin Dialogs
* Gin
* Gin Login
* Admin Dialogs
* Pathauto
* Masquerade
* Ultimate Cron
* Advanced CSS/JS Aggregation
* Media Library Edit
* PWA
* Markdown Easy
* WebP
* Swiper Formatter

Development only:
* Coder
* Devel
* Devel Entity Updates
* Webprofiler
* Drupal Coder
* Drupal Rector

### Config split configurations

```php
$config['config_split.config_split.dev']['status'] = TRUE|FALSE;
$config['config_split.config_split.acceptance']['status'] = TRUE|FALSE;
$config['config_split.config_split.production']['status'] = TRUE|FALSE;
```
`settings.local.php`

#### Production

#### Acceptance

#### Development

## Security checks

@TODO - https://github.com/FriendsOfPHP/security-advisories and https://github.com/fabpot/local-php-security-checker

## Code checks

@TODO - https://www.drupal.org/project/coder
