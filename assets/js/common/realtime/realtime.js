import $ from "jquery";
import {UrlGenerator} from "../url-generator/url_generator";

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
    constructor(url, topic, isPrivate) {
        this._url = url;
        this._topic = topic;
        this._isPrivate = isPrivate === true;
        this._subscribers = [];
        this._authUrl = UrlGenerator.generate('app_realtime_auth');
    }

    connect() {
        if (this._isPrivate) {
            // authenticate to channel if it's private
            this.authenticate();

            return this;
        }
        this._connect();

        return this;
    }

    authenticate() {
        return $.ajax({
            url: this._authUrl,
            method: 'GET',
            data: {
                'topic': this._topic
            }
        }).then((data) => {
            if (true === data) {
                this._connect();
            }
        });
    }

    /**
     * @private
     */
    _connect() {
        if (this._isPrivate) {
            this._eventSource = new EventSource(this._url, { withCredentials: true});
        } else {
            this._eventSource = new EventSource(this._url);
        }
        window.addEventListener('beforeunload', () => {
            this._eventSource.close();
        });
        this._eventSource.onmessage = event => {
            this._subscribers.forEach((subscriber) => {
                subscriber.onmessage(event.data);
            })
        }
    }

    /**
     * @param {RealtimeSubscriberInterface} subscriber
     */
    subscribe(subscriber) {
        this._subscribers.push(subscriber)
    }
}

export {RealtimeSubscriberInterface, RealtimeChannel};