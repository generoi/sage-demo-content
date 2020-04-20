<?php

namespace Genero\Sage\DemoContent;

use Roots\Acorn\ServiceProvider;
use GFForms;
use GFAPI;
use GFExport;
use GFCommon;

class DemoContentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../publishes/resources' => $this->app->resourcePath('demo'),
        ], 'Demo Content');

        $this->gutenberg();
        $this->gravityform();
    }

    /**
     * Change the default content of the Gutenberg demo menu callback.
     */
    public function gutenberg(): void
    {
        add_filter('default_content', function ($content) {
            if (isset($_GET['gutenberg-demo'])) {
                if ($file = $this->getFilePath('gutenberg.html')) {
                    $content = file_get_contents($file);
                }
            }
            return $content;
        }, 11);
    }

    /**
     * Add a Gravity For m demo content.
     */
    public function gravityform(): void
    {
        if (!class_exists('GFForms')) {
            return;
        }

        add_action('admin_menu', [$this, 'gformAddSubmenuEntry'], 11);
    }

    /**
     * Add a menu entry for creating a demo Gravity Form.
     */
    public function gformAddSubmenuEntry(): void
    {
        $parent = GFForms::get_parent_menu(apply_filters('gform_addon_navigation', []));
        add_submenu_page(
            $parent['name'],
            __('Import Demo Form', 'sage'),
            __('Import Demo Form', 'sage'),
            'gform_full_access',
            'sage_gform_demo',
            function () {
                $form = $this->getDemoGravityForm();
                if (!$form) {
                    require_once GFCommon::get_base_path() . '/export.php';
                    if ($file = $this->getFilePath('gravityforms.json')) {
                        GFExport::import_file($file);
                        $form = $this->getDemoGravityForm();
                    }
                }

                if ($form) {
                    wp_redirect(admin_url('admin.php?page=gf_edit_forms&id=' . $form['id']));
                    exit;
                }

                wp_die('Unable to import demo form', 'Import failed');
            }
        );
    }

    /**
     * Return the path to a file with demo content if it exists.
     */
    protected function getFilePath(string $path): ?string
    {
        if (file_exists($file = $this->app->resourcePath("demo/$path"))) {
            return $file;
        }
        if (file_exists($file = __DIR__ . "/../publishes/resources/$path")) {
            return $file;
        }
        return null;
    }

    /**
     * Return the demo Gravity Form as an array if it exists.
     */
    protected function getDemoGravityForm(): ?array
    {
        return collect(GFAPI::get_forms())
            ->filter(function ($form) {
                return $form['title'] === 'Demo';
            })
            ->first();
    }
}
