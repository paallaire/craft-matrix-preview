<?php

namespace modules\matrixpreview\controllers;

use Craft;
use craft\fields\Matrix;
use craft\web\Controller;
use yii\web\Response;

/**
 * Gère la page /admin/settings/matrix-preview, qui permet de choisir
 * quels champs Matrix (natifs) doivent afficher le bouton "Show image preview".
 */
class SettingsController extends Controller
{
    /**
     * Affiche la liste des champs Matrix, chacun avec son lightswitch.
     */
    public function actionIndex(): Response
    {
        $this->requireAdmin();

        $projectConfig = Craft::$app->getProjectConfig();
        $webRoot = rtrim(Craft::getAlias('@web'), '/');

        $rows = [];
        foreach ($this->getMatrixFields() as $field) {
            $rows[] = [
                'uid' => $field->uid,
                'fieldName' => "{$field->name} ({$field->handle})",
                'enabled' => (bool) $projectConfig->get('matrixPreview.fields.' . $field->uid),
                // Dossier où doivent se trouver les images des entry types
                // de ce champ, ex. @web/contentBlocks/ (voir matrix-preview.js
                // pour le nom de fichier exact attendu par entry type).
                'imagePath' => "{$webRoot}/matrix-preview/{$field->handle}/",
            ];
        }

        return $this->renderTemplate('matrixpreview/settings/index', [
            'rows' => $rows,
        ]);
    }

    /**
     * Sauvegarde l'état des lightswitch dans le project config (donc dans le YAML).
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        // Chaque lightswitch a été posté sous fields[<uid>] = "1" ou "0",
        // donc ce tableau est directement indexé par UID de champ.
        $enabledByUid = Craft::$app->getRequest()->getBodyParam('fields', []);

        $projectConfig = Craft::$app->getProjectConfig();

        foreach ($this->getMatrixFields() as $field) {
            $enabled = !empty($enabledByUid[$field->uid]);

            $projectConfig->set(
                'matrixPreview.fields.' . $field->uid,
                $enabled,
                "Image preview toggle for Matrix field “{$field->handle}”"
            );
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * @return Matrix[]
     */
    private function getMatrixFields(): array
    {
        return array_values(array_filter(
            Craft::$app->getFields()->getAllFields(),
            fn($field) => $field instanceof Matrix
        ));
    }
}
