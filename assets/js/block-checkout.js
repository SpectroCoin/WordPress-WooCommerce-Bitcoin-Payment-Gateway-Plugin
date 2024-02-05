const settings = window.wc.wcSettings.getSetting("spectrocoin_data", {});
const label =
  window.wp.htmlEntities.decodeEntities(settings.title) ||
  window.wp.i18n.__("SpectroCoin", "spectrocoin-accepting-bitcoin");
const Content = () => {
  return window.wp.element.createElement(
    "div",
    {},
    settings.checkout_icon &&
      window.wp.element.createElement("img", {
        src: settings.checkout_icon,
        alt: window.wp.i18n.__(
          "SpectroCoin Logo",
          "spectrocoin-accepting-bitcoin"
        ),
        style: { marginBottom: "10px" },
      }),
    window.wp.element.createElement(
      "div",
      {},
      window.wp.htmlEntities.decodeEntities(settings.description || "")
    )
  );
}

const SpectroCoinBlockGateway = {
  name: "spectrocoin",
  label: label,
  content: Object(window.wp.element.createElement)(Content, null),
  edit: Object(window.wp.element.createElement)(Content, null),
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: ["products"], // Update based on your gateway's supported features
  },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(SpectroCoinBlockGateway);
