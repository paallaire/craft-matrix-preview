<?php

namespace modules\matrixpreview\assets;

use craft\web\AssetBundle;

class MatrixPreviewAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';
        $this->js = ['matrix-preview.js'];
        $this->css = ['matrix-preview.css'];
        parent::init();
    }
}
