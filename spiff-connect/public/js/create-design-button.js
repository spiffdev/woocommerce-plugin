const htmlDecode = input => {
    const document = new DOMParser().parseFromString(input, "text/html");
    return document.documentElement.textContent;
};

const spiffAppendCreateDesignButton = (wooProductId, integrationProductId, currencyCode, redirectUrl) => {
    const integrationProduct = new window.Spiff.IntegrationProduct(integrationProductId);

    integrationProduct.on('ready', () => {
        const containers = document.querySelectorAll(`.spiff-button-integration-product-${integrationProductId}`);
        containers.forEach(container => {
            const button = document.createElement('button');
            button.innerText = "Personalize now";

            button.onclick = () => {
                const transaction = new window.Spiff.Transaction({
                    presentmentCurrency: currencyCode,
                    integrationProduct,
                });

                transaction.on('complete', async result => {
                    const data = new FormData();
                    data.append('action', 'spiff_create_cart_item')
                    data.append('spiff_create_cart_item_details', JSON.stringify({
                        exportedData: result.exportedData,
                        price: result.baseCost + result.optionsCost,
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
