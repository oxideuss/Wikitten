# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Upgrade the Docker runtime from PHP 7.3 to PHP 8.5.
- Replace the PHP 7.3 configuration with a compact PHP 8.5 configuration and safer session defaults.
- Modernize application PHP with typed properties, methods and class constants, current array and string syntax, and strict JSON decoding.
- Harden page creation and editing paths, session authentication, and HTML output.
- Update the Pastebin API endpoint and request handling for current PHP cURL handles.
- Update the Compose configuration to current syntax.
- Add web actions to create, rename and delete notes and folders when editing is enabled.

## [1.3.0] 2019-09-11

- Update markdown rendered library to 1.8.0
- Mode definitions to from index.php to default.php
- Implement custom markdown styles
- Refactor internal markdown links

## [1.2.0] 2019-08-27

- Merge of the [Basic login system](https://github.com/devaneando/Wikitten/pull/90) PR.

## [1.1.0] 2019-08-24

- Major refactor of the docker with upgrade to PHP 7.3.
- Merge of [Bootstrap 4 update](https://github.com/devaneando/Wikitten/pull/93) PR.

## [1.0.0] 2019-08-24

First stable version with the work of [Victor Stanciu](vic.stanciu@gmail.com).

Transfer of the repository to [Eduardo Fernandes](edu.fernandes.pt@gmail.com)

[Unreleased]: https://github.com/devaneando/Wikitten/compare/v1.3.0...HEAD
[1.3.0]: https://github.com/devaneando/Wikitten/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/devaneando/Wikitten/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/devaneando/Wikitten/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/devaneando/Wikitten/releases/tag/v1.0.0
