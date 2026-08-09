<?php

namespace modules\matrixpreview;

use Craft;
use craft\base\Field;
use craft\base\Plugin as BasePlugin;
use craft\events\DefineFieldHtmlEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\fields\Matrix;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\Cp;
use craft\web\UrlManager;
use craft\web\View;
use modules\matrixpreview\assets\MatrixPreviewAsset;
use yii\base\Event;
use yii\web\Response;

/**
 * Version "plugin" du module Matrix Preview.
 *
 * Différences par rapport au module :
 * - Étend craft\base\Plugin au lieu de yii\base\Module.
 * - Pas de bootstrap dans config/app.php : Craft le découvre via composer.json
 *   (clé "extra") et il s'active/désactive dans Réglages > Plugins.
 * - hasCpSettings = true fait apparaître un lien "Matrix Preview" cliquable
 *   directement dans la liste des plugins installés.
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public function init(): void
    {
        parent::init();

        // Craft recommande d'attacher les event handlers dans onInit(),
        // une fois que l'application est complètement chargée.
        Craft::$app->onInit(function () {
            $this->attachEventHandlers();
        });
    }

    /**
     * Appelé quand on clique sur "Matrix Preview" dans Réglages > Plugins.
     * On redirige simplement vers notre page de réglages custom.
     */
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(
            UrlHelper::cpUrl('settings/matrix-preview')
        );
    }

    private function attachEventHandlers(): void
    {
        // Permet à Craft de trouver nos templates via le préfixe 'matrixpreview/...'
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $event) {
                $event->roots['matrixpreview'] = __DIR__ . '/templates';
            }
        );

        // Ajoute un lien "Matrix Preview" dans le menu de gauche du CP,
        // visible seulement par les admins.
        Event::on(
            Cp::class,
            Cp::EVENT_REGISTER_CP_NAV_ITEMS,
            function (RegisterCpNavItemsEvent $event) {
                if (!Craft::$app->getUser()->getIsAdmin()) {
                    return;
                }

                $event->navItems[] = [
                    'url' => 'settings/matrix-preview',
                    'label' => Craft::t('app', 'Matrix Preview'),
                    'icon' => 'image',
                ];
            }
        );

        // Routes CP pour notre page de réglages.
        // Le préfixe de route doit être le HANDLE du plugin ("matrix-preview",
        // déclaré dans composer.json > extra.handle), pas le namespace PHP.
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['settings/matrix-preview'] = 'matrix-preview/settings/index';
                $event->rules['settings/matrix-preview/save'] = 'matrix-preview/settings/save';
            }
        );

        // Ajoute notre bouton + JS sur l'input de chaque champ Matrix natif
        // dont le handle a été activé dans notre page de réglages.
        Event::on(
            Field::class,
            Field::EVENT_DEFINE_INPUT_HTML,
            function (DefineFieldHtmlEvent $event) {
                $field = $event->sender;

                if (!$field instanceof Matrix) {
                    return;
                }

                $enabled = Craft::$app->getProjectConfig()
                    ->get('matrixPreview.fields.' . $field->uid);

                if (!$enabled) {
                    return;
                }

                $this->registerPreviewButton($field);
            }
        );
    }

    private function registerPreviewButton(Matrix $field): void
    {
        $webRoot = rtrim(Craft::getAlias('@web'), '/');

        $entryTypes = [];
        foreach ($field->getEntryTypes() as $entryType) {
            $entryTypes[] = [
                'name' => Craft::t('site', $entryType->name),
                'handle' => $entryType->handle,
                'imageUrl' => "{$webRoot}/matrix-preview/{$field->handle}/{$entryType->handle}.jpg",
            ];
        }

        $view = Craft::$app->getView();
        $view->registerAssetBundle(MatrixPreviewAsset::class);
        $view->registerJs(
            'new Craft.MatrixPreview(' . json_encode([
                'containerId' => $view->namespaceInputId($field->handle),
                'entryTypes' => $entryTypes,
            ]) . ');'
        );
    }
}
