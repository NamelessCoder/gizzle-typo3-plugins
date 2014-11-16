Gizzle TYPO3 Plugins
====================

[![Build Status](https://travis-ci.org/NamelessCoder/gizzle-typo3-plugins.svg?branch=master)](https://travis-ci.org/NamelessCoder/gizzle-typo3-plugins) [![Coverage Status](https://img.shields.io/coveralls/NamelessCoder/gizzle-typo3-plugins.svg)](https://coveralls.io/r/NamelessCoder/gizzle-typo3-plugins)

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
    branch: master
    comment: A comment which supports %s to insert branch name and another %s to insert a changelog URL.
    url: http://my-custom-url.foo/if-not-set-then-github-repository-page.html
    removeBuild: true

```

TYPO3 Extension Repository Credentials
--------------------------------------

The `ExtensionRepositoryReleasePlugin` requires a special `.typo3credentials` file placed alongside your `.secret` file (in the
root project folder, not inside the `web/` directory) or in the location you configured in the plugin's options. The contents of
this file must be `username:password` where the `username` must be your typo3.org username and `password` of course your
typo3.org password, in cleartext. Since this file is sensitive information please make sure you don't commit it to Github and
inadvertently leak your login. Treat it with the same or greater care and respect that you would your `.secret` file!
