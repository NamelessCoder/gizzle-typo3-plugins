<?php
namespace NamelessCoder\GizzleTYPO3Plugins\GizzlePlugins;

use NamelessCoder\Gizzle\PluginListInterface;

/**
 * Class PluginList
 */
class PluginList implements PluginListInterface {

	/**
	 * Get all class names of plugins delivered from implementer package.
	 *
	 * @return string[]
	 */
	public function getPluginClassNames() {
		return array(
			'NamelessCoder\\GizzleTYPO3Plugins\\GizzlePlugins\\ExtensionRepositoryReleasePlugin'
		);
	}

}
