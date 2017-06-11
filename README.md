# PHPCI Plugin Codeception

This PHPCI plugin is an alternative plugin for intergartion Codeception.
It supports Codeception version 2.3.

Since Codeceptions directory structure changed the PHPCI default plugin isn't able to read the generated report files.
 
This plugin also makes it possible to run suites using multiple commands for testing different environments.

You may configure it in you project configuration or phpci.yml as followed

```yaml
  \Phizzl\PHPCI\Plugins\Codeception\CodeceptionPlugin:
    suites:
      acceptance:
        - { args: "--env productive,chrome -g mytests" }
        - { args: "--env productive,firefox" }
      unit:
        - { config: "codeception_alternative.yml" }
```
