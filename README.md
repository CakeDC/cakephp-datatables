# CakeDC Datatables plugin for CakePHP

## IMPORTANT: This plugin is under heavy development now, use it at your own risk.

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

```shell
composer require cakedc/cakephp-datatables
```

# To bake datatable index pages.
```shell
bin/cake bake all Articles --theme Cakedc-datatables
```

### To overwrite existing index pages.
```shell
bin/cake bake all Articles --theme Cakedc-datatables -f
```


# Setting up the datatable fields
You can set a simple array with the columns to print or a more complex one with render callbacks, or a mix of them.
### Simple entity visible fields
```php
<?= $this->Datatable->setFields($article->getVisible()) ?>
```

### Manual simple columns configuration
```php
<?= $this->Datatable->setFields(['id', 'title', 'user_id', 'user.name']); ?>
```

### Set complex columns configurations with render callback.
In this case print some random number.
```php
<?= $this->Datatable->setFields([
    [
        'name' => 'user_id',
        'render' => '
            function(data, type) {
                return Math.floor(Math.random() * 1000);
            }
        '
    ]
]); ?>
```

### Set complex columns configurations with render callback.
Print data from the record, hard coded values. Also add parameters to the link URL.
```php
<?= $this->Datatable->setFields([
    [
        'name' => 'title',
        'links' => [
            // Will produce a dynamic link with object data, i.e.
            // <a href="/articles/view/' + obj.id + '">hard coded</a>
            ['url' => ['action' => 'view', 'extra' => ("/' + obj.id + '")], 'label' => 'hard coded'],

            // Will produce a fixed link with a hard coded label, i.e.
            // <a href="/articles/view/d">hard coded</a>
            ['url' => ['action' => 'view', 'd'], 'label' => 'hard coded'],

            // Will produce a fixed link with a dynamic label, i.e.
            // <a href="/articles/edit">' + obj.user_id + '</a>
            ['url' => ['action' => 'edit'], 'value' => 'obj.user_id'],

            // Will produce a fixed link without an external URL in the href attribute, i.e.
            // <a href="#">' + obj.user_id + '</a>
            ['url' => '#', 'value' => 'obj.user_id'],
        ]
    ],
]); ?>
```

### Add conditions to disable links

Add the disable option in the link, with a javascript closure that returns a boolean value, true for show value without the link, and false to return it with the link, this function receives the value of the current column and the row object.

```php
<?= $this->Datatable->setFields([
    [
        'name' => 'title',
        'links' => [
            [
                'url' => ['action' => 'view', 'extra' => ("/' + obj.id + '")],
                'label' => 'hard coded',
                'disable' => 'function (value) { 
                    return value === "N/A"
                }',
            ],
            [
                'url' => ['action' => 'view', 'd'],
                'label' => 'hard coded'
                'disable' => 'function (value, obj) { 
                    return obj.status === "inactive"
                }',
            ],
        ]
    ],
]); ?>
```

### A mix of simple and complex columns conditions
```php
$this->Datatable->setFields(
    [
        'id',
        [
            'name' => 'title',
            'links' => [
                ['url' => ['action' => 'view', 'extra' => ("/' + obj.id + '")], 'label' => 'hard coded'],
                ['url' => ['action' => 'view', 'd'], 'label' => 'hard coded'],
                ['url' => ['action' => 'edit'], 'value' => 'obj.user_id'],
                ['url' => '#', 'value' => 'obj.user_id'],
            ]
        ],
        [
            'name' => 'user_id',
            'render' => '
                function(data, type) {
                    return Math.floor(Math.random() * 1000);
                }
            '
        ],
        'user.name'
    ]
);

$this->Datatable->getDatatableScript("table-articles");
```

Will produce the following script.
```javascript
    // API callback
    let getData = async () => {
        let res = await fetch('/articles.json')
    }

    // Datatables configuration
    $(() => {
        $('#table-articles').DataTable({
            ajax: getData(),
            processing: true,
            serverSide: true,
            columns: [
                {data:'id'},
                {
                    data:'title',
                    render: function(data, type, obj) {
                        return '<a href="/articles/view/' + obj.id + '">hard coded</a>'
                            + '<a href="/articles/view/d">hard coded</a>'
                            + '<a href="/articles/edit">' + obj.user_id + '</a>'
                            + '<a href="#">' + obj.user_id + '</a>'
                    }
                },
                {
                    data:'user_id',
                    render: function(data, type) {
                        return Math.floor(Math.random() * 1000);
                    }
                },
                {data:'user.name'}
            ]
        });
    });
```


# Getting the datatable script.
All you need is to tell the help to create the script for you, pass the tag id to be used for
the datatable.
```php
<?= $this->Datatable->getDatatableScript("table-articles") ?>
```

## Setting the table headers.
Optionally you can format and translate the table header as follows:
```php
<?= $this->Datatable->getTableHeaders($article->getVisible(), true) ?>
```
