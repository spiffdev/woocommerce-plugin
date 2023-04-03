const htmlDecode = input => {
    const document = new DOMParser().parseFromString(input, "text/html");
    return document.documentElement.textContent;
};

const spiffAppendCreateDesignButton = (wooProductId, integrationProductId, currencyCode, redirectUrl, text, size, weight, textColor, backgroundColor, width) => {
    const integrationProduct = new window.Spiff.IntegrationProduct(integrationProductId);

    integrationProduct.on('ready', () => {
        const pageSessionId = window.Spiff.Analytics.createPageSession();
        const containers = document.querySelectorAll(`.spiff-button-integration-product-${integrationProductId}`);
        containers.forEach(container => {
            const button = document.createElement('button');
            if (buttonStyles) {
                button.innerText = text;
                button.fontSize = size;
                button.background = backgroundColor;
                button.color = textColor;
                button.fontWeight = weight;
                button.width = width;
            }
            else {
                // Default
                button.innerText = "Personalize now";
            }

            button.border = "none";
            button.cursor = "pointer";
            button.className = "test-create-design";
            
            button.onclick = () => {
                const transaction = new window.Spiff.Transaction({
                    presentmentCurrency: currencyCode,
                    integrationProduct,
                    pageSessionId,
                });

                transaction.on('complete', async result => {
                    const data = new FormData();
                    data.append('action', 'spiff_create_cart_item')
                    data.append('spiff_create_cart_item_details', JSON.stringify({
                        exportedData: result.exportedData,
                        transactionId: result.transactionId,
                        wooProductId,
                    }));
                    await fetch(ajax_object.ajax_url, {
                        method: 'POST',
                        body: data,
                    });
                    window.location = htmlDecode(redirectUrl);
                });

                transaction.execute();
            };

            container.appendChild(button);
        });
    });

    integrationProduct.on('invalid', () => {
        console.error("Spiff product could not be found.");
    });

    integrationProduct.confirmActive();
};

const spiffAppendCreateDesignButtonBulk = (wooProductId, integrationProductId, currencyCode, redirectUrl, text, size, weight, textColor, backgroundColor, width) => {
    const integrationProduct = new window.Spiff.IntegrationProduct(integrationProductId);

    integrationProduct.on('ready', () => {
        const pageSessionId = window.Spiff.Analytics.createPageSession();
        const containers = document.querySelectorAll(`.spiff-button-bulk-integration-product-${integrationProductId}`);
        containers.forEach(container => {
            const button = document.createElement('button');
            if (buttonStyles) {
                button.innerText = text;
                button.fontSize = size;
                button.background = backgroundColor;
                button.color = textColor;
                button.fontWeight = weight;
                button.width = width;
            }
            else {
                // Default
                button.innerText = "Personalize now";
            }
            
            button.border = "none";
            button.cursor = "pointer";
            button.className = "test-create-design";

            button.onclick = () => {
                const transaction = new window.Spiff.Transaction({
                    presentmentCurrency: currencyCode,
                    integrationProduct,
                    pageSessionId,
                    bulk: true
                });

                transaction.on('complete', async result => {
                    const data = new FormData();
                    data.append('action', 'spiff_create_cart_item')
                    data.append('spiff_create_cart_item_details', JSON.stringify({
                        exportedData: result.exportedData,
                        transactionId: result.transactionId,
                        wooProductId,
                    }));
                    await fetch(ajax_object.ajax_url, {
                        method: 'POST',
                        body: data,
                    });
                    window.location = htmlDecode(redirectUrl);
                });

                transaction.execute();
            };

            container.appendChild(button);
        });
    });

    integrationProduct.on('invalid', () => {
        console.error("Spiff product could not be found.");
    });

    integrationProduct.confirmActive();
};

window.spiffAppendCreateDesignButton = spiffAppendCreateDesignButton;
window.spiffAppendCreateDesignButtonBulk = spiffAppendCreateDesignButtonBulk;
