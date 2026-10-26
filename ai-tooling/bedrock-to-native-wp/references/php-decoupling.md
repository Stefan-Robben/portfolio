# PHP decoupling: webreactor/framework → native WordPress

The proprietary stack's coupling is small and fully enumerable — grep confirms it
(`grep -rniE 'webreactor|illuminate' includes config functions.php`). `illuminate/*`
is only a transitive dependency of the framework/Blade; removing the framework
removes it with no code change.

## Mapping table (what each piece becomes)

| Proprietary thing | Native replacement |
|---|---|
| Composer PSR-4 autoload + `autoload.files` | `spl_autoload_register` + explicit `require_once` in `functions.php` |
| `new Webreactor\Framework\Providers\AppServiceProvider()` in `setup.php` | plain `add_action`/`add_filter`/`add_shortcode` wiring in `setup.php` |
| `config/hooks.php` `actions` + `Framework\Hooks\Actions\Action` base | `add_action('init', [Class::class, 'register'])`; class is plain with a static `register()` |
| `Framework\Hooks\Filters\Admin\LoadACFJson` (in `config/hooks.php` `filters`) | native `acf/settings/load_json` + `acf/settings/save_json` filters |
| `config/providers.php` → `Webreactor\Acf\AcfServiceProvider` → `config/acf_options.php` | `add_action('acf/init', …)` looping `config/acf_options.php` into `acf_add_options_page()` |
| `config/vc-elements.php` + `Webreactor\VCElements\Element`/`BladeElement` | `vc_map()` (editor) + `add_shortcode()` (front-end render) |
| `Webreactor\VCElements\FieldManager` param constants | WPBakery `vc_map` param arrays (mapping below) |
| Blade view returned by `Element::view()` | `ob_start()` + `include` a plain PHP partial, return the buffer |

Delete `config/providers.php`, `config/hooks.php`, `config/vc-elements.php` (their
job is now inline wiring). Keep `config/acf_options.php` — it's already a plain
`acf_add_options_page()` argument array. Delete the theme's `composer.json` +
`composer.lock` (removes the private Satis repo and all `webreactor/*` + `illuminate/*`).
`includes/helpers.php` (often just a `namespace` stub) stays — the autoloader requires it.

## functions.php — autoloader + bootstrap

```php
<?php

/**
 * Theme bootstrap (native, Composer-free).
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/includes/';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = $baseDir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_readable($file)) {
        require $file;
    }
});

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/enqueue.php';
require_once __DIR__ . '/includes/Admin/setup.php';
```

Keep class/file casing exactly as-is (Linux is case-sensitive). If the source uses a
lowercase class like `App\Elements\employees` mapping to `includes/Elements/employees.php`,
leave both lowercase.

## includes/Admin/setup.php — native kernel

`setup.php` lives in `includes/Admin/`, so `__DIR__` is that dir, `dirname(__DIR__)`
is `includes/`, `dirname(__DIR__, 2)` is the theme root.

```php
<?php

namespace App\Admin;

use App\Elements\employees;
use App\Hooks\Actions\RegisterTeamCategoryTax;
use App\Hooks\Actions\RegisterTeamPostType;

add_action('init', [RegisterTeamPostType::class, 'register']);
add_action('init', [RegisterTeamCategoryTax::class, 'register']);

add_filter('acf/settings/load_json', static function (array $paths): array {
    $paths[] = __DIR__ . '/acf-json';
    return $paths;
});
add_filter('acf/settings/save_json', static function (): string {
    return __DIR__ . '/acf-json';
});

add_action('acf/init', static function (): void {
    if (!function_exists('acf_add_options_page')) {
        return;
    }
    foreach ((array) require dirname(__DIR__, 2) . '/config/acf_options.php' as $page) {
        acf_add_options_page($page);
    }
});

add_action('vc_before_init', [employees::class, 'register']);
add_shortcode(employees::BASE, [employees::class, 'render']);
```

## Hook classes (CPT / taxonomy)

Strip the framework: delete `use …\Framework\Hooks\Actions\Action;`, `extends Action`,
and `hook()`; rename `action()` → `public static function register()`. Keep the
constant (`POST_TYPE` / `TEAM_CATEGORY_TAX`) and the exact `$labels`/`$args`, then call
`register_post_type(self::POST_TYPE, $args)` / `register_taxonomy(self::TAX, [PostType::POST_TYPE], $args)`.

## WPBakery element (vc_map + shortcode)

