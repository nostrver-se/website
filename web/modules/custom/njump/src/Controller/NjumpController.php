<?php

declare(strict_types=1);

namespace Drupal\njump\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use swentel\nostr\Event\Profile\Profile;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Nip19\Nip19Helper;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Request\Request as NostrRequest;
use swentel\nostr\Subscription\Subscription;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Njump routes.
 */
final class NjumpController extends ControllerBase {

  /**
   * Builds the response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function __invoke(Request $request): RedirectResponse|array {
    // Determine the identifier first.
    $identifier = $request->attributes->get('identifier');
    $pos = strrpos($identifier, '1');
    if ($pos === false) {
      throw new \RuntimeException(message: 'Invalid bech32 string');
    }
    $prefix = substr($identifier, 0, $pos);
    $nip19Helper = new Nip19Helper();
    $event = [];
    switch ($prefix) {
      case 'note':
        $decoded = $nip19Helper->decodeNote($identifier);
        $event['id'] = $decoded['event_id'];
        break;
      case 'nevent':
        $decoded = $nip19Helper->decode($identifier);
        $event['id'] = $decoded['event_id'];
        if (isset($decoded['relays']) && !empty($decoded['relays'])) {
          $relays = [];
          foreach ($decoded['relays'] as $relay) {
            $relays[] = new Relay($relay);
          }
        }
        if (isset($decoded['author'])) {
          $event['pubkey'] = $decoded['author'];
        }
        break;
      case 'naddr':
        $decoded = $nip19Helper->decode($identifier);
        if (isset($decoded['identifier'])) {
          $event['dTag'] = $decoded['identifier'];
        }
        if (isset($decoded['relays']) && !empty($decoded['relays'])) {
          $relays = [];
          foreach ($decoded['relays'] as $relay) {
            $relays[] = new Relay($relay);
          }
        }
        if (isset($decoded['author'])) {
          $event['pubkey'] = $decoded['author'];
        }
        if (isset($decoded['kind'])) {
          $event['kind'] = $decoded['kind'];
        }
        break;
      default:
        // hex formatted id
        $event['id'] = $identifier;
        break;
    }

    // Fetch event.
    $subscription = new Subscription();
    $subscriptionId = $subscription->setId();
    $filter1 = new Filter();
    if (isset($event['id'])) {
      $filter1->setIds([$event['id']]);
    }
    if (isset($event['dTag'])) {
      $filter1->setTag('#d', [$event['dTag']]);
    }
    if (isset($event['pubkey'])) {
      $filter1->setAuthors([$event['pubkey']]);
    }
    if (isset($event['kind'])) {
      $filter1->setKinds([$event['kind']]);
    }
    $filter1->setLimit(1);
    $filters = [$filter1];
    $requestMessage = new RequestMessage($subscriptionId, $filters);
    if (!isset($relays)) {
      $relays = [
        new Relay('wss://nos.lol'),
        new Relay('wss://relay.nostr.band'),
      ];
    }
    $relaySet = new RelaySet();
    $relaySet->setRelays($relays);
    $nRequest = new NostrRequest($relaySet, $requestMessage);
    $response = $nRequest->send();

    foreach ($response as $relayUrl => $relayResponses) {
      if (count($response[$relayUrl]) > 0) {
        foreach ($relayResponses as $message) {
          if ($message instanceof RelayResponseEvent) {
            $event = $message->event;
          }
        }
      }
    }

    // Fetch profile from pubkey of the event
    $profile = new Profile();
    $profile->fetch($event->pubkey, 'wss://relay.nostr.band');
    $profile_content = json_decode($profile->getContent(), true);

    // TODO check if node already exist with this nostr event id
    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
    $node = $nodeStorage->loadByProperties([
      'created' => $event->created_at,
      'field_nostr_id' => $event->id,
    ]);
    if ($node) {
      // TODO Do we need to update the existing node? Which conditions we have to check?
      // Depends on the type of the event
      $nostr_event_node = reset($node);
      // Redirect to node page
      $url = Url::fromRoute('entity.node.canonical', ['node' => $nostr_event_node->id()]);
      return new RedirectResponse($url->toString());
    } else {
      // Create for each tag a paragraph entity
      $tags = [];
      foreach ($event->tags as $tag) {
        $paragraph_tag = Paragraph::create([
          'type' => 'nostr_tags',
          'status' => 1,
          'field_nostr_tag_name' => $tag[0],
          'field_nostr_tag_value' => $tag[1],
          // TODO process parameters
          'field_nostr_tag_parameters' => $tag[3] ?? [],
        ]);
        $paragraph_tag->save();
        $tags[] = $paragraph_tag;
        // Override title if set
        if ($tag[0] === 'title') {
          $node_title = $tag[1];
        }
      }
      // Create and save Nostr event as a node entity.
      $nostr_event_node = Node::create([
        //'uid' => 2, // TODO should we create a user entity first so we can set the author here?
        'type' => 'nostr_event',
        'title' => $node_title ?? $event->id,
        'created' => $event->created_at,
        'status' => 1,
        'field_nostr_id' => $event->id,
        'field_nostr_pubkey' => $event->pubkey,
        'field_nostr_kind' => $event->kind,
        'field_nostr_content' => $event->content,
        'field_nostr_signature' => $event->sig,
        'field_nostr_tags' => $tags,
      ]);
      $nostr_event_node->save();

      // TODO process URL aliasses for this node
      //$node_path = "/node/$nid";
      //$new_url = $parent_url_alias . $url_alias;
      //
      ///** @var \Drupal\path_alias\PathAliasInterface $path_alias */
      //$path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
      //  'path' => $node_path,
      //  'alias' => $new_url,
      //  'langcode' => 'en',
      //]);
    }

    // Encode as JSON string.
    $event = json_encode($event, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

    // View builder to render node
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');

    return [
      '#theme' => 'njump',
      '#title' => $nostr_event_node->getTitle(), // only set the h1 title, not the meta page title
      '#identifier' => $identifier,
      '#event' => $event,
      '#author' => [
        'profile' => $profile,
        'profile_content' => $profile_content
      ],
      '#nostr_event_node' => $view_builder->view($nostr_event_node, 'default'),
    ];
  }

}
