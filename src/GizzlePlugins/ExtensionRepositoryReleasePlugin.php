<?php
namespace NamelessCoder\GizzleTYPO3Plugins\GizzlePlugins;

use NamelessCoder\Gizzle\AbstractPlugin;
use NamelessCoder\Gizzle\Payload;
use NamelessCoder\Gizzle\PluginInterface;
use NamelessCoder\Gizzle\Repository;
use NamelessCoder\GizzleGitPlugins\GizzlePlugins\ClonePlugin;
use NamelessCoder\GizzleGitPlugins\GizzlePlugins\PullPlugin;
use NamelessCoder\TYPO3RepositoryClient\ExtensionUploadPacker;
use NamelessCoder\TYPO3RepositoryClient\Connection;
use NamelessCoder\TYPO3RepositoryClient\Uploader;

/**
 * Class ExtensionRepositoryReleasePlugin
 */
class ExtensionRepositoryReleasePlugin extends AbstractPlugin implements PluginInterface {

	const OPTION_CREDENTIALSFILE = 'credentialsFile';
	const OPTION_DIRECTORY = 'directory';
	const OPTION_COMMENT = 'comment';
	const OPTION_URL = 'url';
	const OPTION_REMOVEBUILD = 'removeBuild';
	const OPTION_GITCOMMAND = 'gitCommand';
	const OPTION_EXTENSIONKEY = 'extensionKey';
	const CREDENTIALS_FILE = '.typo3credentials';
	const PATTERN_EXTENSION_FOLDER = '/[^a-z0-9_]/';
	const PATTERN_TAG_HEAD = 'refs/tags/';
	const TEMPORARY_FOLDER_PERMISSIONS = 0755;

	/**
	 * @param Payload $payload
	 * @return boolean
	 */
	public function trigger(Payload $payload) {
		return 0 === strpos($payload->getRef(), self::PATTERN_TAG_HEAD);
	}

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
		$sha1 = $payload->getHead()->getId();
		$tag = substr($payload->getRef(), strlen(self::PATTERN_TAG_HEAD));
		$this->validateVersionNumber($tag);

		// additional settings not requiring validation.
		$url = $this->getSettingValue(self::OPTION_URL, $payload->getRepository()->resolveApiUrl(Repository::API_URL_CLONE));
		// look for an upload comment in settings; if not found there, use Payload HEAD's message body
		$comment = $this->getSettingValue(self::OPTION_COMMENT, $payload->getHead()->getMessage());

		// validation: credentials file and local directory path.
		$directory = $this->getSettingValue(self::OPTION_DIRECTORY);
		$directory = rtrim($directory, '/') . '/';
		$directory .= $sha1 . '/' . $this->getWorkingDirectoryName($payload);
		$credentialsFile = $this->getSettingValue(self::OPTION_CREDENTIALSFILE, GIZZLE_HOME . self::CREDENTIALS_FILE);
		$this->validateCredentialsFile($credentialsFile);
		list ($username, $password) = $this->readUploadCredentials($credentialsFile);

		// initializing build directory and cloning source
		$clone = $this->getGitClonePlugin();
		$clonePluginSettings = $this->getSubPluginSettings('\NamelessCoder\GizzleGitPlugins\GizzlePlugins\ClonePlugin', array(
			ClonePlugin::OPTION_GITCOMMAND => $this->getSettingValue(self::OPTION_GITCOMMAND, ClonePlugin::DEFAULT_GITCOMMAND),
			ClonePlugin::OPTION_DIRECTORY => $directory,
			ClonePlugin::OPTION_SINGLE => TRUE,
			ClonePlugin::OPTION_BRANCH => $tag,
			ClonePlugin::OPTION_DEPTH => 1
		));
		$clone->initialize($clonePluginSettings);
		$this->createWorkingDirectory($directory);
		$this->validateDirectory($directory);
		$clone->process($payload);

		// a large, properly formatted data file.
		$comment = sprintf($comment, $url);
		try {
			$output = $this->getUploader()->upload($directory, $username, $password, $comment);
		} catch (\SoapFault $error) {
			throw new \RuntimeException($error->getMessage(), $error->getCode());
		}

		// cleanup and messages
		if (TRUE === (boolean) $this->getSettingValue(self::OPTION_REMOVEBUILD, FALSE)) {
			$this->removeWorkingDirectory($this->getSettingValue(self::OPTION_DIRECTORY), $sha1);
		}
		$payload->getResponse()->addOutputFromPlugin($this, $output);
	}

	/**
	 * @param Payload $payload
	 * @return string
	 */
	protected function getWorkingDirectoryName(Payload $payload) {
		return $this->getSettingValue(self::OPTION_EXTENSIONKEY, $payload->getRepository()->getName());
	}

	/**
	 * @return ClonePlugin
	 */
	protected function getGitClonePlugin() {
		return new ClonePlugin();
	}

	/**
	 * @param string $directory
	 */
	protected function createWorkingDirectory($directory) {
		mkdir($directory, self::TEMPORARY_FOLDER_PERMISSIONS, TRUE);
	}

	/**
	 * @param string $directory
	 * @param string $sha1
	 */
	protected function removeWorkingDirectory($directory, $sha1) {
		system('rm -rf ' . escapeshellarg($directory . '/' . $sha1));
	}

	/**
	 * Validates that $version conforms to the expected TYPO3
	 * extension versioning scheme (major.minor.bugfix).
	 * Throws a RuntimeException if it doesn't.
	 *
	 * @param string $version
	 * @throws \RuntimeException
	 */
	protected function validateVersionNumber($version) {
		if (1 !== preg_match('/^[\\d]{1,2}\.[\\d]{1,2}\.[\\d]{1,2}$/i', $version)) {
			throw new \RuntimeException(
				'Invalid version number "' . $version . '" detected from tag, aborting upload',
				1426360822
			);
		}
	}

	/**
	 * @param $directory
	 * @throws \RuntimeException
	 */
	protected function validateDirectory($directory) {
		$directoryName = pathinfo($directory, PATHINFO_FILENAME);
		$matches = array();
		if (0 < preg_match(self::PATTERN_EXTENSION_FOLDER, $directoryName, $matches)) {
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
