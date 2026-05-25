/**
 * Frontend GTM Fallback Tracking Integration
 * Prevents duplicates by communicating back to server after client-side GTM push.
 */
window.reportTrackingFallback = function (orderId, invoiceId, csrfToken, fallbackUrl) {
    if (!orderId || !invoiceId || !fallbackUrl) {
        console.warn('Fallback tracking arguments missing.');
        return;
    }

    var payload = {
        order_id: parseInt(orderId, 10),
        invoice_id: invoiceId
    };

    if (window.fetch) {
        fetch(fallbackUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            console.log('Fallback tracking status:', data);
        })
        .catch(function (error) {
            console.error('Fallback tracking call failed:', error);
        });
    } else {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', fallbackUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        console.log('Fallback tracking status:', res);
                    } catch (e) {
                        console.log('Fallback tracking success:', xhr.responseText);
                    }
                } else {
                    console.error('Fallback tracking call failed with status:', xhr.status);
                }
            }
        };
        xhr.send(JSON.stringify(payload));
    }
};
