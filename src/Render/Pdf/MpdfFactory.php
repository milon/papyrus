<?php

declare(strict_types=1);

namespace Milon\Papyrus\Render\Pdf;

use Milon\Papyrus\Config\DocumentSize;
use Milon\Papyrus\Config\FontRegistry;
use Milon\Papyrus\Config\Project;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

final class MpdfFactory
{
    public static function create(Project $project, DocumentSize $document): Mpdf
    {
        $registry = FontRegistry::fromProject($project);

        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $customFontsDir = $project->assetsDir.'/fonts';

        if (is_dir($customFontsDir)) {
            $fontDirs[] = $customFontsDir;
        }

        $options = [
            'mode' => 'utf-8',
            'format' => [$document->widthMm, $document->heightMm],
            'margin_left' => $document->marginLeft,
            'margin_right' => $document->marginRight,
            'margin_top' => $document->marginTop,
            'margin_bottom' => $document->marginBottom,
            'fontDir' => $fontDirs,
            'fontdata' => [...$fontData, ...$registry->fontData],
            'default_font' => $registry->defaultFont,
        ];

        $scriptRules = $registry->applicableScriptRules();

        if ($scriptRules !== []) {
            $options['languageToFont'] = new ScriptLanguageToFont($scriptRules);
        }

        try {
            return new Mpdf($options);
        } catch (MpdfException $e) {
            throw new PdfException($e->getMessage(), previous: $e);
        }
    }
}
