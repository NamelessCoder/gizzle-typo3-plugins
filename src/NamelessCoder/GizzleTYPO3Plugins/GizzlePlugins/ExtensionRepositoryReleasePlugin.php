<?php
namespace NamelessCoder\GizzleTYPO3Plugins\GizzlePlugins;

use NamelessCoder\Gizzle\AbstractPlugin;
use NamelessCoder\Gizzle\Payload;
use NamelessCoder\Gizzle\PluginInterface;
use NamelessCoder\TYPO3RepositoryClient\ExtensionUploadPacker;
use NamelessCoder\TYPO3RepositoryClient\Connection;
use NamelessCoder\TYPO3RepositoryClient\Uploader;

/**
 * Class ExtensionRepositoryReleasePlugin
 */
class ExtensionRepositoryReleasePlugin extends AbstractPlugin implements PluginInterface {

	const OPTION_DIRECTORY = 'directory';
	const OPTION_COMMENT = 'comment';
	const OPTION_BRANCH = 'branch';
	const OPTION_URL = 'url';
	const DEFAULT_COMMENT = 'Automatic release built from Github branch %s. See %s for change log.';
	const CREDENTIALS_FILE = '.typo3credentials';

	/**
	 * Upload the extension contained in repository to
	 * the official TYPO3 extension repository using
	 * credentials stored in the .typo3credentials file
	 * as a "username:password" value.
	 *
	 * @param Payload $payload
	 * @return void
	 * @throws \RuntimeException
	 */
	public function process(Payload $payload) {
		// validation: credentials file and local directory path.
		$credentialsFile = $this->getSettingValue(self::OPTION_CREDENTIALSFILE, GIZZLE_HOME . self::CREDENTIALS_FILE);
		$this->validateCredentialsFile($credentialsFile);
		$directory = $this->getSettingValue(self::OPTION_DIRECTORY);
		$this->validateDirectory($directory);
		// additional settings not requiring validation.
		$branch = $this->getSettingValue(self::OPTION_BRANCH, $payload->getRepository()->getMasterBranch());
		$url = $this->getSettingValue(self::OPTION_URL, $payload->getRepository()->getUrl());
		$comment = $this->getSettingValue(self::OPTION_COMMENT, self::DEFAULT_COMMENT);
		$comment = sprintf($comment, $branch, $url);
		// a large, properly formatted data file.
		list ($username, $password) = $this->readUploadCredentials();
		$output = $this->getUploader()->upload($directory, $username, $password, $comment);
		$payload->getResponse()->addOutputFromPlugin($this, $output);
	}

	/**
	 * @param $directory
	 * @throws \RuntimeException
	 */
	protected function validateDirectory($directory) {
		$directoryName = pathinfo($directory, PATHINFO_FILENAME);
		$matches = array();
		if (0 < preg_match('/[^a-z0-9_]/', $directoryName, $matches)) {
			throw new \RuntimeException('Directory "' . $directoryName . '" has a name indicating it is not a TYPO3 extension. ' .
				'Expected folder name should contain only a-z, 0-9 and underscores.', 1412208381);
		}
		if (FALSE === is_dir($directory)) {
			throw new \RuntimeException('Directory "' . $directory . '" does not exist; cannot upload', 1412208391);
		}
	}

	/**
	 * @param string $credentialsFile
	 * @throws \RuntimeException
	 */
	protected function validateCredentialsFile($credentialsFile) {
		if (FALSE === file_exists($credentialsFile)) {
			throw new \RuntimeException('Required TYPO3 TER upload credentials file "' . $credentialsFile . '" not found');
		}
	}

	/**
	 * @param string $credentialsFile
	 * @return array
	 */
	protected function readUploadCredentials($credentialsFile) {
		return explode(':', trim(file_get_contents($credentialsFile)));
	}

	/**
	 * @return Uploader
	 */
	protected function getUploader() {
		return new Uploader();
	}

}
