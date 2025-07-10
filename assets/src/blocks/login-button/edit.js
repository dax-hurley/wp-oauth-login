/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * Editor styles for the block.
 */
import './editor.scss';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import {
	useBlockProps,
	InspectorControls,
	RichText,
} from '@wordpress/block-editor';
import { Panel, PanelBody, CheckboxControl } from '@wordpress/components';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @param {Object}   root0               - Object containing the block's attributes and functions.
 * @param {Object}   root0.attributes    - The block's attributes.
 * @param {Function} root0.setAttributes - Function to update the block's attributes.
 * @param {string}   root0.className     - The block's class name.
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes, className } ) {
	const { buttonText, forceDisplay } = attributes;

	const buttonTextAttributes = {
		format: 'string',
		className: 'oauth-login-button-text',
		onChange: ( value ) => {
			setAttributes( { buttonText: value } );
		},
		value: buttonText,
		placeholder: __( 'WP OAuth Login', 'login-with-oauth' ),
	};

	const forceDisplayAttributes = {
		label: __( 'Display Logout', 'login-with-oauth' ),
		help: __(
			'If the user is logged in, keeping this box unchecked will remove the WP OAuth Login button from the page. If the box is checked, the button will show with title changed to ‘Logout’',
			'login-with-oauth'
		),
		checked: forceDisplay,
		onChange: ( val ) => {
			setAttributes( { forceDisplay: val } );
		},
	};

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<Panel>
					<PanelBody title={ __( 'Settings', 'login-with-oauth' ) }>
						<CheckboxControl { ...forceDisplayAttributes } />
					</PanelBody>
				</Panel>
			</InspectorControls>
			<div className="wp_oauth_login__button-container">
				<span className="wp_oauth_login__button">
					<span className="wp_oauth_login__oauth-icon"> </span>
					<RichText { ...buttonTextAttributes } />
				</span>
			</div>
		</div>
	);
}
