const alphaSettings = window.wc.wcSettings.getSetting('alpha_bank_type_data');
const defaultLabel = window.wp.i18n.__('Alpha Bank', 'alphabank');

/**
 * Label component
 */
const alphaLabel = window.wp.htmlEntities.decodeEntities(alphaSettings.title) || defaultLabel;

/**
 * Content component
 */
const AlphaContent = () =>{
	return  window.wp.htmlEntities.decodeEntities(alphaSettings.description || '');
}

/**
 * Payment method config object.
 */
const Block_Gateway = {
	name: 'alphabank',
	label: alphaLabel,
	content: Object(window.wp.element.createElement)(AlphaContent, null),
	edit: Object(window.wp.element.createElement)(AlphaContent, null),
	canMakePayment: () => true,
	ariaLabel: alphaLabel,
	supports: {
		features: alphaSettings.supports,
	},
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway);