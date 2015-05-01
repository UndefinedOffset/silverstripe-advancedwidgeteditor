Advanced Widget Editor
=================
[![Build Status](https://travis-ci.org/UndefinedOffset/silverstripe-advancedwidgeteditor.png)](https://travis-ci.org/UndefinedOffset/silverstripe-advancedwidgeteditor)

Replaces the Widget Editor to enable support for advanced form fields such as UploadField

## Requirements
* SilverStripe 3.1.x
* [SilverStripe Widgets 1.0.x](https://github.com/silverstripe/silverstripe-widgets/)

## Installation
* Download the module from here https://github.com/UndefinedOffset/silverstripe-advancedwidgeteditor/archive/master.zip
* Extract the downloaded archive into your site root so that the destination folder is called advancedwidgeteditor, opening the extracted folder should contain _config.php in the root along with other files/folders
* Run dev/build?flush=all to regenerate the manifest

If you prefer you may also install using composer:
```
composer require undefinedoffset/silverstripe-advancedwidgeteditor
```

## Usage
To use advanced widget editor over the default SilverStripe widgets editor you need to change your extension from WidgetPageExtension to use AdvancedWidgetPageExtension instead.

```yml
Page:
  extensions:
    - 'AdvancedWidgetPageExtension'
```

## Controlling Available Widgets per-class
To control widgets on a per-class host class (class which the Advanced Widget Editor is controlling the widget area for), you can use the config property available_widgets for example:
```yml
Page:
    available_widgets:
        - "RSSWidget"
        - "MyExampleWidget"

BlogTree:
    available_widgets:
        - "ArchiveWidget"
        - "BlogManagementWidget"
        - "TagCloudWidget"
        - "SubscribeRSSWidget"
```

In the case of the above example the Page class and it's decedent classes will have the RSSWidget and MyExampleWidget available. Where the BlogTree class and it's decendent classes will also have ArchiveWidget, BlogManagementWidget, etc along side the RSSWidget and MyExampleWidget.

You can also restrict widgets from decedent classes using the example above say we want to restrict BlogHolder (a decendent of BlogTree) to not have access to the blog management widget but still have BlogTree be allowed to use that widget, for example you can use the bellow:
```yml
BlogHolder:
    restricted_widgets:
        - "BlogManagementWidget"
```

## Reporting an issue
When you're reporting an issue please ensure you specify what version of SilverStripe you are using i.e. 3.1.3, 3.2beta, master etc. Also be sure to include any JavaScript or PHP errors you receive, for PHP errors please ensure you include the full stack trace. Also please include how you produced the issue. You may also be asked to provide some of the classes to aid in re-producing the issue. Stick with the issue, remember that you seen the issue not the maintainer of the module so it may take allot of questions to arrive at a fix or answer.
