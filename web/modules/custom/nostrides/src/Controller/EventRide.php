<?php

declare(strict_types=1);

namespace Drupal\nostrides\Controller;

use Drupal\Core\Controller\ControllerBase;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

/**
 * Returns responses for nostrides routes.
 */
final class EventRide extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(HttpRequest $request): array {
    $event['id'] = $request->attributes->get('event_id');

    // Fetch event.
    $subscription = new Subscription();
    $subscriptionId = $subscription->setId();
    $filter1 = new Filter();
    $filter1->setIds([$event['id']]);
    $filter1->setLimit(1);
    $filters = [$filter1];
    $requestMessage = new RequestMessage($subscriptionId, $filters);
    $relays = [
      new Relay('wss://nos.lol'),
      new Relay('wss://relay.damus.io'),
      new Relay('wss://relay.nostr.band'),
    ];
    $relaySet = new RelaySet();
    $relaySet->setRelays($relays);
    $request = new Request($relaySet, $requestMessage);
    $response = $request->send();

    foreach ($response as $relayUrl => $relayResponses) {
      if (count($response[$relayUrl]) > 0) {
        foreach ($relayResponses as $message) {
          if ($message instanceof RelayResponseEvent) {
            $tags = $message->event->tags;
            $event['dTag'] = $this->getTagValue($tags, 'd');
            $event['title'] = $this->getTagValue($tags, 'title');
            $event['content'] = $message->event->content;
            $event['gpx'] = $this->getTagValue($tags, 'r');
            $event['recorded_at'] = $this->getTagValue($tags, 'recorded_at');
          }
        }
      }
    }

    // Fetch referenced 30101 kind event.
    // Get d-tag from event.
    if (isset($event['dTag'])) {
      $filter1 = new Filter();
      $filter1->setKinds([30101]);
      $filter1->setLimit(100);
      // TODO
//      $filter1->setTags(
//        [
//          '#d' => $event['dTag'],
//        ],
//      );
      $filters = [$filter1];
      $requestMessage = new RequestMessage($subscriptionId, $filters);
      $request = new Request($relaySet, $requestMessage);
      $response = $request->send();

    }

    return [
      '#theme' => 'nostrides-item',
      '#event' => $event,
    ];

  }

  private function getTagValue (array $tags, string $key) {
    foreach ($tags as $tag) {
      if ($tag[0] === $key) {
        $value = $tag[1];
      }
    }
    return $value;
  }

}
