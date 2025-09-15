(function (window, document) {
    "use strict";

    var ai = {
        queue: [],
        batchSize: 5,          // Number of events per batch
        retryLimit: 3,         // Maximum retries per batch
        retryDelay: 1000,      // Initial delay in ms
        collectEndpoint: window.AppInsightsConfig?.collectEndpoint || "/appinsights/collect",

        trackEvent: function (name, properties = {}) {
            this.queue.push({ type: 'event', name, properties });
            console.log("Tracked Event:", name, properties);
            if (this.queue.length >= this.batchSize) this.flush();
        },

        trackException: function (error, properties = {}) {
            try {
                if (error instanceof Error) {
                    error = { message: error.message, stack: error.stack };
                }
                this.queue.push({ type: 'exception', error, properties });
                console.error("Tracked Exception:", error, properties);
                if (this.queue.length >= this.batchSize) this.flush();
            } catch (e) {
                console.error("Failed to track exception:", e);
            }
        },

        flush: function () {
            if (!this.collectEndpoint || this.queue.length === 0) return;

            var batch = this.queue.splice(0, this.batchSize);
            this.sendBatch(batch, 0);
        },

        sendBatch: function (batch, attempt) {
            var self = this;
            try {
                fetch(this.collectEndpoint, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(batch)
                }).then(function (res) {
                    if (!res.ok) throw new Error("HTTP error " + res.status);
                }).catch(function (err) {
                    console.error("Telemetry send failed:", err);
                    if (attempt < self.retryLimit) {
                        setTimeout(function () {
                            self.sendBatch(batch, attempt + 1);
                        }, self.retryDelay * Math.pow(2, attempt)); // exponential backoff
                    } else {
                        console.error("Telemetry batch dropped after retries:", batch);
                    }
                });
            } catch (e) {
                console.error("Failed to flush telemetry batch:", e);
            }
        }
    };

    window.appInsights = ai;

    window.addEventListener("error", function (event) {
        ai.trackException({
            message: event.message,
            filename: event.filename,
            lineno: event.lineno,
            colno: event.colno,
            stack: event.error ? event.error.stack : null
        });
        ai.flush();
    });

    window.addEventListener("unhandledrejection", function (event) {
        ai.trackException({
            message: "Unhandled promise rejection",
            reason: event.reason
        });
        ai.flush();
    });

    // Optional: flush before page unload
    window.addEventListener("beforeunload", function () {
        ai.flush();
    });

})(window, document);
