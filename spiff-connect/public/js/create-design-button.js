const htmlDecode = input => {
		const document = new DOMParser().parseFromString(input, "text/html");
	return document.documentElement.textContent;
};

const spiffAppendCreateDesignButton = (integrationProductId, currencyCode, redirectUrl) => {
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

								transaction.on('complete', result => {
										console.log(result);
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
