const htmlDecode = input => {
    const document = new DOMParser().parseFromString(input, "text/html");
    return document.documentElement.textContent;
};

const spiffAppendCreateDesignButton = (wooProductId, integrationProductId, currencyCode, redirectUrl, text, size, weight, textColor, backgroundColor, width, height) => {
    const integrationProduct = new window.Spiff.IntegrationProduct(integrationProductId);

    integrationProduct.on('ready', () => {
        const containers = document.querySelectorAll(`.spiff-button-integration-product-${integrationProductId}`);
        containers.forEach(container => {
            const button = document.createElement('button');

            button.innerText = text;
            button.style = `font-size: ${size}; background: ${backgroundColor}; color: ${textColor}; font-weight: ${weight}; width: ${width}; height: ${height}; cursor: pointer; border: none;`;

            button.className = "test-create-design";
            
            button.onclick = () => {
                const transaction = new window.Spiff.Transaction({
                    presentmentCurrency: currencyCode,
                    integrationProduct
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

const spiffAppendCreateDesignButtonBulk = (wooProductId, integrationProductId, currencyCode, redirectUrl, text, size, weight, textColor, backgroundColor, width, height) => {
    const integrationProduct = new window.Spiff.IntegrationProduct(integrationProductId);

    integrationProduct.on('ready', () => {
        const containers = document.querySelectorAll(`.spiff-button-bulk-integration-product-${integrationProductId}`);
        containers.forEach(container => {
            const button = document.createElement('button');

            button.innerText = text;
            button.style = `font-size: ${size}; background: ${backgroundColor}; color: ${textColor}; font-weight: ${weight}; width: ${width}; height: ${height}; cursor: pointer; border: none;`;
            
            button.className = "test-create-design";

            button.onclick = () => {
                const transaction = new window.Spiff.Transaction({
                    presentmentCurrency: currencyCode,
                    integrationProduct,
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
