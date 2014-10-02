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
