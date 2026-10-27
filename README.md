# External Links

A dependency-free WordPress plugin that makes external links in post content and comments open safely in a new tab.

## Features

- Adds `target="_blank"` + `rel="noopener noreferrer"` to external links (protects against tab-nabbing)
- Relative links, same-domain links and subdomains are never touched
- Existing `target`/`rel` attributes are respected, never clobbered
- Optional `nofollow` and an external-link icon (CSS, theme-overridable)
- Per-host exclusions, one per line, plus the `wel_excluded_hosts` filter
- Render-time only: deactivating restores the original markup

## Settings

**Settings → External Links**: nofollow toggle, icon toggle, excluded hosts.

## Install

Upload the plugin folder to `wp-content/plugins/` and activate it.

## Uninstall

Removes the single `wel_settings` option.

## License

MIT
