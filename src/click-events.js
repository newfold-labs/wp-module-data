{
    /**
    * When a user clicks on something that has a `data-nfd-click` attribute, send an event to the API endpoint.
    *
    * @param {Event} e The event object.
    */
    const processEvent = (e) => {
        // If it's a link, prevent the default behavior until the event is sent.
        if (e.target.tagName === 'A') {
            e.preventDefault();
        }

        const target = e.target;
        const eventId = target.getAttribute('data-nfd-click');
        const eventKey = target.getAttribute('data-nfd-event-key') || eventId;
        const eventCategory = target.getAttribute('data-nfd-event-category') || 'plugin';
        const brand = target.getAttribute('data-nfd-brand') || window.nfdHiiveEvents.brand;
        const queue = target.getAttribute('data-nfd-queue') || false;

        const eventData = {
            action: eventId,
            category: eventCategory,
            queue,
            data: {
                label_key: eventKey,
                brand,
                page: window.location.href,
            }
        };

				// To handle marketplace items.
				if (target.getAttribute('data-nfd-product-id')) {
					eventData.data.product_id = target.getAttribute('data-nfd-product-id');
				}

        // Send event to the API
        window.wp.apiFetch({
            url: window.nfdHiiveEvents.eventEndpoint,
            method: 'POST',
            data: eventData
        }).catch((error) => {
            console.error('Error sending event to API', error);
        });

        // Send user to the link.
        if (e.target.tagName === 'A') {
            window.location.href = e.target.href;
        }
    };

    window.addEventListener('load', () => {
        document.querySelector('#wpwrap').addEventListener('click', (e) => {
            if (e.target.hasAttribute('data-nfd-click')) {
                processEvent(e);
            }
        });
    });
}
