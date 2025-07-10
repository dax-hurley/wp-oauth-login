<?php
/**
 * Interface Module.
 *
 * Every module inside src/Modules/ should implement
 * to this interface.
 *
 * @package RtCamp\OAuthLogin
 * @since 1.0.0
 */

namespace RtCamp\OAuthLogin\Interfaces;

/**
 * Interface Module
 *
 * @package WpGuruDev\OrderExport
 */
interface Module {

	/**
	 * Initialization of module.
	 *
	 * @return void
	 */
	public function init(): void;

	/**
	 * Return module name.
	 *
	 * @return string
	 */
	public function name(): string;
}
