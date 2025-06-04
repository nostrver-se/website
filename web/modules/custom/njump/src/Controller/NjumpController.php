<?php

declare(strict_types=1);

namespace Drupal\njump\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use swentel\nostr\Event\Event;
use swentel\nostr\Event\List\RelayListMetadata;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Key\Key;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Nip19\Nip19Helper;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Request\Request as NostrRequest;
use swentel\nostr\Subscription\Subscription;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    // Handle different identifiers in this switch case.
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
        if (!empty($decoded['relays'])) {
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
      case 'nprofile':
        $decoded = $nip19Helper->decode($identifier);
        $event['pubkey'] = $decoded['pubkey'];
        $event['kind'] = 0;
        if (!empty($decoded['relays'])) {
          $event['relays'] = $decoded['relays'];
        } else {
          $relayListMetadata = new RelayListMetadata($decoded['pubkey']);
          if(!empty($relayListMetadata->getRelays())) {
            $event['relays'] = $relayListMetadata->getWriteRelays();
          }
        }
        break;
      case 'npub':
        $key = new Key();
        $event['pubkey'] = $key->convertToHex($identifier);
        $event['kind'] = 0;
        break;
      default:
        // hex formatted id
        $event['id'] = $identifier;
        break;
    }

    // Fetch event with a subscription and request.
    $subscription = new Subscription();
    $subscriptionId = $subscription->setId();
    // Set filters.
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
    // Create request message.
    $requestMessage = new RequestMessage($subscriptionId, $filters);
    // Set relays where the request message is being sent to.
    if (!isset($relays)) {
      $relays = [
        new Relay('wss://nos.lol'),
        new Relay('wss://relay.nostr.band'),
      ];
    }
    $relaySet = new RelaySet();
    $relaySet->setRelays($relays);
    // Create request with the relays and request message.
    $nRequest = new NostrRequest($relaySet, $requestMessage);
    // Send it.
    $response = $nRequest->send();

    // Handle all responses from the request.
    foreach ($response as $relayUrl => $relayResponses) {
      if (count($response[$relayUrl]) > 0) {
        foreach ($relayResponses as $message) {
          if ($message instanceof RelayResponseEvent) {
            $event = new Event();
            $event->populate($message->event);
            break;
          }
        }
      }
    }

    // We got nothing, show a page not found page.
    if (!isset($event)) {
      throw new NotFoundHttpException();
    }

    // Fetch profile from pubkey of the event
//    $profile = new Profile();
//    $profile->fetch($event->getPublicKey());
//    $profile_content = json_decode($profile->getContent(), true);

    // TODO check if node already exist with this nostr event id
    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
    $node = $nodeStorage->loadByProperties([
      'created' => $event->getCreatedAt(),
      'field_nostr_id' => $event->getId(),
    ]);
    if ($node) {
      // TODO Do we need to update the existing node? Which conditions we have to check?
      // Depends on the type of the event
      $nostr_event_node = reset($node);
    } else {
      // Create for each tag a paragraph entity
      $tags = [];
      foreach ($event->getTags() as $tag) {
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
        $title = $event->getTag('title');
        if ($title) {
          $node_title = $title[0][1];
        }
      }
      // Create and save Nostr event as a node entity.
      $nostr_event_node = Node::create([
        //'uid' => 2, // TODO should we create a user entity first so we can set the author here?
        'type' => 'nostr_event',
        'title' => $node_title ?? $event->getId(),
        'created' => $event->getCreatedAt(),
        'status' => 1,
        'field_nostr_id' => $event->getId(),
        'field_nostr_pubkey' => $event->getPublicKey(),
        'field_nostr_kind' => $event->getKind(),
        'field_nostr_content' => $event->getContent(),
        'field_nostr_signature' => $event->getSignature(),
        'field_nostr_tags' => $tags,
      ]);
      $nostr_event_node->save();

      if ($identifier) {
        // Add identifier string as a URL alias for this node
        $node_path = '/node/'.$nostr_event_node->id();
        $alias = '/e/'.$identifier;
        /** @var \Drupal\path_alias\PathAliasInterface $path_alias */
        $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
          'path' => $node_path,
          'alias' => $alias,
          'langcode' => 'en',
        ]);
        $path_alias->save();
        // TODO check if we can generate the other identifiers for this node too? So we could have as much identifiers as URL aliasses as possible:
        // - note1
        // - nevent1
        // - naddr1
        // - nprofile1
        // - npub1
      }
    }

    // TODO remove this code here below
    // Encode as JSON string.
    //$event = json_encode($event, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

    // View builder to render node
    //$view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    // Return render array for the node.
    //return $view_builder->view($nostr_event_node, 'default');

    $url = Url::fromRoute('entity.node.canonical', ['node' => $nostr_event_node->id()]);
    return new RedirectResponse($url->toString());
  }
}
