# Copilot instructions for BellePoule / Cotcot display

Purpose: give an AI coding agent the minimal, actionable context to make safe, high-value edits in this repo.

- Entry point: `index.php` — loads a `.cotcot` XML (DOMDocument) and renders pages based on `?file=` (or `?cotcot=`) and `?item=`.
  - Supported `item` values: `menu`, `lst`, `pou`, `poudet`, `clapou`, `clatab`, `tab`, `finalcla`, `flag`.
  - Example: `http://localhost/index.php?file=Fleuret-test.cotcot&item=lst`

- Where data lives: XML competition files live in `cotcot/` (default) or the path set by `$COTCOT_DIRECTORY` in `config.php`.
  - Use `functions.php::getCotcotDirectory()` when resolving file paths.
  - File extension check enforces `\.cotcot$` in `index.php`.

- Configuration: `config.php` contains most runtime knobs:
  - `$COTCOT_DIRECTORY` — path to .cotcot files (relative or absolute).
  - `$AUTO_REFRESH_INTERVAL` (ms) — centralized auto-refresh used by client JS.
  - `$BURST_*`, `$SCROLL_DELAY` — parameters used by autoscroll logic.
  - `$FOOTER_ENABLED`, `$FOOTER_TEXT`, `$FOOTER_HEIGHT` — footer rendering.
  - Toggle debug logging with `$DEBUG_MODE` in `config.php` and `$debug_mode` in `error_handler.php`.

- Frontend / client responsibilities:
  - `js/scroll-refresh.js` manages autoscroll lifecycle (globals: `isAutoScrolling`, `startAutoScroll`, `scrollTimeout`).
  - `js/functions.js` contains helpers used by the page scripts.
  - `js/bracket-lines.js` draws bracket connectors for `tab` pages.
  - `js/nouislider.js` implements zoom/table sliders used by `index.php`.
  - Cookies used by client: `zoom_{pageKey}`, `tabStart`, `tabEnd`, `scrollSpeed` (agent edits should preserve names).

- Server-side patterns and helpers (look here first):
  - `functions.php` — many rendering helpers: `renderCompetitionHeader()`, `renderFinalClassement()`, `getTireurList()`, `getTireurRankingList()`, `formatWeapon()`, `IE()`.
  - `pays.php` — country/flag helper used by renderers (see `flag_icon()` usage).
  - `my6.php`, `my_phase_pointage.php`, `selcot.php` contain domain-specific rendering and parsing; index.php wires them together.

- Error handling & logs:
  - `error_handler.php` registers a custom handler and writes to `error.log` in the project root.
  - For debugging: enable `$debug_mode = true` to get verbose HTML traces; also set `$DEBUG_MODE = true` in `config.php` for extra debugLog output.

- Important integrations & runtime requirements:
  - PHP must have the DOM extension available (DOMDocument, libxml) — XML parsing is core to functionality.
  - No external package manager or build step is used. Static assets are under `css/`, `js/`, `svg/`, `logo/`.

- Conventions and gotchas agents must follow:
  - Preserve existing query parameter names and cookie keys (changing them breaks client behavior).
  - `.cotcot` files are authoritative; most rendering reads attributes like `Classement`, `REF`, `RangInitial` — modify rendering only if you understand the XML shape.
  - `config.php` path resolution uses `realpath()` fallback — when changing paths, validate with `getCotcotDirectory()`.
  - Avoid introducing meta refresh tags — the project uses centralized JS timers (`AUTO_REFRESH_INTERVAL`) instead.

- Quick local run (Windows / PowerShell):
  - Prefer XAMPP: place project under `c:\xampp\htdocs` and start Apache.
  - Or use PHP built-in server for quick testing from PowerShell:
    ```powershell
    php -S localhost:80 -t c:\xampp\htdocs
    # then open http://localhost:80/index.php?file=Fleuret-test.cotcot&item=lst
    ```

If anything above is unclear or you want more examples (XML shape, specific helper walkthroughs, or where to add tests), tell me which area to expand and I'll iterate.
