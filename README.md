# CakeDC Datatables plugin for CakePHP

## IMPORTANT: This plugin is under heavy development now, use it at your own risk.

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org).

The recommended way to install composer packages is:

```shell
composer require cakedc/cakephp-datatables
```

### Load plugin
# To bake datatable index pages.

```shell
bin/cake plugin load CakeDC/Datatables
```

# To bake datatable index pages.
```shell
bin/cake bake all Articles -t CakeDC/Datatables
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

### Add postLink and confirmation message
Add the `type => "POST"` to the link, and the message in the `confirm` option

```php
<?= $this->Datatable->setFields([
    [
        'name' => 'action',
        'links' => [
            [
                'url' => ['action' => 'delete', 'extra' => ("/' + obj.id + '")],
                'label' => 'delete record',
                'type' => \CakeDC\Datatables\Datatables::LINK_TYPE_POST,
                'confirm' => __('Are you sure you want to delete this item?'),
            ],
        ]
    ],
]); ?>
```

NOTE: For now postLink does not support SecurityComponent, it is recommended to disable the method to use in the controller


#### Change method for confirmation message
The condition for the confirmation message is a javascript closure that receives the message and returns a boolean value.
```php
<?= $this->Datatable->setFields([
    [
        'name' => 'action',
        'links' => [
            [
                'url' => ['action' => 'delete', 'extra' => ("/' + obj.id + '")],
                'label' => 'delete record',
                'type' => \CakeDC\Datatables\Datatables::LINK_TYPE_POST,
                'confirm' => __('Are you sure you want to delete this item?'),
                'confirmCondition' => 'function (message){ return window.confirm(message); }',
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

# Types of inputs to search in colunms:
Now have 4 types of inputs:
    input
    select
    select multiple
    date

to define the type of search need in definition of columns especificate this array:
```php
'searchInput' => [
            'type' => '{{any-type}}',
            'options' => [
                    ['id' => 1, 'name' => 'one'],
            ],
        ],
```

### for input type text:
not need make anything is for default:

### for type select:
```php
'searchInput' => [
    type => 'select',
    'options' => [
        ['id' => 1, 'name' => 'one'],
        ['id' => 2, 'name' => 'two'],
        ....
    ]
],
```

### for type multiple:
```php
'searchInput' => [
    type => 'multiple',
    'options' => [
        ['id' => 1, 'name' => 'one'],
        ['id' => 2, 'name' => 'two'],
        ....
    ]
],
```
### for type date:
need jquery-ui or jquery-datepicker
```php
'searchInput' => [
    type => 'date',
    'options' => [],
]
```

it is to integrate for columns definition:

ejample:
```php

<?= $this->Datatable->setFields([
    [
        'name' => 'user_id',
        'searchInput' => [
            'type' => 'select',
            'options' => [
                ['id' => 1, 'name' => 'one'],
                ['id' => 2, 'name' => 'two'],
                ....
            ]
        ],
        'render' => '
            function(data, type) {
                return Math.floor(Math.random() * 1000);
            }
        '
    ]
]); ?>
```
in options is posible utilize find('list) and put names id and name.
if not want to search column is necesary to especify this,

```php 'searchable' => false, ```

# getting the datatable input delay 2 second:
```php
<?php $this->Datatable->setConfigKey('delay', 2000, true); ?>
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

# Create multiple datatables in the same template
Resets the datatable instance, and then you can set up a new one
```php
<?= $this->Datatable->reset() ?>
```
# Indicate a specific url to obtain the data
For example if you are in /pages/index but you et date from /pages/list, is usefull when you have multiple tables in the same page
```php
<?= $this->Datatable->getInstance()->setConfig('ajaxUrl', ['controller' => 'Pages', 'action' => 'list']); ?>
```
# Indicate specific type for ajax call
You can specify on config "ajaxType" if would that ajax calls are GET or POST, for example
```php
<?php
$this->Datatable->getInstance()->setConfig('ajaxType', 'POST');
$this->Datatable->getInstance()->setConfig('csrfToken', $this->getRequest()->getAttribute("csrfToken"));
?>
```
Important: if you set POST you must set "unlockedActions" on Security Component, specify the target action in the controller's initialize function
```php
public function initialize(): void
{
    parent::initialize();

    ...

    if ($this->components()->has('Security')) {
        $this->Security->setConfig('unlockedActions', ['list']);
    }

    ...

}
```

# Add callback when row is created

For example, if you need to add a css class (to change row color) to each tr according to a specific value in the data

```php
$this->Datatable->setCallbackCreatedRow(
    'function( row, data, dataIndex ) {
        if (!data.viewed) {
            $(row).addClass("rowhighlight");
        }
    }'
);
```

# Change library of multiselect

The default is jquery-ui multiselect, if you want to change this you can change it in the configuration or like the code example below

```php
$this->Datatable->getInstance()->setConfig('multiSelectType', 'select2');
```
