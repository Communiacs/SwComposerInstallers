<?php
if (!getenv('SHOPWARE_PATH_COMPOSER_ROOT')) {
    putenv('SHOPWARE_PATH_COMPOSER_ROOT=' . '{$root-dir}');
}
if (!getenv('SHOPWARE_PATH_ROOT')) {
    putenv('SHOPWARE_PATH_ROOT=' . '{$web-dir}');
}
if (!getenv('SHOPWARE_PATH_WEB')) {
    putenv('SHOPWARE_PATH_WEB=' . '{$web-dir}');
}
// '{$composer-mode}'
