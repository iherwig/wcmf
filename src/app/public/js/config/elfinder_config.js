/**
 * Configuration for elFinder
 */
elfinderConfig = {
    width: '782',
    height: '609',
    cssClass: 'wcmfFinder',
    rememberLastDir: true,
    resizable: false,
    commands: [
        'open', 'reload', 'home', 'up', 'back', 'forward', 'getfile', 'quicklook',
        'download', 'rm', 'duplicate', 'rename', 'mkdir', 'mkfile', 'upload', 'copy',
        'cut', 'paste', 'edit', 'extract', 'archive', 'search', 'info', 'view', 'help',
        'resize', 'crop', 'sort'
    ],
    ui: ['toolbar'/*, 'places'*/, 'tree', 'path', 'stat'],
    uiOptions: {
        toolbar: [
          ['back', 'forward'], /* ['reload'],*//* ['home', 'up'],*/['mkdir', 'mkfile', 'upload'],
          ['open', 'download', 'getfile'], ['info'], ['quicklook'], ['copy', 'cut', 'paste'],
          ['rm'], ['duplicate', 'rename', 'edit', 'resize', 'crop'], ['extract', 'archive'],
          ['search'], ['view'], ['help']
        ],
        tree: {
            openRootOnLoad: true,
            syncTree: true
        },
        navbar: {
            minWidth: 150,
            maxWidth: 500
        },
        cwd: {
            oldSchool: false
        }
    },
    contextmenu: {
        navbar: ['open', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'info'],
        cwd: ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'info'],
        files: [
            'getfile', '|', 'open', 'quicklook', '|', 'download', '|', 'copy', 'cut', 'paste', 'duplicate', '|',
            'rm', '|', 'edit', 'rename', 'resize', 'crop', '|', 'archive', 'extract', '|', 'info'
        ]
    }
};