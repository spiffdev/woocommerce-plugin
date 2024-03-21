const htmlDecode = input => {
  const document = new DOMParser().parseFromString(input, "text/html");
  return document.documentElement.textContent;
};

const spiffAppendCreateDesignButton = (wooProductId, integrationProductId, currencyCode, redirectUrl, buttonConfig) => {
 
  const product = new window.Spiff.IntegrationProduct(integrationProductId);
  const buttonClass = "test-create-design";

    product.on("ready", () => {
      const containers = document.querySelectorAll(`.spiff-button-integration-product-${integrationProductId}`);
      containers.forEach(container => {
        const button = createButton(buttonConfig.personalizeButtonText, buttonConfig, buttonClass);
        container.appendChild(button);

        button.onclick = () => showSpiffTransaction(product, currencyCode, wooProductId, redirectUrl);
      });
  });

  product.confirmActive();
};

const showSpiffTransaction = ( product, currencyCode, wooProductId, redirectUrl) => {
  const hostedExperienceOptions = {
      presentmentCurrency: currencyCode,
      product: product,
  };
  const hostedExperience = new window.Spiff.HostedExperience(hostedExperienceOptions);
  hostedExperience.on("complete", async (result) => {
      // Handle the result of the workflow experience..
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
  hostedExperience.execute();
}


const createButton = (text, buttonConfig, buttonClass) => {
  const button = document.createElement('button');
  button.innerText = text;
  button.style = `font-size: ${buttonConfig.size}; background: ${buttonConfig.backgroundColor}; color: ${buttonConfig.textColor}; font-weight: ${buttonConfig.weight}; width: ${buttonConfig.width}; height: ${buttonConfig.height}; cursor: pointer; border: none;`;
  button.className = buttonClass;
  return button;
}

const executeTransaction = async (currencyCode, integrationProduct, wooProductId, redirectUrl, isBulk) => {
  const transaction = new window.Spiff.Transaction({
    presentmentCurrency: currencyCode,
    integrationProduct,
    bulk: isBulk
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
}

window.spiffAppendCreateDesignButton = spiffAppendCreateDesignButton;
