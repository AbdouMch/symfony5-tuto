/**
 * @interface
 */
class RealtimeSubscriberInterface {
    /**
     * @abstract
     */
    onmessage(message) {
    };
}

class RealtimeChannel {
    constructor(url) {
        this._url = url;
        this._subscribers = [];
    }

    connect() {
        this._eventSource = new EventSource(this._url);
        window.addEventListener('beforeunload', () => {
            this._eventSource.close();
        });
        this._eventSource.onmessage = event => {
            this._subscribers.forEach((subscriber) => {
                subscriber.onmessage(event.data);
            })
        }
        return this;
    }

    /**
     * @param {RealtimeSubscriberInterface} subscriber
     */
    subscribe(subscriber) {
        this._subscribers.push(subscriber)
    }
}

export {RealtimeSubscriberInterface, RealtimeChannel};