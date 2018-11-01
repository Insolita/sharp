<?php

namespace Code16\Sharp\Http\Composers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class MenuViewComposer
{

    /**
     * Build the menu and bind it to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $menuItems = new Collection;

        if(config("sharp.menu")) {
            foreach (config("sharp.menu") as $menuItemConfig) {
                $type = $menuItemConfig['type'];
                if ($type == 'category') {
                    $menuItem = new MenuCategory($menuItemConfig);
                    $menuItem->type = $type;
                } else {
                    $menuItem = new MenuEntity($menuItemConfig['entity'], $menuItemConfig['properties']);
                    $menuItem->type = $type;
                }

                if(($type == 'category' && sizeof($menuItem->entities)) || $type == 'page' || $type == 'external') {
                    $menuItems->push($menuItem);
                }
            }
        }

        $sharpMenu = [
            "name" => config("sharp.name", "Sharp"),
            "user" => sharp_user()->{config("sharp.auth.display_attribute", "name")},
            "dashboard" => $this->hasDashboard(),
            "menuItems" => $menuItems,
            "currentEntity" => isset($view->entityKey) ? explode(':', $view->entityKey)[0] : null
        ];

        $view->with('sharpMenu', (object)$sharpMenu);
    }

    /**
     * @return bool
     */
    private function hasDashboard()
    {
        return !!config("sharp.dashboard", false);
    }
}

class MenuCategory 
{
    /** @var string */
    public $label;

    /** @var array */
    public $entities = [];

    public function __construct(array $category)
    {
        $this->label = $category["label"] ?? "Unnamed category";

        if (isset($category['entities'])) {
            foreach ((array)$category["entities"] as $entityKey => $entity) {
                if (sharp_has_ability("entity", $entityKey)) {
                    $this->entities[] = new MenuEntity($entityKey, $entity);
                }
            }
        }
    }
}

class MenuEntity
{
    /** @var string */
    public $key;

    /** @var string */
    public $label;

    /** @var string */
    public $icon;

    /** @var string */
    public $url;

    public function __construct(string $key, array $entity)
    {
        $this->key = $key;
        $this->label = $entity["label"] ?? "Unnamed entity";
        $this->icon = $entity["icon"] ?? null;
        $this->url = $entity["url"] ?? null;
    }
}