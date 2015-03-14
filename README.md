Gizzle TYPO3 Plugins
====================

[![Build Status](https://img.shields.io/jenkins/s/https/jenkins.fluidtypo3.org/gizzle-typo3-plugins.svg?style=flat-square)](https://jenkins.fluidtypo3.org/job/gizzle-typo3-plugins/) [![Coverage Status](https://img.shields.io/coveralls/NamelessCoder/gizzle-typo3-plugins.svg?style=flat-square)](https://coveralls.io/r/NamelessCoder/gizzle-typo3-plugins) [![Latest Stable Version](https://img.shields.io/packagist/v/NamelessCoder/gizzle-typo3-plugins.svg?style=flat-square)](https://packagist.org/packages/namelesscoder/gizzle-typo3-plugins) [![Total Downloads](https://img.shields.io/packagist/dt/NamelessCoder/gizzle-typo3-plugins.svg?style=flat-square)](https://packagist.org/packages/namelesscoder/gizzle-typo3-plugins)

Plugins for [Gizzle](https://github.com/NamelessCoder/gizzle) to perform various tasks related to the TYPO3 software family.

Settings
--------

The following `Settings.yml` file shows every possible setting for every plugin in this collection with sample values.

```yaml
NamelessCoder\GizzleTYPO3Plugins:
  NamelessCoder\GizzleTYPO3Plugins\GizzlePlugins\ExtensionRepositoryReleasePlugin:
    enabled: true
    directory: /working/directory/path/
    credentialsFile: /path/to/.typo3credentials/if/not/in/project/root
    comment: A short comment text describing the upload. Taken from Payload HEAD's message body if not configured.
    url: http://my-custom-url.foo/if-not-set-then-github-repository-page.html
    removeBuild: true
    gitCommand: `which git`
    extensionKey: optional_underscored_extensionkey

```

Note that the plugin supports sub-plugin settings for `NamelessCoder\GizzleGitPlugins\GizzlePlugins\ClonePlugin` that can be
used to override any defaults that `ExtensionRepositoryReleasePlugin` will use. See Gizzle documentation about sub-plugins.

The `extensionKey` parameter is specially supported as a `$_GET` parameter. If specified, `$_GET['extensionKey']` will overrule
any extension key defined in settings. If neither `$_GET` nor the settings file contain an extension key, the plugin will attempt
to use the repository's name as extension key. **If your extension key is different from the repository name you must always
provide the extension key in URL or settings**.

TYPO3 Extension Repository Credentials
--------------------------------------

The `ExtensionRepositoryReleasePlugin` requires a special `.typo3credentials` file placed alongside your `.secret` file (in the
root project folder, not inside the `web/` directory) or in the location you configured in the plugin's options. The contents of
this file must be `username:password` where the `username` must be your typo3.org username and `password` of course your
typo3.org password, in cleartext. Since this file is sensitive information please make sure you don't commit it to Github and
inadvertently leak your login. Treat it with the same or greater care and respect that you would your `.secret` file!
