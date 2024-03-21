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
    data.append("action", "spiff_create_cart_item");
    data.append(
      "spiff_create_cart_item_details",
      JSON.stringify({
        exportedData: result.exportedData,
        transactionId: result.transactionId,
        wooProductId,
      })
    );
    await fetch(ajax_object.ajax_url, {
      method: "POST",
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

const addTransactionFromCustomerPortalToCart = async (item) => {
    const data = new FormData();
    data.append("action", "spiff_create_cart_item");
    data.append(
      "spiff_create_cart_item_details",
      JSON.stringify({
        exportedData: item.exportedData,
        transactionId: item.transactionId,
      })
    );
    await fetch(ajax_object.ajax_url, {
      method: "POST",
      body: data,
    });
}

const spiffLaunchCustomerPortal = (applicationKey, redirectUrl) => {
  hostedExperienceOptions = {
    applicationKey,
    portalMode: true,
  };
  const hostedExperience = new window.Spiff.HostedExperience(hostedExperienceOptions);
  hostedExperience.on('complete', async (result) => {
    if (result.type === 'transaction') {
      console.log("SpiffCommerce - Adding Transaction to Cart");
      await addTransactionFromCustomerPortalToCart(result);
      window.location = htmlDecode(redirectUrl);
    } else if (result.type === 'bundle') {
      if (!result.items || result.items.length === 0) {
        throw new Error('SpiffCommerce - Bundle has no items');
      }
      console.log("SpiffCommerce - Adding Bundle to Cart");
      for (const item of result.items) {
        await addTransactionFromCustomerPortalToCart(item);
      }
      window.location = htmlDecode(redirectUrl);
    } else {
      throw new Error('SpiffCommerce - Unknown Experience Result Type');
    }
  });
  hostedExperience.execute({});
};

window.spiffAppendCreateDesignButton = spiffAppendCreateDesignButton;
window.spiffLaunchCustomerPortal = spiffLaunchCustomerPortal;
