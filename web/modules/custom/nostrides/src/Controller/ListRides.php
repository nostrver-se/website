<?php

declare(strict_types=1);

namespace Drupal\nostrides\Controller;

use Drupal\Core\Controller\ControllerBase;
use Sentry\Util\JSON;
use swentel\nostr\Filter\Filter;
use swentel\nostr\Message\RequestMessage;
use swentel\nostr\Relay\Relay;
use swentel\nostr\RelayResponse\RelayResponseEvent;
use swentel\nostr\Request\Request;
use swentel\nostr\Subscription\Subscription;
use function PHPUnit\Framework\isInstanceOf;

/**
 * Returns responses for Nostrides routes.
 */
final class ListRides extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    /**
     * Request 30101 event kinds from relays:
     * - wss://nos.lol
     */
    $subscription = new Subscription();
    $subscriptionId = $subscription->setId();
    $filter1 = new Filter();
    $filter1->setKinds([30100]);
    $filter1->setLimit(50);
    $filters = [$filter1];
    $requestMessage = new RequestMessage($subscriptionId, $filters);
    $relay = new Relay('wss://relay.nostr.band');
    $request = new Request($relay, $requestMessage);
    $response = $request->send();

    // Process response.
    $events = [];
    foreach ($response as $relayUrl => $relayResponses) {
      foreach ($relayResponses as $message) {
        if ($message instanceof RelayResponseEvent) {
          // Check if title is set as tag.
          $title = '';
          if ($message->event->tags) {
            foreach ($message->event->tags as $tag) {
              if ($tag[0] === 'title') {
                $title = $tag[1];
              }
            }
          }
          if (isset($title) && $title !== '') {
            // Content field is JSON stringified.
            $content = json_decode($message->event->content, TRUE);
            if (is_array($content)) {
              $content = json_encode($content, JSON_PRETTY_PRINT);
            } else {
              $content = $message->event->content;
            }
            $events[] = [
              'id' => $message->event->id,
              'title' => $title,
              'pubkey' => $message->event->pubkey,
              'content' => $content,
              'tags' => $message->event->tags,
            ];
          }
        }
      }
    }
    return [
      '#theme' => 'nostrides-list',
      '#events' => $events,
    ];
  }

}
