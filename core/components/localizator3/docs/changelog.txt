# Changelog

All notable changes to Localizator3 are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

---

## [1.1.0-beta] - 2026-11-13

### Added
- PrimeVue theme via VueTools (`@vuetools/useTheme`): always ModxManagerTheme (`getActiveTheme('modx')`). New `vueManager/src/app/primevueTheme.js` with `getManagerPrimeVueConfig()` and `getPrimarySaveSeverity()` (always `success` for Save/Add). Localizator3 ignores the VueTools `vuetools.theme` / Aura setting.
- Lexicon keys `localizator_content_active` / `localizator_content_active_desc` for the "Publish translation" control on the Localization tab.

### Removed
- Unused Vue components `ContentFormDialog.vue` and `LanguageFormDialog.vue` (forms live in `ContentGrid` / `LanguagesGrid`).

### Changed
- Vue manager grids use shared composables (`useConnector`, `useLexicon`, `useDataTable`, `useGridCrud`, `useConfirmAction`). Content localization logic lives in `useContentForm.js` so `ContentGrid.vue` / `LanguagesGrid.vue` stay under the 400-line gate.
- Manager Vue UI chrome matches MODX / VueTools: green `#6cb24a`, 13px type, 32px controls, resource-like form tabs, flat panels. Languages page paints `#modx-content`; the resource Localization tab does not hide the host ExtJS panel.
- Languages page layout: page title, compact stats strip, search without a redundant label (16rem), primary action on the right.
- Localization toolbar: constrained language select, publish checkbox next to it (description in `title`), Save uses theme severity, Translate is outlined secondary.
- `createLocalizatorApp` uses ModxManagerTheme; theme options use `darkModeSelector: '.modx-dark-mode'` and `cssLayer: false`.
- `resolver_11_upgrade.php` no longer bulk-enables every TV on install. New TVs stay off until `localizator3_enabled` is set on the TV form. The unused `localizator3_tv_fields` system setting is removed if present.
- Localization tab: the language comes only from the toolbar select. The form `key` field is hidden (#19). The "Publish translation" checkbox sits in that toolbar and is checked when the value is `1`.
- `richtext` TVs use the MODX editor (`MODx.loadRTE` or Ace), the same editor as the resource form. The TV description sits next to the label. Placeholders that repeated the field caption are removed.

### Fixed
- Nested MIGX auto-translate: `configs` / `formtabs` from a child field are written to the nested TV `input_properties` before the recursive `translateTV()` call. Sibling fields no longer inherit a polluted parent `$params` bag. Nested migx via `inputTVtype` + config name (or inline formtabs) is translated, not only `inputTV` references.
- TV participation in localization: new TVs default to off (`localizator3_enabled=0`). The localization tab shows and saves only TVs enabled on the TV form. The installer used to enable every TV.
- MODX 3 `modTemplateVar` xPDO map merge: the class key is `MODX\Revolution\modTemplateVar`, not the legacy `modTemplateVar` (`OnMODXInit` and the resolver). The whole map is written back, because nested `$modx->map[$class][$key]` does not persist in MODX 3 (xPDOMap returns a copy). The `localizator3_enabled` column is added on install and upgrade.
- The "TV is available in localizations" checkbox is saved. `OnTVFormSave` stores `0` when the box is cleared. ExtJS does not post `0`, so without the handler a TV could not be turned off.
- `getformconfig`, `localizatorContent`, `fields`, and `pdofetchlocalizator3` check the physical column, not only the map. Installations without the column keep the previous behavior and do not throw SQL errors.
- `localizatorContent::renderTVOutput()`: the missing `localizator_is_mod_template_var()` call is replaced with `instanceof modTemplateVar`. Localized TV output no longer fatals.
- Languages grid "Active" column: `getlist` returns a strict `bool`. The UI coerces `0/1/'0'/'1'` to `bool` in the grid, the toggle, and the stats. Stats count every language, ignoring the search filter. Status tags use Active / Disabled lexicon keys instead of Enable / Disable action labels.
- Content form tabs sit flush with the panel top (PrimeVue `.p-tablist` padding removed).

---

## [1.0.12-beta] - 2026-11-06

### Fixed
- TV fields on the Localization tab render by type. `number`, `listbox`, `listbox-multiple`, `checkbox`, `radio`, and similar types use PrimeVue controls. MIGX, tvmedia3x, and other custom types use the MODX renderer. Every TV used to be a plain textarea.
- Custom TV values (MIGX, tvmedia3x, and others) are collected and saved per localization. Generated field ids do not collide with TVs on the main resource form.

---

## [1.0.11-beta] - 2026-10-07

### Added
- Setting `localizator3_request_language_param` (default `language`): a GET/POST parameter with an explicit language key. It outranks the `localizator3_key` cookie in connector/AJAX requests and in auto-detect redirects. An empty value disables the override. Example: `?language=ru` while the cookie is `en`.
- `localizator::getRequestLanguageKey()` and `applyLanguageFromKey()` read that parameter and sync `cultureKey` / `localizator3_key`.

### Changed
- `resolveConnectorLanguage()` order: request parameter, Referer path, HTTP host, cookie.
- `applyLanguageFromCookie()` delegates to `applyLanguageFromKey()`.

---

## [1.0.10-beta] - 2026-07-17

### Added
- `localizator::applyLanguage()`, `applyLanguageFromCookie()`, and `resolveConnectorLanguage()` sync the language outside a full page request.

### Fixed
- Connector / AJAX: `OnMODXInit` calls `resolveConnectorLanguage()` (Referer path or the `localizator3_key` cookie). Language used to be set only when `X-Requested-With: XMLHttpRequest` and a Referer were both present, so package `connector.php` calls kept the system `cultureKey` (often `ru`) while the storefront was already `en`.

### Changed
- Language application moved out of `findLocalization()` into `applyLanguage()` so other callers can reuse it.

---

## [1.0.9-beta] - 2026-07-07

### Added
- Setting `localizator3_default_from_resource`: default-language content (`localizator3_default_language`) comes from the resource fields and TVs. The Localization tab lists translations only.
- `localizator::shouldUseResourceFields()`, `getLocalizedFieldValue()`, `buildDefaultLanguageSource()`, and `localizatorContent::hydrateFromResource()`.

### Fixed
- PHP 8: `findLocalization()` no longer passes `$request` by reference. `OnHandleRequest` and `OnMODXInit` pass the request path through a variable. This removes the frontend fatal "could not be passed by reference".
- Install on PHP 8.2: Symfony 7 instead of 8 in vendor, platform PHP 8.2, and a corrected `platform_check.php` (#8).
- VueTools resolver: `modTransportProvider::request()` is called with the MODX 3 signature, through a shared helper. A VueTools failure does not abort the Localizator3 install (#8).
- Phinx migrations: MODX `table_prefix` is no longer applied twice. The migration log table is `{table_prefix}localizator3_migrations`. A repair runs before Phinx on installations that were already broken (#9).
- Package uninstall: the `localizator3` plugin and its events are removed by a resolver and transport attributes (`UNINSTALL_OBJECT`) (#10).

---

## [1.0.8-beta] - 2026-06-17

### Fixed
- Resource Localization tab: Vue mount race. The bridge does not set `data-mounted` until `app.mount()`, and retries while the ES module is still loading.
- Mount recovery: a stuck `data-mounted` flag without `.content-grid` is cleared. The tab `activate` handler and `doLayout` run after mount.
- Localization tab: language select and an inline Document / TV form replace the grid list. Save stays on the tab.
- Locale dropdown: every active Localizator3 language. Switching loads an existing translation or starts a new one for that key.
- Full width of the localization tab: ExtJS `layout: anchor` and `anchor: 100%`, plus CSS from `#localizator3-resource-tab` to `#localizator3-content-app`.
- CSS: `index.min.css` renamed to `content.min.css` in `OnDocFormPrerender.php`.
- Build: `languages.min.js` is emitted.
- Vite: `inlineDynamicImports: true`. Each entry is one file, with no code-split chunks.

### Added
- VueTools ≥1.1.2-pl: Vue 3, Pinia, and PrimeVue 4 through an Import Map.
- `vuetools` resolver: checks for VueTools and installs it.
- Trait `Localizator3VueControllerTrait` with `requireVueTools()` and `addVueModule()`.
- Composables: `useConnector`, `useLexicon`, `useDataTable`, `useGridCrud`, `useConfirmAction`.
- Shared components: `FormFieldRenderer.vue`, `GridActionsColumn.vue`.
- Dialog components: `ContentFormDialog.vue`, `LanguageFormDialog.vue`.
- Lexicon entry `localizator3_vuetools_required` when VueTools is missing.

### Changed
- Docs: one `docs/` tree (kebab-case), index in `docs/README.md`, Vue UI and getformconfig brought up to date.
- Vite `external` for `vue`, `pinia`, `primevue`, `@vuetools/*`. Bundles are about 14 KB instead of about 800 KB.
- `createLocalizatorApp.js`: one Vue bootstrap without Pinia (stores are unused).
- ExtJS fallback for the grids is removed. The manager UI is Vue and requires VueTools.

---

## [1.0.7-beta] - 2026-06-17

### Added
- Vue UI: scoped styles with BEM prefixes (`.content-grid__*`, `.languages-grid__*`).
- Vue UI: PrimeVue `ConfirmDialog` instead of native `confirm()`.
- Vue UI: `appendTo="self"` on Dialog so portals stay out of ExtJS styles.

### Changed
- CSS: PrimeVue's global `box-sizing` is overridden with `!important` in `main.css`.
- Legacy ExtJS styles removed from `main.css`.
- Duplicate PrimeVue imports removed from `ContentGrid.vue`.
- Docs: README.md, `docs/*`, `llms.txt`, `readme.txt`.
- Build is documented as `php _build/build.php`, with asset paths under `vue-dist`.
- Package version synced in `_build/config.inc.php`.
- Cursor rules updated for Localizator3.

### Fixed
- Docs: the language switcher snippet is `getLanguageList` (it was documented as `getLocales`).

---

## [1.0.6-beta] - 2026-06-17

### Added
- Lexicons for Ukrainian (uk), Kazakh (kk), Uzbek (uz), and Azerbaijani (az).
- Vue localization tab on the resource form (`ContentGrid.vue`).
- Processor `mgr/content/getformconfig` for the form config, including customization.
- Event `OnBuildLocalizationTabs` with `resource`, `user`, and `customization`.
- Per-user field customization: `visible` and `rank`.
- mSearch integration: localized fields are indexed (`mseOnBeforeIndex`, `mseOnGetWorkFields`). Field names look like `ru-pagetitle` and `en-description` (see `docs/integration-msearch.md`).
- miniShop3 product option localization (color, size, and similar options).
- Tables `localizator3_option` (`locOption`) and `localizator3_product_option` (`locProductOption`).
- Fenom modifiers: `locoptioncaption`, `locproductoptionvalue`.
- Service methods: `getLocalizedOptionCaption`, `getLocalizedOptionDescription`, `getLocalizedProductOptionValue`.

---

## [1.0.5-beta] - 2026-06-17

### Removed
- mSearch2 / mFilter2 support. Use [mFilter](https://docs.modx.pro/components/mfilter/) for faceted filtering.

---

## [1.0.4-beta] - 2026-06-17

### Added
- miniShop3 integration: a Localizator tab for `msProduct`, and `getLocalizedResources` with `msProducts` / `ms3Products`.

---

## [1.0.3-beta] - 2026-06-17

### Added
- Snippet `getLocalizedField`: output a resource field in a given language.
- CLI script `translate_resources.php` for bulk translation from the console.
- Setting `localizator3_auto_detect_language`: detect the visitor language from Accept-Language or the cookie.

---

## [1.0.2-beta] - 2026-06-17

### Added
- Event `OnToggleLocalizatorLanguage` when the language is resolved (validation, cache, integrations).
- Snippet `getLocalizedSitemap`: `sitemap.xml` with hreflang for multilingual pages.

---

## [1.0.1-beta] - 2026-06-17

### Added
- `onlyWithLocalization` on `getLocalizedResources`: drop resources that have no localization for the current language (INNER JOIN).
- Snippet `makeLocalizedUrl`: resource URL with a language prefix.
- `getLanguageList` extended with `outputMode` (list/dropdown), `showActive`, and `activeClass`. Chunk `languages.dropdown.tpl`.
- Setting `localizator3_404_if_no_localization`: 404 for documents without a localization. Event `OnHasLocalizatorError404`.
- `rank` on `localizator3_languages`. `getLanguageList` accepts `sortby` and `sortdir`.
- Snippet `getLocalizedCanonical`: canonical and hreflang.

---

## [1.0.0-beta] - 2026-06-17

### Added
- First release, MODX 3 only.
- Fork of Localizator adapted for MODX Revolution 3.
- Separate package name (`localizator3`) and tables (`localizator3_*`), so it does not clash with the original Localizator.
- MODX 3 namespaces (`\MODX\Revolution\*`, `\xPDO\Transport\*`).
- MODX 3 only, with no MODX 2 compatibility layer.
