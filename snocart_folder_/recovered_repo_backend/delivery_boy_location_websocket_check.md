Yes, the delivery boy app is indeed getting location from the delivery boy app using websockets.

Here's the evidence:

1.  **`config/websockets.php`**: This configuration file explicitly maps the websocket route `'delivery-man/live-location'` to the handler class `\App\WebSockets\Handler\DMLocationSocketHandler::class`. This indicates that a dedicated handler is in place to process delivery man location data via websockets.

2.  **`app/WebSockets/Handler/DMLocationSocketHandler.php`**:
    *   The `onMessage` method within this class is responsible for processing incoming websocket messages.
    *   It expects a JSON payload containing `token`, `longitude`, and `latitude`.
    *   It authenticates the delivery man using the provided `token`.
    *   Upon successful authentication, it updates or creates a record in the `DeliveryHistory` table with the received `longitude` and `latitude`.
    *   It includes logic to `checkNearbyCustomer` and potentially send notifications.
    *   It broadcasts the updated location using an event (`DeliveryManLocationUpdated`), which can be consumed by other parts of the application for real-time tracking (e.g., on a customer-facing map).
    *   It sends an acknowledgment back to the delivery man's app.

This setup clearly demonstrates that the application uses websockets to receive, process, store, and broadcast live location data from delivery boys.