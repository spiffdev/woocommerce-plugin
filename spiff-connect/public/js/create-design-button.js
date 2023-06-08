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
        const button = createButton(buttonConfig.nonBulkText, buttonConfig, buttonClass);
        container.appendChild(button);

        button.onclick = () => showSpiffTransaction(product, currencyCode, integrationProductId, wooProductId, redirectUrl, false);
      });
  });

  product.confirmActive();
};

const showSpiffTransaction = ( product, currencyCode, integrationProductId, wooProductId, redirectUrl, isBulk ) => {
  
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


const spiffAppendCreateDesignButtonBulk = (wooProductId, integrationProductId, currencyCode, redirectUrl, buttonConfig) => {
  const product = new window.Spiff.IntegrationProduct(integrationProductId);
  const buttonClass = "test-create-design";

  product.on('ready', () => {
    const containers = document.querySelectorAll(`.spiff-button-bulk-integration-product-${integrationProductId}`);
    containers.forEach(container => {
      const button = createButton(buttonConfig.bulkText, buttonConfig, buttonClass);
      container.appendChild(button);

      button.onclick = () => showSpiffTransaction(product, currencyCode, integrationProductId, wooProductId, redirectUrl, true);
      //button.onclick = () => executeTransaction(currencyCode, integrationProduct, wooProductId, redirectUrl, true);
    });
  });

  //integrationProduct.on('invalid', () => console.error("Spiff product could not be found."));
  product.confirmActive();
};

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
window.spiffAppendCreateDesignButtonBulk = spiffAppendCreateDesignButtonBulk;