`FieldManager` constants → WPBakery param keys:
`PARAM_TYPE_HEADING`→`heading`, `PARAM_TYPE_TYPE`→`type`, `PARAM_TYPE_NAME`→`param_name`,
`PARAM_TYPE_DESCRIPTION`→`description`, `PARAM_TYPE_VALUE`→`value`, `PARAM_TYPE_STANDARD`→`std`;
`FIELD_TYPE_TEXT`→`'textfield'`, `FIELD_TYPE_SELECT`→`'dropdown'`.

Keep the original shortcode base string (e.g. `vc_element_output_employees`) so existing
page content still renders.

```php
<?php

namespace App\Elements;

use App\Hooks\Actions\RegisterTeamCategoryTax;
use App\Hooks\Actions\RegisterTeamPostType;

class employees
{
    public const BASE = 'vc_element_output_employees';

    public static function register(): void
    {
        if (!function_exists('vc_map')) {
            return;
        }
        vc_map([
            'name' => __('Medewerkers', 'orthodontiepraktijken'),
            'base' => self::BASE,
            'category' => __('Content', 'orthodontiepraktijken'),
            'params' => self::params(),
        ]);
    }

    public static function render($atts): string
    {
        $atts = shortcode_atts(['title' => '', 'team' => ''], $atts, self::BASE);
        $title = $atts['title'];
        $employees = self::getEmployees($atts);   // whatever query the original ran

        ob_start();
        include dirname(__DIR__, 2) . '/resources/views/employees.php';
        return (string) ob_get_clean();
    }

    public static function params(): array
    {
        return [
            ['type' => 'textfield', 'heading' => __('Titel', '...'), 'param_name' => 'title', 'description' => __('…', '...')],
            ['type' => 'dropdown', 'heading' => __('Teams', '...'), 'param_name' => 'team', 'value' => self::getTeams(), 'std' => '', 'description' => __('…', '...')],
        ];
    }
    // getEmployees()/getTeams(): copy verbatim — they already use get_posts()/get_terms() + the constants.
}
```

Notes: `shortcode_atts` casts `$atts` internally, so a bare shortcode (`$atts === ''`) is
handled — pass `$atts` straight through. Watch for a dead `use SomeVendor\Enums\…;` import
in the original element and drop it; also fix any stray wrong text-domain in labels.

## Blade view → plain PHP partial

`resources/views/<name>.php`, receiving the render()-scope locals directly (PHP `include`
shares scope — no `extract` needed):

```php
<?php
/** @var string $title @var \WP_Post[] $employees */
if (!defined('ABSPATH')) { exit; } ?>
<section class="employees">
    <h2 class="employees__title"><?= $title ?></h2>
    <div class="employees__grid">
        <?php foreach ($employees as $employee) : ?>
            <article class="employees__item">
                <?= get_the_post_thumbnail($employee) ?>
                <div class="employees__meta">
                    <p class="employees__name"><?= get_the_title($employee) ?></p>
                    <p class="employees__job"><?= get_field('job_title', $employee) ?></p>
                </div>
                <a class="employees__link" href="<?= get_permalink($employee) ?>"></a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
```

Replace the original Tailwind utility classes on the markup with **semantic component
classes** backed by hand-written CSS (see `css-conversion.md`) — since the view is being
rewritten anyway, this is free. Keep `{!! !!}` → `<?= ?>` (raw) to preserve identical
output; only add `esc_url()`/`esc_html()` if the user opts into hardening. Delete the old
`.blade.php` file and the empty Blade view scaffolding.

## wp-config reference (docs/wp-config.sample.php)

Bedrock's `config/application.php` is deleted, so capture its still-relevant constants as a
committed sample inside the theme (`docs/wp-config.sample.php`), then each site hand-writes
its own `wp-config.php`. Carry over: DB + `$table_prefix`, the 8 salts, `WP_HOME`/`WP_SITEURL`
(drop `CONTENT_DIR`/`WP_CONTENT_DIR` — native `/wp-content`), SpinupWP compat
(`WP_REDIS_DISABLED`, `DISABLE_WP_CRON`), hardening (`AUTOMATIC_UPDATER_DISABLED`,
`DISALLOW_FILE_EDIT`, `DISALLOW_FILE_MODS`, `WP_POST_REVISIONS`), plugin licenses, the
`HTTP_X_FORWARDED_PROTO` reverse-proxy HTTPS block, and per-env debug flags. End with
`if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/'); require_once ABSPATH . 'wp-settings.php';`.

**Two latent bugs to fix while transcribing** (they recur in this boilerplate):
`ACF_PRO_LICENSE` read `env('ACF_LICENCE')` while `.env` defined `ACF_LICENSE` (never applied);
and `env('WP_ROCKET_EMAIL' ?? '')` / `env('WP_ROCKET_KEY' ?? '')` put `?? ''` *inside* the
`env()` argument (a no-op).
