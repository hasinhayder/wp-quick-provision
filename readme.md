# WP Quick Provision 

This plugin can save you from boredom and from doing the same tasks again and again you do after installing a fresh version of WordPress. You can provision your new setup by automatically installing themes and plugins using **WP Quick Provision** plugin, all by supplying a list of those themes and plugins from gist.github.com. Here is a valid data format that is required by this plugin to properly provision your WordPress installation.

```js
{
    "themes": [
        "hello-elementor",
        "wp-bootstrap-starter"
    ],
    "plugins": [
        "elementor",
        "happy-elementor-addons",
        "contact-form-7",
        "woocommerce",
        "query-monitor",
        "regenerate-thumbnails",
        "classic-editor",
        "jsm-show-post-meta"
    ]
}
```

Example Provision Data URL: [https://gist.github.com/hasinhayder/7b93c50e5f0ff11e26b9b8d81f81d306](https://gist.github.com/hasinhayder/7b93c50e5f0ff11e26b9b8d81f81d306) or [https://gist.github.com/hasinhayder/5cf59b883005e043454f5fe0d2d9546b](https://gist.github.com/hasinhayder/5cf59b883005e043454f5fe0d2d9546b)

As soon as you save this data on gist.github.com and add tis gist url in your plugin, it will start installing all these themes and plugins mentioned in your data. It will not download a plugin if it is already available in your WordPress setup.

From version 1.1 you can host your provision data anywhere and supply that URL to this plugin for provisioning.

After installing everything, **WP Quick Provision** will activate all these plugins.


## Installation 

Installing **WP Quick Provision** is simple, just like any other WordPress plugin

1. Upload `wp-quick-provision` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

or

1. Download the zip from WordPress plugin repository
2. Go to your plugin section, click add new and upload this zip
3. Activate

After installing you will find the link under **Tools** menu in your WordPress admin panel, named as "WP Quick Provision"

## Frequently Asked Questions

### is there any risk of losing my data 

No, it only installs new themes and plugins and it doesn't delete any of your existing themes or plugins.

### During installation, it times out 

Make sure to set your php script execution time to 300 or more.

## Changelog 

### 2.0.1
* URl validation fix

### 2.0
* Huge improvements on workflow
* All themes and plugins will be displayed in WP_List_Table before starting final provisioning
* Users can now uncheck any theme or plugin before final provisioning
* Huge UI Improvement
* Bulletproof external provision data validation

### 1.1.1 
* Minor code fix

### 1.1 
* Allow universal URL for provision data
* Added wpqp_data_source filter for provision data url
* Added support for external **public zip url** for the themes and plugins, please check the new data format at [https://gist.github.com/hasinhayder/5cf59b883005e043454f5fe0d2d9546b](https://gist.github.com/hasinhayder/5cf59b883005e043454f5fe0d2d9546b) - Note that old data format is still valid