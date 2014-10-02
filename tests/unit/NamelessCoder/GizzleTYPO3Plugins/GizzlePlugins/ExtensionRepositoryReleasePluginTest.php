<?php
namespace NamelessCoder\GizzleTYPO3Plugins\Tests\Unit\GizzlePlugins;
use NamelessCoder\GizzleTYPO3Plugins\GizzlePlugins\ExtensionRepositoryReleasePlugin;

/**
 * Class ExtensionRepositoryReleasePluginTest
 */
class ExtensionRepositoryReleasePluginTest extends \PHPUnit_Framework_TestCase {

	public function testGetUploaderReturnsUploader() {
		$plugin = new ExtensionRepositoryReleasePlugin();
		$method = new \ReflectionMethod($plugin, 'getUploader');
		$method->setAccessible(TRUE);
		$result = $method->invoke($plugin);
		$this->assertInstanceof('NamelessCoder\\TYPO3RepositoryClient\\Uploader', $result);
	}

	public function testValidateDirectoryThrowsExceptionOnMissingDirectory() {
		$plugin = new ExtensionRepositoryReleasePlugin();
		$method = new \ReflectionMethod($plugin, 'validateDirectory');
		$method->setAccessible(TRUE);
		$this->setExpectedException('RuntimeException');
		$method->invoke($plugin, 'directorydoesnotexist');
	}

	/**
	 * @dataProvider getInvalidDirectoryNames
	 * @param string $directory
	 * @param integer $expectedCode
	 */
	public function testValidateDirectoryThrowsExceptionOnInvalidCharactersOrMissingDirectory($directory, $expectedCode) {
		$plugin = new ExtensionRepositoryReleasePlugin();
		$method = new \ReflectionMethod($plugin, 'validateDirectory');
		$method->setAccessible(TRUE);
		$this->setExpectedException('RuntimeException', NULL, $expectedCode);
		$method->invoke($plugin, $directory);
	}

	/**
	 * @dataProvider getValidDirectoryNames
	 * @param string $directory
	 */
	public function testValidateDirectoryDoesNotThrowsExceptionOnValidDirectoryNames($directory) {
		$plugin = new ExtensionRepositoryReleasePlugin();
		$method = new \ReflectionMethod($plugin, 'validateDirectory');
		$method->setAccessible(TRUE);
		$method->invoke($plugin, $directory);
	}

	public function testValidateCredentialsFileThrowsExceptionOnMissingFile() {
		$plugin = new ExtensionRepositoryReleasePlugin();
		$method = new \ReflectionMethod($plugin, 'validateCredentialsFile');
		$method->setAccessible(TRUE);
		$this->setExpectedException('RuntimeException');
		$method->invoke($plugin, 'filedoesnotexist');
	}

	public function testValidateCredentialsFileDoesNotThrowExceptionIfFileExists() {
		$plugin = new ExtensionRepositoryReleasePlugin();
		$method = new \ReflectionMethod($plugin, 'validateCredentialsFile');
		$method->setAccessible(TRUE);
		$method->invoke($plugin, __FILE__);
	}

	/**
	 * @return array
	 */
	public function getInvalidDirectoryNames() {
		return array(
			// invalid names
			array('/tmp/-directory', 1412208381),
			array('/tmp/invalid-directory', 1412208381),
			array('/tmp/åname', 1412208381),
			array('/tmp/name space', 1412208381),
			array('/tmp/NameCaps', 1412208381),
			// non-existing but valid names
			array('/tmp/has/nice/folder', 1412208391),
			array('/tmp/extension_key', 1412208391),
			array('/tmp/extension_key_123', 1412208391)
		);
	}

	/**
	 * @return array
	 */
	public function getValidDirectoryNames() {
		return array(
			array('/tmp/'),
			array('/home/'),
			array('/etc/'),
		);
	}

}
