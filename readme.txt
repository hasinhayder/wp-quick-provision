=== WP Quick Provision ===
Contributors: hasinhayder
Tags: settings, provision, setup, management, development
Requires at least: 4
Tested up to: 5.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This is a powerful provisioning plugin to install multiple themes and plugins automatically by providing them as a list from https://gist.github.com. You can also update multiple options in your options table at once. This plugin can save your time from installing same set of themes and plugins again and again in your WordPress setup. Extremely handy to quickly setup your development platform.

== Description ==

This plugin cave save you from boredom and from doing the same tasks again and again you do after installing a fresh version of WordPress. You can provision your new setup by automatically installing themes and plugins using **WP Quick Provision** plugin, all by supplying a list of those themes and plugins from gist.github.com. Here is a valid data format that is required by this plugin to properly provision your WordPress installation.

```js
{
    "themes": [
        "astra",
        "hello-elementor",
        "wp-bootstrap-starter"
    ],
    "plugins": [
        "contact-form-7",
        "woocommerce",
        "elementor",
        "happy-elementor-addons",
        "query-monitor",
        "regenerate-thumbnails",
        "classic-editor",
        "jsm-show-post-meta"
    ]
}
```

URL: https://gist.github.com/hasinhayder/7b93c50e5f0ff11e26b9b8d81f81d306

As soon as you save this data on gist.github.com and add tis gist url in your plugin, it will start installing all these themes and plugins mentioned in your data. It will not download a plugin if it is already available in your WordPress setup.

After installing everything, **WP Quick Provision** will activate all these plugins.


== Installation ==

Installing **WP Quick Provision** is simple, just like any other WordPress plugin

e.g.

1. Upload `wp-quick-provision` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

or

1. Download the zip from WordPress plugin repository
2. Go to your plugin section, click add new and upload this zip
3. Activate

After installing you will find the link under **Tools** menu in your WordPress admin panel, named as "WP Quick Provision"

== Frequently Asked Questions ==

= is there any risk of losing my data =

No, it only installs new themes and plugins and it doesn't delete any of your existing themes or plugins.

= During installation, it times out =

Make sure to set your php script execution time to 300 or more.
