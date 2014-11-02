<?php
namespace NamelessCoder\GizzleTYPO3Plugins\Tests\Unit\GizzlePlugins;

use NamelessCoder\Gizzle\Commit;
use NamelessCoder\Gizzle\Repository;
use NamelessCoder\GizzleGitPlugins\GizzlePlugins\ClonePlugin;
use NamelessCoder\GizzleTYPO3Plugins\GizzlePlugins\ExtensionRepositoryReleasePlugin;
use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

/**
 * Class ExtensionRepositoryReleasePluginTest
 */
class ExtensionRepositoryReleasePluginTest extends \PHPUnit_Framework_TestCase {

	public static function setUpBeforeClass() {
		define('GIZZLE_HOME', '.');
	}

	public function testReadCredentialsFile() {
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('temp', 0777));
		$file = vfsStream::url('temp/typo3credentials');
		file_put_contents($file, 'username:password');
		$plugin = new ExtensionRepositoryReleasePlugin();
		$method = new \ReflectionMethod($plugin, 'readUploadCredentials');
		$method->setAccessible(TRUE);
		$result = $method->invoke($plugin, $file);
		$this->assertEquals(array('username', 'password'), $result);
	}

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

	public function testGetGitClonePluginReturnsClonePluginInstance() {
		$plugin = new ExtensionRepositoryReleasePlugin();
		$method = new \ReflectionMethod($plugin, 'getGitClonePlugin');
		$method->setAccessible(TRUE);
		$result = $method->invoke($plugin);
		$this->assertInstanceOf('NamelessCoder\\GizzleGitPlugins\\GizzlePlugins\\ClonePlugin', $result);
	}

	public function testProcess() {
		vfsStreamWrapper::register();
		vfsStreamWrapper::setRoot(new vfsStreamDirectory('temp', 0777));
		$settings = array(
			ExtensionRepositoryReleasePlugin::OPTION_DIRECTORY => vfsStream::url('temp'),
			ExtensionRepositoryReleasePlugin::OPTION_URL => 'url',
			ExtensionRepositoryReleasePlugin::OPTION_BRANCH => 'master',
			ExtensionRepositoryReleasePlugin::OPTION_COMMENT => 'comment',
			ExtensionRepositoryReleasePlugin::OPTION_REMOVEBUILD => TRUE
		);
		$plugin = $this->getMock(
			'NamelessCoder\\GizzleTYPO3Plugins\\GizzlePlugins\\ExtensionRepositoryReleasePlugin',
			array('validateCredentialsFile', 'getUploader', 'readUploadCredentials', 'getGitClonePlugin')
		);
		$payload = $this->getMock(
			'NamelessCoder\\Gizzle\\Payload',
			array('getRepository', 'getHead', 'getResponse', 'getRef'),
			array(), '', FALSE
		);
		$repository = new Repository();
		$repository->setMasterBranch('master');
		$repository->setName('repository');
		$repository->setUrl($settings[ExtensionRepositoryReleasePlugin::OPTION_URL]);
		$head = new Commit();
		$head->setId('123');
		$clone = $this->getMock('NamelessCoder\\GizzleGitPlugins\\GizzlePlugins\\ClonePlugin', array('initialize', 'process'));
		$clone->expects($this->once())->method('initialize')->with(array(
			ClonePlugin::OPTION_DIRECTORY => $settings[ExtensionRepositoryReleasePlugin::OPTION_DIRECTORY] . '/123/repository',
			ClonePlugin::OPTION_BRANCH => '1.1.1',
			ClonePlugin::OPTION_DEPTH => 1,
			ClonePlugin::OPTION_SINGLE => TRUE
		));
		$clone->expects($this->once())->method('process')->with($payload);
		$response = $this->getMock('NamelessCoder\\Gizzle\\Response', array('addOutputFromPlugin'));
		$response->expects($this->once())->method('addOutputFromPlugin')->with($plugin, array());
		$uploader = $this->getMock('NamelessCoder\\TYPO3RepositoryClient\\Uploader', array('upload'));
		$uploader->expects($this->once())->method('upload')->with(
			$settings[ExtensionRepositoryReleasePlugin::OPTION_DIRECTORY] . '/' . $head->getId() . '/' . $repository->getName(),
			'username',
			'password',
			$settings[ExtensionRepositoryReleasePlugin::OPTION_COMMENT]
		)->will($this->returnValue(array()));
		$payload->expects($this->any())->method('getRepository')->will($this->returnValue($repository));
		$payload->expects($this->any())->method('getResponse')->will($this->returnValue($response));
		$payload->expects($this->any())->method('getHead')->will($this->returnValue($head));
		$payload->expects($this->any())->method('getRef')->will($this->returnValue('refs/tags/1.1.1'));
		$plugin->expects($this->once())->method('getGitClonePlugin')->will($this->returnValue($clone));
		$plugin->expects($this->once())->method('validateCredentialsFile');
		$plugin->expects($this->once())->method('getUploader')->will($this->returnValue($uploader));
		$plugin->expects($this->once())->method('readUploadCredentials')->will($this->returnValue(array('username', 'password')));
		$plugin->initialize($settings);
		$plugin->process($payload);
	}

	/**
	 * @param $branch
	 * @param $payloadRef
	 * @param $expected
	 * @dataProvider getTriggerValues
	 */
	public function testTrigger($branch, $payloadRef, $expected) {
		$payload = $this->getMock('NamelessCoder\\Gizzle\\Payload', array('getRepository', 'getRef'), array(), '', FALSE);
		$repository = new Repository();
		$repository->setMasterBranch('master');
		$payload->expects($this->any())->method('getRepository')->will($this->returnValue($repository));
		$payload->expects($this->any())->method('getRef')->will($this->returnValue($payloadRef));
		$plugin = new ExtensionRepositoryReleasePlugin();
		$plugin->initialize(array(
			ExtensionRepositoryReleasePlugin::OPTION_BRANCH => $branch
		));
		$this->assertEquals($expected, $plugin->trigger($payload));
	}

	/**
	 * @return array
	 */
	public function getTriggerValues() {
		return array(
			array('master', 'refs/tags/1.1.1', TRUE),
			array('master', 'refs/heads/development', FALSE),
			array('development', 'refs/tags/1.1.3', TRUE)
		);
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
