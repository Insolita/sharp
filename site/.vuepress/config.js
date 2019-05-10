module.exports = {
    title: 'Sharp',
    themeConfig: {
        nav: [
            { text: 'Home', link: '/' },
            { text: 'Documentation', link: '/docs/' },
        ],
        sidebar: {
            '/docs/': [
                'authentication',
                {
                    title: 'Entity Lists',
                    collapsable: false,
                    children: [
                        'building-entity-list',
                        'filters',
                        'commands',
                        'entity-states',
                        'reordering-instances',
                    ]
                },
                {
                    title: 'Entity Forms',
                    collapsable: false,
                    children: [
                        'building-entity-form',
                        ...[
                            'text',
                            'textarea',
                            'markdown',
                            'wysiwyg',
                            'number',
                            'html',
                            'check',
                            'date',
                            'upload',
                            'select',
                            'autocomplete',
                            'tags',
                            'list',
                            'autocomplete-list',
                            'geolocation'
                        ].map(page => `form-fields/${page}`),
                        'entity-authorizations',
                        'multiforms',
                        'custom-form-fields'
                    ]
                },
                {
                    title: 'Dashboard',
                    collapsable: false,
                    children: [
                        'dashboard'
                    ],
                },
                {
                    title: 'Generalities',
                    collapsable: false,
                    children: [
                        'building-menu',
                        'how-to-transform-data',
                        'context',
                        'sharp-built-in-solution-for-uploads',
                        'form-data-localization',
                        'testing-with-sharp',
                        'artisan-generators'
                    ]
                },
                'style-visual-theme'
            ],
        },
    }
};