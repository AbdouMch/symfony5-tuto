class RealtimeChannel {
    constructor( url) {
        this._url = url;
    }

    connect() {
        this._eventSource = new EventSource(this._url);

        return this;
    }

    onMessage() {
        return new Promise((resolve) => {
            this._eventSource.onmessage = event => {
                resolve(event.data)
            }
        })
    }
}

export { RealtimeChannel };