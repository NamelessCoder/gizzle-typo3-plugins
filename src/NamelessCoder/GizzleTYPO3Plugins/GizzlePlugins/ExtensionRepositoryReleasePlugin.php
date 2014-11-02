<?php
namespace NamelessCoder\GizzleTYPO3Plugins\GizzlePlugins;

use NamelessCoder\Gizzle\AbstractPlugin;
use NamelessCoder\Gizzle\Payload;
use NamelessCoder\Gizzle\PluginInterface;
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
	const OPTION_BRANCH = 'branch';
	const OPTION_URL = 'url';
	const OPTION_REMOVEBUILD = 'removeBuild';
	const DEFAULT_COMMENT = 'Automatic release built from Github branch %s. See %s for change log.';
	const CREDENTIALS_FILE = '.typo3credentials';

	/**
	 * @param Payload $payload
	 * @return boolean
	 */
	public function trigger(Payload $payload) {
		return 0 === strpos($payload->getRef(), 'refs/tags/');
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
		$tag = substr($payload->getRef(), 10);

		// additional settings not requiring validation.
		$branch = $this->getSettingValue(self::OPTION_BRANCH, $payload->getRepository()->getMasterBranch());
		$url = $this->getSettingValue(self::OPTION_URL, $payload->getRepository()->getUrl());
		$comment = $this->getSettingValue(self::OPTION_COMMENT, self::DEFAULT_COMMENT);

		// validation: credentials file and local directory path.
		$directory = $this->getSettingValue(self::OPTION_DIRECTORY);
		$directory = rtrim($directory, '/') . '/';
		$directory .= $sha1 . '/' . $payload->getRepository()->getName();
		$credentialsFile = $this->getSettingValue(self::OPTION_CREDENTIALSFILE, GIZZLE_HOME . self::CREDENTIALS_FILE);
		$this->validateCredentialsFile($credentialsFile);
		list ($username, $password) = $this->readUploadCredentials($credentialsFile);

		// initializing build directory and cloning source
		$clone = $this->getGitClonePlugin();
		$clone->initialize(array(
			ClonePlugin::OPTION_DIRECTORY => $directory,
			ClonePlugin::OPTION_BRANCH => $tag,
			ClonePlugin::OPTION_DEPTH => 1,
			ClonePlugin::OPTION_SINGLE => TRUE
		));
		$this->createWorkingDirectory($directory);
		$this->validateDirectory($directory);
		$clone->process($payload);
		$comment = sprintf($comment, $tag, $url);

		// a large, properly formatted data file.
		$output = $this->getUploader()->upload($directory, $username, $password, $comment);

		// cleanup and messages
		if (TRUE === (boolean) $this->getSettingValue(self::OPTION_REMOVEBUILD, FALSE)) {
			$this->removeWorkingDirectory($this->getSettingValue(self::OPTION_DIRECTORY), $sha1);
		}
		$payload->getResponse()->addOutputFromPlugin($this, $output);
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
		mkdir($directory, 0755, TRUE);
	}

	/**
	 * @param string $directory
	 * @param string $sha1
	 */
	protected function removeWorkingDirectory($directory, $sha1) {
		system('rm -rf ' . escapeshellarg($directory . '/' . $sha1));
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
