<?php
namespace NamelessCoder\GizzleTYPO3Plugins\GizzlePlugins;

use NamelessCoder\Gizzle\Payload;
use NamelessCoder\Gizzle\PluginInterface;

/**
 * Class ExtensionRepositoryReleasePlugin
 */
class ExtensionRepositoryReleasePlugin implements PluginInterface {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Initialize the plugin with an array of settings.
	 *
	 * @param array $settings
	 * @return void
	 */
	public function initialize(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Analyse $payload and return TRUE if this plugin should
	 * be triggered in processing the payload.
	 *
	 * @param Payload $payload
	 * @return boolean
	 */
	public function trigger(Payload $payload) {

	}

	/**
	 * Perform whichever task the Plugin should perform based
	 * on the payload's data.
	 *
	 * @param Payload $payload
	 * @return void
	 */
	public function process(Payload $payload) {

	}

}
