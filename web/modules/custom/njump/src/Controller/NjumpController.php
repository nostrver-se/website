<?php

declare(strict_types=1);

namespace Drupal\njump\Controller;

use Drupal\Core\Controller\ControllerBase;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\Relay\RelaySet;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Request\Request as NostrRequest;
use swentel\nostr\Subscription\Subscription;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Njump routes.
 */
final class NjumpController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(Request $request): array {

    $event = [];
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
    $request = new NostrRequest($relaySet, $requestMessage);
    $response = $request->send();

    foreach ($response as $relayUrl => $relayResponses) {
      if (count($response[$relayUrl]) > 0) {
        foreach ($relayResponses as $message) {
          if ($message instanceof RelayResponseEvent) {
            $event = $message->event;
          }
        }
      }
    }

    // Encode as JSON string.
    $event = json_encode($event, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);

    return [
      '#theme' => 'njump',
      '#event' => $event,
    ];
  }

}
