<?php
namespace NamelessCoder\GizzleTYPO3Plugins\GizzlePlugins;

use NamelessCoder\Gizzle\PluginListInterface;

/**
 * Class PluginList
 */
class PluginList implements PluginListInterface {

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
	 * Get all class names of plugins delivered from implementer package.
	 *
	 * @return string[]
	 */
	public function getPluginClassNames() {
		$plugins = array();
		if (TRUE === $this->isEnabled('NamelessCoder\\GizzleTYPO3Plugins\\GizzlePlugins\\ExtensionRepositoryReleasePlugin')) {
			$plugins[] = 'NamelessCoder\\GizzleTYPO3Plugins\\GizzlePlugins\\ExtensionRepositoryReleasePlugin';
		}
		return $plugins;
	}

	/**
	 * @param string $class
	 * @return boolean
	 */
	protected function isEnabled($class) {
		$class = 'NamelessCoder\\GizzleTYPO3Plugins\\GizzlePlugins\\ExtensionRepositoryReleasePlugin';
		return (boolean) TRUE === isset($this->settings[$class]['enabled']) ? $this->settings[$class]['enabled'] : TRUE;
	}

}
