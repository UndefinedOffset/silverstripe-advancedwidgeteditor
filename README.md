Advanced Widget Editor
=================
[![Build Status](https://travis-ci.org/UndefinedOffset/silverstripe-advancedwidgeteditor.png)](https://travis-ci.org/UndefinedOffset/silverstripe-advancedwidgeteditor)

Replaces the Widget Editor to enable support for advanced form fields such as `SilverStripe\AssetAdmin\Forms\UploadField`

## Requirements
* SilverStripe 4.6+
* [SilverStripe Widgets ~2.0](https://github.com/silverstripe/silverstripe-widgets/)

## Installation
```
composer require undefinedoffset/silverstripe-advancedwidgeteditor
```

## Usage
To use advanced widget editor over the default SilverStripe widgets editor you need to change your extension from WidgetPageExtension to use AdvancedWidgetPageExtension instead.

```yml
Page:
  extensions:
    - 'UndefinedOffset\AdvancedWidgetEditor\Extensions\AdvancedWidgetPageExtension'
```

## Controlling Available Widgets per-class
To control widgets on a per-class host class (class which the Advanced Widget Editor is controlling the widget area for), you can use the config property available_widgets for example:
```yml
Page:
    available_widgets:
        - "RSSWidget"
        - "MyExampleWidget"

SilverStripe\Blog\Model\Blog:
    available_widgets:
        - 'SilverStripe\Blog\Widgets\BlogArchiveWidget'
        - 'SilverStripe\Blog\Widgets\BlogCategoriesWidget'
        - 'SilverStripe\Blog\Widgets\BlogTagsCloudWidget'
        - 'SilverStripe\Blog\Widgets\BlogRecentPostsWidget'
```

In the case of the above example the `Page` class and it's decedent classes will have the `RSSWidget` and `MyExampleWidget` available. Where the `SilverStripe\Blog\Model\Blog` class and it's decendent classes will also have ArchiveWidget, BlogManagementWidget, etc along side the `RSSWidget` and `MyExampleWidget`.

You can also restrict widgets from decedent classes using the example above say we want to restrict the `SilverStripe\Blog\Model\Blog` class to not have access to the `RSSWidget` but still have `Page` be allowed to use that widget, for example you can use the bellow:
```yml
SilverStripe\Blog\Model\Blog:
    restricted_widgets:
        - "RSSWidget"
```

## Reporting an issue
When you're reporting an issue please ensure you specify what version of SilverStripe you are using. Also be sure to include any JavaScript or PHP errors you receive, for PHP errors please ensure you include the full stack trace. Also please include how you produced the issue. You may also be asked to provide some of the classes to aid in re-producing the issue. Stick with the issue, remember that you seen the issue not the maintainer of the module so it may take allot of questions to arrive at a fix or answer.
