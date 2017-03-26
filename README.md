Shopware Composer installers
=============================

This package acts as composer plugin in order to download and install
Shopware core and plugins and put them into a directory structure
which is suitable for TYPO3 to work correctly.

The behavior of the installer can be influenced by configuration in the `extra` section of the root `composer.json`

```
  "extra": {
      "shopware/shopware": {
          "web-dir": "web",
          "prepare-web-dir": true
      }
    }
```

#### `web-dir`
You can specify a relative path from the base directory, where the public document root should be located.

*The default value* is `""`, which means next to your root `composer.json`. This default value is kept for compatiblity reasons, but is recommended to have the document root in a separate folder so that the vendor folder and the `composer.lock` file are not accessible.

#### `prepare-web-dir`
Whether or not links to ... folders will be established in the web directory.
At a later point, this option might affect other actions like publishing assets.

*The default value* is `false`.
