# Wikitten

Wikitten is a small PHP wiki for notes, code snippets, and documentation. It serves Markdown and HTML pages from a folder on disk, with a searchable file tree and optional web editing.

## Features

- Browse Markdown and HTML notes and nested folders.
- Use the sidebar to search names, open folders, and expand or collapse their contents.
- Edit existing text files in the browser with CodeMirror.
- Create Markdown or HTML notes and folders from the web interface.
- Rename notes and folders. Deleting a folder removes its contents after a confirmation.
- Render Markdown Extra features, including fenced code blocks, tables, footnotes, and metadata.
- Add JSON front matter to Markdown pages for page titles, descriptions, tags, and author information.
- Choose a light or dark theme and optionally load a custom Markdown stylesheet.
- Optionally protect the wiki with the built-in username and password login.
- Optionally publish a page to Pastebin.

## Requirements

- PHP 8.5 with the Fileinfo extension.
- Apache with `mod_rewrite` enabled for the supplied rewrite configuration.
- The cURL extension only if Pastebin support is enabled.
- Write access to the library directory when web editing is enabled.

The application uses current PHP syntax and types, and the bundled Markdown renderer has been updated for PHP 8 string offsets, interpolation, and dynamic-property behavior. No Composer dependencies are required.

### Changes in this update

- Updated the Docker runtime from PHP 7.3 to PHP 8.5, replaced the legacy PHP configuration, and refreshed the Compose configuration.
- Modernized the PHP application and bundled Markdown renderer for current PHP syntax and removed deprecated string-offset, interpolation, and dynamic-property patterns.
- Hardened library path checks, HTML escaping, JSON front-matter decoding, and session authentication defaults.
- Updated Pastebin API request handling for current PHP cURL handles.
- Added browser-based creation, renaming, and deletion of notes and folders, plus folder navigation and an expandable sidebar tree.

## Run with Docker

Create `docker/.env`:

```dotenv
HTTP_PORT=8080
LIBRARY_FOLDER=../library
```

Then build and start Wikitten:

```shell
cd docker
docker compose up --build
```

Open `http://localhost:8080` (or the port selected by `HTTP_PORT`). The project is mounted into the container, and `LIBRARY_FOLDER` is mounted as the wiki library. To stop the container, run `docker compose down` from the `docker` directory.

## Run on Apache

Point an Apache virtual host or local development host at the repository directory. Enable `mod_rewrite`, ensure PHP 8.5 and Fileinfo are enabled, and make the library directory writable by the web server if you plan to edit it through Wikitten.

For Laragon, place the repository under the web root, enable Fileinfo in the active PHP configuration, then open the configured virtual host or project URL.

## Configure

Copy `config.php.example` to `config.php` in the repository root. Edit the constants there to customize the wiki. Common options:

| Option | Default | Purpose |
| --- | --- | --- |
| `APP_NAME` | `Wikitten` | Name shown in the page title and sidebar. |
| `DEFAULT_FILE` | `index.md` | Homepage note served at the wiki root when it exists. |
| `LIBRARY` | `library/` | Directory containing notes and folders. |
| `ENABLE_EDITING` | `false` | Enables note editing and all web file-management actions. |
| `USE_PAGE_METADATA` | `true` | Reads JSON front matter from Markdown notes. |
| `USE_DARK_THEME` | `false` | Enables the dark color theme. |
| `USE_WIKITTEN_LOGO` | `true` | Shows the Wikitten logo. |
| `ACCESS_USER`, `ACCESS_PASSWORD` | Disabled | Enables the built-in login when both are defined. |
| `ENABLE_PASTEBIN`, `PASTEBIN_API_KEY` | Disabled | Enables public Pastebin publishing for notes. |
| `INTERNAL_WIKI_LINK` | `true` | Enables internal wiki link handling. |
| `EXTERNAL_LINK_TARGET` | `_blank` | Default target for external links. |
| `CUSTOM_MARKDOWN_STYLESHEET` | Empty | Optional stylesheet filename under `static/css/custom-styles/`. |

Enable editing by uncommenting `ENABLE_EDITING` in `config.php`. If the wiki is reachable by other people, also set a private username and password with `ACCESS_USER` and `ACCESS_PASSWORD` before enabling edits. Web editing can create, change, rename, and permanently delete files in the library.

## Manage notes and folders

When editing is enabled, open a folder or note and expand **Manage notes and folders**. The folder page creates new items inside that folder; a note page creates new items beside that note. New notes default to Markdown and can be given a supported extension such as `.html`.

The sidebar folder name opens the folder page. Its chevron expands or collapses the nested tree. You can rename the current note or folder from its management panel. Deleting a folder deletes all of its contents, so the interface asks for confirmation first.

## Page metadata

With `USE_PAGE_METADATA` enabled, a Markdown page can start with JSON front matter between two `---` lines:

```markdown
---
{
  "title": "Project notes",
  "description": "Notes for the project",
  "tags": ["project", "notes"],
  "author": "Your name"
}
---

# Project notes
```

## Security and storage

Notes are ordinary files under `LIBRARY`, so they can also be edited or backed up with other tools. Web file changes are limited to paths inside the configured library, and editing and file-management forms use session tokens. Use the built-in login or restrict network access when enabling editing; the login is disabled unless both access constants are configured.

Pastebin publishing creates a public paste. Only enable it if that matches your expectations for the content being shared.

## More information

See [CHANGELOG.md](CHANGELOG.md) for the update history and [config.php.example](config.php.example) for the complete set of configuration options.
