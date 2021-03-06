<?php

namespace Code16\Sharp\Tests\Unit\Components;

use Code16\Sharp\Tests\Fixtures\User;
use Code16\Sharp\Tests\SharpTestCase;
use Code16\Sharp\View\Components\Menu;

class MenuComponentTest extends SharpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(new User);
    }

    /** @test */
    function we_can_define_an_external_url_in_the_menu()
    {
        $this->app['config']->set(
            'sharp.menu', [
                [
                    "label" => "external",
                    "icon" => "fa-globe",
                    "url" => "https://google.com"
                ]
            ]
        );
        
        $menu = app(Menu::class);

        $this->assertArraySubset(
            [
                "label" => "external",
                "icon" => "fa-globe",
                "url" => "https://google.com",
                "type" => "url"
            ], 
            (array)$menu->getItems()[0]
        );
    }

    /** @test */
    function we_can_define_a_direct_entity_link_in_the_menu()
    {
        $this->app['config']->set(
            'sharp.menu', [
                [
                    "label" => "people",
                    "icon" => "fa-user",
                    "entity" => "person"
                ]
            ]
        );

        $menu = app(Menu::class);

        $this->assertArraySubset(
            [
                "key" => "person",
                "label" => "people",
                "icon" => "fa-user",
                "type" => "entity",
                "url" => route("code16.sharp.list", "person"),
            ], 
            (array)$menu->getItems()[0]
        );
    }

    /** @test */
    function we_can_define_a_category_in_the_menu()
    {
        $this->app['config']->set(
            'sharp.menu', [
                [
                    "label" => "Data",
                    "entities" => [
                        [
                            "label" => "people",
                            "icon" => "fa-user",
                            "entity" => "person"
                        ]
                    ]
                ]
            ]
        );

        $menu = app(Menu::class);

        $this->assertEquals("Data", $menu->getItems()[0]->label);
        $this->assertEquals("category", $menu->getItems()[0]->type);

        $this->assertArraySubset(
            [
                "key" => "person",
                "label" => "people",
                "icon" => "fa-user",
                "type" => "entity",
                "url" => route("code16.sharp.list", "person"),
            ], 
            (array)$menu->getItems()[0]->entities[0]
        );
    }

    /** @test */
    function we_can_define_a_dashboard_in_the_menu()
    {
        $this->app['config']->set(
            'sharp.menu', [
                [
                    "label" => "My Dashboard",
                    "icon" => "fa-dashboard",
                    "dashboard" => "personal_dashboard"
                ]
            ]
        );

        $menu = app(Menu::class);

        $this->assertArraySubset(
            [
                "key" => "personal_dashboard",
                "label" => "My Dashboard",
                "icon" => "fa-dashboard",
                "type" => "dashboard",
                "url" => route("code16.sharp.dashboard", "personal_dashboard"),
            ], 
            (array)$menu->getItems()[0]
        );
    }

    /** @test */
    function we_can_define_a_single_show_entity_link_in_the_menu()
    {
        $this->app['config']->set(
            'sharp.menu', [
                [
                    "label" => "people",
                    "icon" => "fa-user",
                    "entity" => "person",
                    "single" => true
                ]
            ]
        );

        $menu = app(Menu::class);

        $this->assertArraySubset(
            [
                "key" => "person",
                "label" => "people",
                "icon" => "fa-user",
                "type" => "entity",
                "url" => route("code16.sharp.single-show", "person"),
            ], 
            (array)$menu->getItems()[0]
        );
    }
}