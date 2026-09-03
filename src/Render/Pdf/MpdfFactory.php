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

        foreach ($project->fontDirs() as $customFontsDir) {
            if (! in_array($customFontsDir, $fontDirs, true)) {
                $fontDirs[] = $customFontsDir;
            }
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
            // mPDF only asks languageToFont for a face once the text carries a
            // language tag, and it only tags runs by script when both switches
            // are on. Without them the rules never fire and non-Latin passages
            // fall through to the default font as tofu.
            $options['autoScriptToLang'] = true;
            $options['autoLangToFont'] = true;
            $options['languageToFont'] = new ScriptLanguageToFont($scriptRules);
        }

        try {
            return new Mpdf($options);
        } catch (MpdfException $e) {
            throw new PdfException($e->getMessage(), previous: $e);
        }
    }
}
